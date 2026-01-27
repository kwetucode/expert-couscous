<?php

namespace App\Livewire\Proforma;

use App\Mail\ProformaInvoiceMail;
use App\Models\ProformaInvoice;
use App\Services\ProformaService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class ProformaIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $periodFilter = 'this_month';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $sortField = 'proforma_date';
    public $sortDirection = 'desc';

    public $proformaToDelete = null;
    public $proformaToProcess = null;
    public $actionType = '';
    public $showActionModal = false;
    public $showDeleteModal = false;

    // Pour l'envoi par email
    public $showEmailModal = false;
    public $emailTo = '';
    public $proformaToSend = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'periodFilter' => ['except' => 'this_month'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'proforma_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        // Apply default period filter
        $this->applyPeriodFilter($this->periodFilter);
    }

    /**
     * Apply period filter to set date range
     */
    public function applyPeriodFilter(?string $period): void
    {
        if (!$period || $period === 'custom') {
            return;
        }

        $now = now();

        switch ($period) {
            case 'today':
                $this->dateFrom = $now->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'yesterday':
                $yesterday = $now->copy()->subDay();
                $this->dateFrom = $yesterday->format('Y-m-d');
                $this->dateTo = $yesterday->format('Y-m-d');
                break;

            case 'this_week':
                $this->dateFrom = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'last_week':
                $this->dateFrom = $now->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->dateTo = $now->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                break;

            case 'this_month':
                $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'last_month':
                $this->dateFrom = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;

            case 'last_3_months':
                $this->dateFrom = $now->copy()->subMonths(3)->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'last_6_months':
                $this->dateFrom = $now->copy()->subMonths(6)->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'this_year':
                $this->dateFrom = $now->copy()->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;

            case 'last_year':
                $this->dateFrom = $now->copy()->subYear()->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->copy()->subYear()->endOfYear()->format('Y-m-d');
                break;

            case 'all':
                $this->dateFrom = '';
                $this->dateTo = '';
                break;
        }
    }

    /**
     * Get period label for display
     */
    public function getPeriodLabel(): string
    {
        return match($this->periodFilter) {
            'today' => 'Aujourd\'hui',
            'yesterday' => 'Hier',
            'this_week' => 'Cette semaine',
            'last_week' => 'Semaine dernière',
            'this_month' => 'Ce mois',
            'last_month' => 'Mois dernier',
            'last_3_months' => '3 derniers mois',
            'last_6_months' => '6 derniers mois',
            'this_year' => 'Cette année',
            'last_year' => 'Année dernière',
            'all' => 'Toutes les dates',
            'custom' => 'Personnalisé',
            default => 'Ce mois'
        };
    }

    public function updatedPeriodFilter($value)
    {
        $this->applyPeriodFilter($value);
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->periodFilter = 'custom';
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->periodFilter = 'custom';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function markAsSent(ProformaService $service)
    {
        if (!$this->proformaToProcess) return;

        try {
            $proforma = ProformaInvoice::findOrFail($this->proformaToProcess);
            $service->markAsSent($proforma);
            $this->dispatch('show-toast', message: 'Proforma marquée comme envoyée.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }

        $this->proformaToProcess = null;
    }

    /**
     * Préparer l'envoi par email - ouvre la modal
     */
    public function prepareEmailSend($proformaId)
    {
        $proforma = ProformaInvoice::find($proformaId);
        if (!$proforma) return;

        $this->proformaToSend = $proformaId;
        $this->emailTo = $proforma->client_email ?? '';
        $this->showEmailModal = true;
    }

    /**
     * Préparer une action (accept, reject, convert) - ouvre la modal
     */
    public function prepareAction($proformaId, $action)
    {
        $this->proformaToProcess = $proformaId;
        $this->actionType = $action;
        $this->showActionModal = true;
    }

    /**
     * Préparer la suppression - ouvre la modal
     */
    public function prepareDelete($proformaId)
    {
        $this->proformaToDelete = $proformaId;
        $this->showDeleteModal = true;
    }

    /**
     * Fermer la modal d'action
     */
    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->proformaToProcess = null;
        $this->actionType = '';
    }

    /**
     * Fermer la modal de suppression
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->proformaToDelete = null;
    }

    /**
     * Envoyer la proforma par email
     */
    public function sendByEmail(ProformaService $service)
    {
        $this->validate([
            'emailTo' => 'required|email',
        ], [
            'emailTo.required' => 'L\'adresse email est requise.',
            'emailTo.email' => 'L\'adresse email n\'est pas valide.',
        ]);

        if (!$this->proformaToSend) return;

        try {
            $proforma = ProformaInvoice::with(['items.productVariant.product', 'store', 'user'])->findOrFail($this->proformaToSend);

            // Mettre à jour l'email du client si différent
            if ($proforma->client_email !== $this->emailTo) {
                $proforma->update(['client_email' => $this->emailTo]);
            }

            // Envoyer l'email
            Mail::to($this->emailTo)->send(new ProformaInvoiceMail($proforma));

            // Marquer comme envoyée si en brouillon
            if ($proforma->status === ProformaInvoice::STATUS_DRAFT) {
                $service->markAsSent($proforma);
            }

            $this->dispatch('show-toast', message: "Proforma envoyée par email à {$this->emailTo}", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur lors de l\'envoi : ' . $e->getMessage(), type: 'error');
        }

        $this->closeEmailModal();
    }

    /**
     * Fermer la modal d'email
     */
    public function closeEmailModal()
    {
        $this->showEmailModal = false;
        $this->emailTo = '';
        $this->proformaToSend = null;
    }

    public function accept(ProformaService $service)
    {
        if (!$this->proformaToProcess) return;

        try {
            $proforma = ProformaInvoice::findOrFail($this->proformaToProcess);
            $service->accept($proforma);
            $this->dispatch('show-toast', message: 'Proforma acceptée.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }

        $this->closeActionModal();
    }

    public function reject(ProformaService $service)
    {
        if (!$this->proformaToProcess) return;

        try {
            $proforma = ProformaInvoice::findOrFail($this->proformaToProcess);
            $service->reject($proforma);
            $this->dispatch('show-toast', message: 'Proforma refusée.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }

        $this->closeActionModal();
    }

    public function convert(ProformaService $service)
    {
        if (!$this->proformaToProcess) return;

        try {
            $proforma = ProformaInvoice::with('items')->findOrFail($this->proformaToProcess);
            $invoice = $service->convertToInvoice($proforma);

            $this->closeActionModal();
            $this->dispatch('show-toast', message: "Proforma convertie en facture {$invoice->invoice_number}.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
            $this->closeActionModal();
        }
    }

    public function duplicate($proformaId, ProformaService $service)
    {
        try {
            $proforma = ProformaInvoice::findOrFail($proformaId);
            $newProforma = $service->duplicate($proforma);
            $this->dispatch('show-toast', message: "Proforma dupliquée : {$newProforma->proforma_number}", type: 'success');
            return redirect()->route('proformas.edit', $newProforma);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function delete()
    {
        if (!$this->proformaToDelete) return;

        try {
            $proforma = ProformaInvoice::findOrFail($this->proformaToDelete);

            if ($proforma->status !== ProformaInvoice::STATUS_DRAFT) {
                $this->dispatch('show-toast', message: 'Seules les proformas en brouillon peuvent être supprimées.', type: 'error');
                $this->closeDeleteModal();
                return;
            }

            $proforma->items()->delete();
            $proforma->delete();
            $this->dispatch('show-toast', message: 'Proforma supprimée avec succès.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }

        $this->closeDeleteModal();
    }

    public function getStatisticsProperty(ProformaService $service): array
    {
        return $service->getStatistics(current_store_id());
    }

    public function render()
    {
        $proformas = ProformaInvoice::query()
            ->with(['store', 'user']);

        // Filter by current store
        if (current_store_id()) {
            $proformas->where('store_id', current_store_id());
        }

        $proformas->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('proforma_number', 'like', "%{$this->search}%")
                      ->orWhere('client_name', 'like', "%{$this->search}%")
                      ->orWhere('client_email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('proforma_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('proforma_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.proforma.proforma-index', [
            'proformas' => $proformas->paginate($this->perPage),
            'statistics' => $this->statistics,
        ]);
    }
}
