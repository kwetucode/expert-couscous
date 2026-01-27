<?php

namespace App\Livewire\Invoice;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\DeleteInvoiceAction;
use App\Actions\Invoice\MarkInvoiceAsPaidAction;
use App\Actions\Invoice\SendInvoiceAction;
use App\Actions\Invoice\CancelInvoiceAction;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceExcelExporter;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $periodFilter = 'today';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $sortField = 'invoice_date';
    public $sortDirection = 'desc';

    public $invoiceToDelete = null;
    public $invoiceToProcess = null;
    public $actionType = '';

    // Create invoice modal properties
    public $saleId = null;
    public $invoiceDate;
    public $dueDate;
    public $invoiceStatus = 'draft';
    public $selectedSale = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'periodFilter' => ['except' => 'today'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'invoice_date'],
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
            default => 'Aujourd\'hui'
        };
    }

    public function updatedPeriodFilter($value)
    {
        $this->applyPeriodFilter($value);
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

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        if ($this->periodFilter !== 'custom') {
            $this->periodFilter = 'custom';
        }
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        if ($this->periodFilter !== 'custom') {
            $this->periodFilter = 'custom';
        }
    }

    public function openCreateModal()
    {
        $this->saleId = null;
        $this->invoiceDate = now()->format('Y-m-d');
        $this->dueDate = now()->addDays(30)->format('Y-m-d');
        $this->invoiceStatus = 'draft';
        $this->selectedSale = null;
        $this->resetValidation();
        $this->dispatch('open-create-modal');
    }

    public function closeCreateModal()
    {
        $this->saleId = null;
        $this->selectedSale = null;
        $this->resetValidation();
        $this->dispatch('close-create-modal');
    }

    public function updatedSaleId($value)
    {
        if ($value) {
            $repository = app(\App\Repositories\SaleRepository::class);
            $this->selectedSale = $repository->find($value);
        } else {
            $this->selectedSale = null;
        }
    }

    public function createInvoice()
    {
        $this->validate([
            'saleId' => 'required|exists:sales,id',
            'invoiceDate' => 'required|date',
            'dueDate' => 'nullable|date|after_or_equal:invoiceDate',
            'invoiceStatus' => 'required|in:draft,sent',
        ], [
            'saleId.required' => 'Veuillez sélectionner une vente.',
            'saleId.exists' => 'La vente sélectionnée n\'existe pas.',
            'invoiceDate.required' => 'La date de facturation est requise.',
            'invoiceDate.date' => 'Format de date invalide.',
            'dueDate.date' => 'Format de date invalide.',
            'dueDate.after_or_equal' => 'La date d\'échéance doit être postérieure ou égale à la date de facturation.',
            'invoiceStatus.required' => 'Le statut est requis.',
        ]);

        try {
            $action = app(CreateInvoiceAction::class);
            $invoice = $action->execute($this->saleId, [
                'invoice_date' => $this->invoiceDate,
                'due_date' => $this->dueDate,
                'status' => $this->invoiceStatus,
            ]);

            session()->flash('success', 'Facture créée avec succès.');
            $this->closeCreateModal();
            $this->dispatch('close-create-modal');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
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

    public function markAsPaid()
    {
        if (!$this->invoiceToProcess) {
            return;
        }

        try {
            $action = app(MarkInvoiceAsPaidAction::class);
            $repository = app(InvoiceRepository::class);

            $invoice = $repository->find($this->invoiceToProcess);

            if ($invoice) {
                $action->execute($invoice->id);
                session()->flash('success', 'Facture marquée comme payée avec succès.');
            } else {
                session()->flash('error', 'Facture introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->invoiceToProcess = null;
    }

    public function sendInvoice()
    {
        if (!$this->invoiceToProcess) {
            return;
        }

        try {
            $action = app(SendInvoiceAction::class);
            $repository = app(InvoiceRepository::class);

            $invoice = $repository->find($this->invoiceToProcess);

            if ($invoice) {
                $action->execute($invoice->id);
                session()->flash('success', 'Facture envoyée avec succès.');
            } else {
                session()->flash('error', 'Facture introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->invoiceToProcess = null;
    }

    public function cancelInvoice()
    {
        if (!$this->invoiceToProcess) {
            return;
        }

        try {
            $action = app(CancelInvoiceAction::class);
            $repository = app(InvoiceRepository::class);

            $invoice = $repository->find($this->invoiceToProcess);

            if ($invoice) {
                $action->execute($invoice->id);
                session()->flash('success', 'Facture annulée avec succès.');
            } else {
                session()->flash('error', 'Facture introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->invoiceToProcess = null;
    }

    public function delete()
    {
        if (!$this->invoiceToDelete) {
            return;
        }

        try {
            $action = app(DeleteInvoiceAction::class);
            $repository = app(InvoiceRepository::class);

            $invoice = $repository->find($this->invoiceToDelete);

            if ($invoice) {
                $action->execute($invoice->id);
                session()->flash('success', 'Facture supprimée avec succès.');
            } else {
                session()->flash('error', 'Facture introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->invoiceToDelete = null;
    }

    public function exportExcel(InvoiceRepository $repository, InvoiceExcelExporter $exporter)
    {
        try {
            $query = $repository->query()
                ->with(['sale.client', 'sale.items']);

            // Apply search
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('sale.client', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            }

            // Apply status filter
            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            // Apply date range filter
            if ($this->dateFrom && $this->dateTo) {
                $query->whereDate('invoice_date', '>=', $this->dateFrom)
                      ->whereDate('invoice_date', '<=', $this->dateTo);
            } elseif ($this->dateFrom) {
                $query->whereDate('invoice_date', '>=', $this->dateFrom);
            } elseif ($this->dateTo) {
                $query->whereDate('invoice_date', '<=', $this->dateTo);
            }

            // Apply sorting
            $query->orderBy($this->sortField, $this->sortDirection);

            $invoices = $query->get();

            // Get period label for export
            $periodLabel = $this->getPeriodLabel();

            return $exporter->export($invoices, $this->dateFrom, $this->dateTo, $periodLabel);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'export : ' . $e->getMessage());
            return null;
        }
    }

    public function exportPdf()
    {
        $params = [
            'period' => $this->periodFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
        ];

        // Remove empty values
        $params = array_filter($params, fn($value) => $value !== '' && $value !== null);

        return redirect()->route('reports.invoices', $params);
    }

    public function render(InvoiceRepository $repository, \App\Repositories\SaleRepository $saleRepository)
    {
        $query = $repository->query()
            ->with(['sale.client', 'sale.items']);

        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('sale.client', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->where('invoice_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('invoice_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $invoices = $query->paginate($this->perPage);

        // Calculate statistics
        $statistics = $repository->statistics();

        // Get sales without invoices for the create modal
        $availableSales = $saleRepository->query()
            ->whereDoesntHave('invoice')
            ->where('status', 'completed')
            ->with('client')
            ->orderBy('sale_date', 'desc')
            ->get();

        return view('livewire.invoice.invoice-index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'availableSales' => $availableSales,
        ]);
    }
}
