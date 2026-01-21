<?php

namespace App\Livewire\Admin;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class SubscriptionSettings extends Component
{
    // Edit modal
    public bool $showEditModal = false;
    public ?int $editingPlanId = null;
    public array $editForm = [
        'name' => '',
        'price' => 0,
        'max_stores' => 1,
        'max_users' => 3,
        'max_products' => 100,
        'features' => [],
    ];

    // Discount settings
    public array $discounts = [
        '3_months' => 5,
        '6_months' => 10,
        '12_months' => 20,
    ];

    // General settings
    public int $trialDays = 14;
    public string $currency = 'CDF';

    public function mount(): void
    {
        $this->authorize('viewAny', \App\Models\Organization::class);
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        // Charger les paramètres depuis le cache ou utiliser les valeurs par défaut
        $cachedDiscounts = Cache::get('subscription_discounts');
        $this->discounts = $cachedDiscounts ? $this->ensureArray($cachedDiscounts) : [
            '3_months' => 5,
            '6_months' => 10,
            '12_months' => 20,
        ];

        $this->trialDays = (int) Cache::get('subscription_trial_days', 14);
        $this->currency = Cache::get('subscription_currency', 'CDF') ?: 'CDF';
    }

    /**
     * Convertit récursivement un objet/array en array PHP natif
     */
    protected function ensureArray(mixed $data): array
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = $this->ensureArray($value);
            }
        }

        return $data;
    }

    public function openEditModal(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $this->editingPlanId = $planId;
        $this->editForm = [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => $plan->price,
            'max_stores' => $plan->max_stores,
            'max_users' => $plan->max_users,
            'max_products' => $plan->max_products,
            'features_text' => implode("\n", $plan->features ?? []),
        ];
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingPlanId = null;
        $this->editForm = [];
    }

    public function savePlan(): void
    {
        $this->validate([
            'editForm.name' => 'required|string|max:50',
            'editForm.price' => 'required|numeric|min:0',
            'editForm.max_stores' => 'required|integer|min:1',
            'editForm.max_users' => 'required|integer|min:1',
            'editForm.max_products' => 'required|integer|min:1',
            'editForm.features_text' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($this->editingPlanId);

        // Convertir le texte des fonctionnalités en tableau
        $features = array_filter(
            array_map('trim', explode("\n", $this->editForm['features_text'] ?? '')),
            fn($f) => !empty($f)
        );

        $plan->update([
            'name' => $this->editForm['name'],
            'price' => (int) $this->editForm['price'],
            'max_stores' => (int) $this->editForm['max_stores'],
            'max_users' => (int) $this->editForm['max_users'],
            'max_products' => (int) $this->editForm['max_products'],
            'features' => array_values($features),
        ]);

        $this->closeEditModal();
        $this->dispatch('show-toast', message: 'Plan mis à jour avec succès !', type: 'success');
    }

    public function saveDiscounts(): void
    {
        $this->validate([
            'discounts.3_months' => 'required|numeric|min:0|max:100',
            'discounts.6_months' => 'required|numeric|min:0|max:100',
            'discounts.12_months' => 'required|numeric|min:0|max:100',
        ]);

        Cache::forever('subscription_discounts', $this->discounts);
        $this->dispatch('show-toast', message: 'Réductions mises à jour !', type: 'success');
    }

    public function saveGeneralSettings(): void
    {
        $this->validate([
            'trialDays' => 'required|integer|min:0|max:90',
            'currency' => 'required|string|size:3',
        ]);

        Cache::forever('subscription_trial_days', $this->trialDays);
        Cache::forever('subscription_currency', $this->currency);

        $this->dispatch('show-toast', message: 'Paramètres généraux mis à jour !', type: 'success');
    }

    public function togglePopular(int $planId): void
    {
        // Désactiver "populaire" sur tous les plans
        SubscriptionPlan::query()->update(['is_popular' => false]);

        // Activer sur le plan sélectionné
        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_popular' => !$plan->is_popular]);

        $this->dispatch('show-toast', message: 'Plan mis en avant !', type: 'success');
    }

    public function resetToDefaults(): void
    {
        // Réexécuter le seeder pour réinitialiser les plans
        \Artisan::call('db:seed', ['--class' => 'SubscriptionPlanSeeder']);

        $this->discounts = [
            '3_months' => 5,
            '6_months' => 10,
            '12_months' => 20,
        ];
        $this->trialDays = 14;
        $this->currency = 'CDF';

        Cache::forget('subscription_discounts');
        Cache::forget('subscription_trial_days');
        Cache::forget('subscription_currency');

        $this->dispatch('show-toast', message: 'Paramètres réinitialisés !', type: 'success');
    }

    /**
     * Obtenir les statistiques des abonnements
     */
    public function getStatsProperty(): array
    {
        $stats = \App\Models\Organization::query()
            ->selectRaw('subscription_plan, COUNT(*) as count')
            ->groupBy('subscription_plan')
            ->orderBy('subscription_plan')
            ->pluck('count', 'subscription_plan')
            ->toArray();

        $totalRevenue = SubscriptionPayment::query()
            ->where('status', 'completed')
            ->sum('total');

        $monthlyRevenue = SubscriptionPayment::query()
            ->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        return [
            'by_plan' => $stats,
            'total_organizations' => array_sum($stats),
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    public function render()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('livewire.admin.subscription-settings', [
            'plans' => $plans,
            'stats' => $this->stats,
        ]);
    }
}
