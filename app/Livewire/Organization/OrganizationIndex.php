<?php

namespace App\Livewire\Organization;

use App\Models\Organization;
use App\Models\SubscriptionHistory;
use App\Services\OrganizationService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    // Subscription management modal
    public ?int $subscriptionOrgId = null;
    public ?string $subscriptionOrgName = null;
    public ?string $subscriptionStartsAt = null;
    public ?string $subscriptionEndsAt = null;

    // Subscription history modal
    public ?int $historyOrgId = null;
    public ?string $historyOrgName = null;
    public string $historyActionFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    /**
     * Switch to another organization
     */
    public function switchTo(int $organizationId): mixed
    {
        $user = auth()->user();

        // Super admin peut switcher vers n'importe quelle organisation
        if (!$user->isSuperAdmin() && !$user->belongsToOrganization($organizationId)) {
            session()->flash('error', 'Accès non autorisé à cette organisation.');
            return null;
        }

        $organization = Organization::find($organizationId);

        if (!$organization) {
            session()->flash('error', 'Organisation introuvable.');
            return null;
        }

        session(['current_organization_id' => $organization->id]);
        $user->update(['default_organization_id' => $organization->id]);

        session()->flash('success', "Vous êtes maintenant dans l'organisation \"{$organization->name}\".");

        return $this->redirect(route('dashboard'));
    }

    /**
     * Sort by column
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Listen for organization created event
     */
    #[On('organization-created')]
    public function refreshOnCreate(): void
    {
        $this->resetPage();
        $this->dispatch('\$refresh');
    }

    /**
     * Listen for organization updated event
     */
    #[On('organization-updated')]
    public function refreshOnUpdate(): void
    {
        $this->dispatch('\$refresh');
    }

    /**
     * Listen for organization deleted event
     */
    #[On('organization-deleted')]
    public function refreshOnDelete(): void
    {
        $this->resetPage();
        $this->dispatch('\$refresh');
    }

    /**
     * Open subscription management modal
     */
    public function openSubscriptionModal(int $organizationId): void
    {
        $user = auth()->user();
        
        // Only super admin can manage subscription dates
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'Accès non autorisé.');
            return;
        }

        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            session()->flash('error', 'Organisation introuvable.');
            return;
        }

        $this->subscriptionOrgId = $organization->id;
        $this->subscriptionOrgName = $organization->name;
        $this->subscriptionStartsAt = $organization->subscription_starts_at?->format('Y-m-d');
        $this->subscriptionEndsAt = $organization->subscription_ends_at?->format('Y-m-d');

        $this->dispatch('open-subscription-modal');
    }

    /**
     * Update subscription dates
     */
    public function updateSubscriptionDates(): void
    {
        $user = auth()->user();
        
        // Only super admin can manage subscription dates
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'Accès non autorisé.');
            return;
        }

        $this->validate([
            'subscriptionStartsAt' => 'nullable|date',
            'subscriptionEndsAt' => 'nullable|date|after_or_equal:subscriptionStartsAt',
        ], [
            'subscriptionEndsAt.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $organization = Organization::find($this->subscriptionOrgId);
        
        if (!$organization) {
            session()->flash('error', 'Organisation introuvable.');
            return;
        }

        // Sauvegarder les anciennes valeurs pour l'historique
        $oldStartsAt = $organization->subscription_starts_at?->format('Y-m-d');
        $oldEndsAt = $organization->subscription_ends_at?->format('Y-m-d');
        
        $newStartsAt = $this->subscriptionStartsAt;
        $newEndsAt = $this->subscriptionEndsAt;

        // Mettre à jour l'organisation
        $organization->update([
            'subscription_starts_at' => $newStartsAt ? \Carbon\Carbon::parse($newStartsAt)->startOfDay() : null,
            'subscription_ends_at' => $newEndsAt ? \Carbon\Carbon::parse($newEndsAt)->endOfDay() : null,
        ]);

        // Recharger l'organisation pour avoir les nouvelles dates
        $organization->refresh();

        // Déterminer le type d'action
        $action = \App\Models\SubscriptionHistory::ACTION_DATES_MODIFIED;
        
        // Si on prolonge la date de fin, c'est une extension
        if ($oldEndsAt && $newEndsAt && $newEndsAt > $oldEndsAt) {
            $action = \App\Models\SubscriptionHistory::ACTION_EXTENDED;
        }

        // Créer une note descriptive
        $notes = "Modification manuelle par {$user->name}. ";
        if ($oldStartsAt !== $newStartsAt) {
            $notes .= "Début: " . ($oldStartsAt ?? 'vide') . " → " . ($newStartsAt ?? 'vide') . ". ";
        }
        if ($oldEndsAt !== $newEndsAt) {
            $notes .= "Fin: " . ($oldEndsAt ?? 'vide') . " → " . ($newEndsAt ?? 'vide') . ".";
        }

        // Enregistrer dans l'historique
        \App\Models\SubscriptionHistory::record(
            organization: $organization,
            action: $action,
            oldPlan: $organization->subscription_plan instanceof \App\Enums\SubscriptionPlan 
                ? $organization->subscription_plan->value 
                : $organization->subscription_plan,
            payment: null,
            notes: trim($notes),
            user: $user
        );

        $this->dispatch('close-subscription-modal');
        $this->resetSubscriptionModal();
        
        session()->flash('success', "Les dates d'abonnement de \"{$organization->name}\" ont été mises à jour.");
    }

    /**
     * Reset subscription modal state
     */
    public function resetSubscriptionModal(): void
    {
        $this->subscriptionOrgId = null;
        $this->subscriptionOrgName = null;
        $this->subscriptionStartsAt = null;
        $this->subscriptionEndsAt = null;
    }

    /**
     * Open subscription history modal
     */
    public function openHistoryModal(int $organizationId): void
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'Accès non autorisé.');
            return;
        }

        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            session()->flash('error', 'Organisation introuvable.');
            return;
        }

        $this->historyOrgId = $organization->id;
        $this->historyOrgName = $organization->name;
        $this->historyActionFilter = '';

        $this->dispatch('open-history-modal');
    }

    /**
     * Reset history modal state
     */
    public function resetHistoryModal(): void
    {
        $this->historyOrgId = null;
        $this->historyOrgName = null;
        $this->historyActionFilter = '';
    }

    /**
     * Sync product types and categories for an organization
     */
    public function syncProductTypesAndCategories(int $organizationId, OrganizationService $organizationService): void
    {
        $user = auth()->user();
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            $this->dispatch('show-toast', message: 'Organisation introuvable.', type: 'error');
            return;
        }
        
        // Super admin or organization admin can sync
        if (!$user->isSuperAdmin() && !$user->isOrganizationAdmin($organization)) {
            $this->dispatch('show-toast', message: 'Accès non autorisé.', type: 'error');
            return;
        }

        try {
            $organizationService->initializeProductTypesAndCategories($organization);
            
            $this->dispatch('show-toast', 
                message: "Les types de produits et catégories de \"{$organization->name}\" ont été synchronisés.", 
                type: 'success'
            );
        } catch (\Exception $e) {
            $this->dispatch('show-toast', 
                message: 'Erreur lors de la synchronisation: ' . $e->getMessage(), 
                type: 'error'
            );
        }
    }

    /**
     * Get subscription history for the selected organization
     */
    public function getSubscriptionHistory()
    {
        if (!$this->historyOrgId) {
            return collect();
        }

        return SubscriptionHistory::query()
            ->where('organization_id', $this->historyOrgId)
            ->with(['user', 'payment'])
            ->when($this->historyActionFilter, fn($q) => $q->where('action', $this->historyActionFilter))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        $user = auth()->user();
        
        // Si super admin, afficher toutes les organisations
        if ($user->isSuperAdmin()) {
            $organizations = Organization::query()
                ->with(['stores', 'owner', 'members'])
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->when($this->type, fn($q) => $q->where('type', $this->type))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10);
        } else {
            // Sinon, afficher uniquement les organisations de l'utilisateur
            $organizations = $user->organizations()
                ->with(['stores', 'owner', 'members'])
                ->when($this->search, fn($q) => $q->where('organizations.name', 'like', "%{$this->search}%"))
                ->when($this->type, fn($q) => $q->where('organizations.type', $this->type))
                ->orderBy("organizations.{$this->sortBy}", $this->sortDirection)
                ->paginate(10);
        }

        $types = [
            'individual' => 'Entrepreneur individuel',
            'company' => 'Entreprise',
            'franchise' => 'Franchise',
            'cooperative' => 'Coopérative',
            'group' => 'Groupe commercial',
        ];

        // Get history if modal is open
        $subscriptionHistory = $this->getSubscriptionHistory();

        return view('livewire.organization.organization-index', [
            'organizations' => $organizations,
            'types' => $types,
            'subscriptionHistory' => $subscriptionHistory,
            'historyActions' => SubscriptionHistory::ACTION_LABELS,
        ]);
    }
}
