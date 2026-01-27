<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\DeletePurchaseAction;
use App\Repositories\PurchaseRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\UserRepository;
use App\Services\PurchaseService;
use App\Services\PurchaseExcelExporter;
use App\Mail\PurchasesReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $supplierFilter = '';
    public $statusFilter = '';
    public $periodFilter = 'this_month';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $sortField = 'purchase_date';
    public $sortDirection = 'desc';

    public $purchaseToDelete = null;
    public $purchaseToReceive = null;
    public $purchaseToCancel = null;
    public $purchaseToRestore = null;
    public $selectedPurchase = null;

    // Email modal properties
    public $selectedUserId = null;
    public $selectedUserId2 = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'supplierFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'periodFilter' => ['except' => 'this_month'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'purchase_date'],
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

    public function updatingSupplierFilter()
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

    /**
     * Get users for email modal dropdown
     */
    public function getUsersProperty()
    {
        $userRepository = app(UserRepository::class);
        return $userRepository->getAllWithFilters(perPage: 100)->items();
    }

    /**
     * Open email modal
     */
    public function openEmailModal()
    {
        $this->dispatch('open-email-modal');
    }

    /**
     * Close email modal
     */
    public function closeEmailModal()
    {
        $this->selectedUserId = null;
        $this->selectedUserId2 = null;
        $this->dispatch('close-email-modal');
    }

    /**
     * Export purchases to PDF
     */
    public function exportPdf(PurchaseRepository $repository)
    {
        $purchases = $this->getFilteredPurchases($repository);
        $periodLabel = $this->getPeriodLabel();
        $stats = $this->calculateStats($repository);

        $pdfTotals = [
            'received_count' => $purchases->where('status', 'received')->count(),
            'received_amount' => $purchases->where('status', 'received')->sum('total'),
            'pending_count' => $purchases->where('status', 'pending')->count(),
            'pending_amount' => $purchases->where('status', 'pending')->sum('total'),
        ];

        $pdf = Pdf::loadView('reports.purchases', [
            'title' => 'Rapport des Achats',
            'date' => now()->format('d/m/Y H:i'),
            'purchases' => $purchases,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'periodLabel' => $periodLabel,
            'totals' => $pdfTotals,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'achats_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Export purchases to Excel
     */
    public function exportExcel(PurchaseRepository $repository, PurchaseExcelExporter $exporter)
    {
        $purchases = $this->getFilteredPurchases($repository);
        $periodLabel = $this->getPeriodLabel();

        return $exporter->export($purchases, $this->dateFrom, $this->dateTo, $periodLabel);
    }

    /**
     * Send purchases report via email
     */
    public function sendReportEmail(PurchaseRepository $repository, PurchaseExcelExporter $exporter, UserRepository $userRepository)
    {
        Log::info('sendReportEmail called for purchases', ['selectedUserId' => $this->selectedUserId, 'selectedUserId2' => $this->selectedUserId2]);

        if (!$this->selectedUserId && !$this->selectedUserId2) {
            session()->flash('error', 'Veuillez sélectionner au moins un utilisateur.');
            return;
        }

        $pdfPath = null;
        $excelPath = null;

        try {
            // Get selected users
            $users = collect();

            if ($this->selectedUserId) {
                $user1 = $userRepository->find($this->selectedUserId);
                if ($user1 && $user1->email) {
                    $users->push($user1);
                }
            }

            if ($this->selectedUserId2 && $this->selectedUserId2 != $this->selectedUserId) {
                $user2 = $userRepository->find($this->selectedUserId2);
                if ($user2 && $user2->email) {
                    $users->push($user2);
                }
            }

            Log::info('Users found for purchases report', ['count' => $users->count(), 'emails' => $users->pluck('email')]);

            if ($users->isEmpty()) {
                session()->flash('error', 'Aucun utilisateur valide sélectionné.');
                return;
            }

            // Get filtered purchases
            $purchases = $this->getFilteredPurchases($repository);
            $periodLabel = $this->getPeriodLabel();
            $stats = $this->calculateStats($repository);

            // Calculate totals for PDF template
            $pdfTotals = [
                'received_count' => $purchases->where('status', 'received')->count(),
                'received_amount' => $purchases->where('status', 'received')->sum('total'),
                'pending_count' => $purchases->where('status', 'pending')->count(),
                'pending_amount' => $purchases->where('status', 'pending')->sum('total'),
            ];

            // Create temp directory if not exists
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate temp PDF file
            $pdf = Pdf::loadView('reports.purchases', [
                'title' => 'Rapport des Achats',
                'date' => now()->format('d/m/Y H:i'),
                'purchases' => $purchases,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'periodLabel' => $periodLabel,
                'totals' => $pdfTotals,
            ]);
            $pdfPath = $tempDir . '/rapport_achats_' . time() . '_' . uniqid() . '.pdf';
            $pdf->save($pdfPath);

            // Generate temp Excel file
            $excelPath = $tempDir . '/rapport_achats_' . time() . '_' . uniqid() . '.xlsx';
            $exporter->exportToFile($purchases, $this->dateFrom, $this->dateTo, $periodLabel, $excelPath);

            // Send email to each selected user
            $sentEmails = [];
            foreach ($users as $user) {
                Log::info('Sending purchases report email to: ' . $user->email);

                $mailable = new PurchasesReportMail(
                    recipientName: $user->name,
                    periodLabel: $periodLabel,
                    dateFrom: $this->dateFrom ?: null,
                    dateTo: $this->dateTo ?: null,
                    totals: $stats,
                    pdfPath: $pdfPath,
                    excelPath: $excelPath
                );

                Mail::to($user->email)->send($mailable);
                $sentEmails[] = $user->email;

                Log::info('Purchases report email sent successfully to: ' . $user->email);
            }

            // Cleanup temp files
            if ($pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            if ($excelPath && file_exists($excelPath)) {
                unlink($excelPath);
            }

            $this->closeEmailModal();
            session()->flash('success', 'Le rapport a été envoyé avec succès à ' . implode(' et ', $sentEmails));
        } catch (\Exception $e) {
            // Cleanup temp files in case of error
            if ($pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            if ($excelPath && file_exists($excelPath)) {
                unlink($excelPath);
            }

            Log::error('Error sending purchases report email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }

    /**
     * Get filtered purchases collection
     */
    private function getFilteredPurchases(PurchaseRepository $repository)
    {
        $query = $repository->query()->with(['supplier', 'items.productVariant.product']);

        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('purchase_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('supplier', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply supplier filter
        if ($this->supplierFilter) {
            $query->where('supplier_id', $this->supplierFilter);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date range filter
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->where('purchase_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->where('purchase_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->get();
    }

    public function showDetails($purchaseId, PurchaseRepository $repository)
    {
        $this->selectedPurchase = $repository->find($purchaseId);

        if ($this->selectedPurchase) {
            $this->selectedPurchase->load(['supplier', 'items.productVariant.product']);
        }
    }

    public function receivePurchase(PurchaseService $service, PurchaseRepository $repository)
    {
        if (!$this->purchaseToReceive) {
            return;
        }

        try {
            $purchase = $repository->find($this->purchaseToReceive);

            if ($purchase) {
                $service->markAsReceived($purchase->id);
                $this->dispatch('show-toast', message: 'Achat réceptionné avec succès.', type: 'success');
            } else {
                $this->dispatch('show-toast', message: 'Achat introuvable.', type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }

        $this->purchaseToReceive = null;
    }

    public function cancelPurchase(PurchaseRepository $repository)
    {
        if (!$this->purchaseToCancel) {
            return;
        }

        try {
            $purchase = $repository->find($this->purchaseToCancel);

            if ($purchase && $purchase->status !== 'cancelled') {
                $purchase->update(['status' => 'cancelled']);
                session()->flash('success', 'Achat annulé avec succès.');
            } else {
                session()->flash('error', 'Achat introuvable ou déjà annulé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->purchaseToCancel = null;
    }

    public function restorePurchase(PurchaseRepository $repository)
    {
        if (!$this->purchaseToRestore) {
            return;
        }

        try {
            $purchase = $repository->find($this->purchaseToRestore);

            if ($purchase && $purchase->status === 'cancelled') {
                $purchase->update(['status' => 'pending']);
                session()->flash('success', 'Achat réactivé avec succès. Il est maintenant en attente.');
            } else {
                session()->flash('error', 'Achat introuvable ou non annulé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->purchaseToRestore = null;
    }

    public function delete(DeletePurchaseAction $action, PurchaseRepository $repository)
    {
        if (!$this->purchaseToDelete) {
            return;
        }

        try {
            $purchase = $repository->find($this->purchaseToDelete);

            if ($purchase) {
                $action->execute($purchase->id);
                session()->flash('success', 'Achat supprimé avec succès.');
            } else {
                session()->flash('error', 'Achat introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->purchaseToDelete = null;
    }

    public function render(PurchaseRepository $repository, SupplierRepository $supplierRepository)
    {
        $query = $repository->query()
            ->with(['supplier', 'items']);

        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('purchase_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('supplier', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply supplier filter
        if ($this->supplierFilter) {
            $query->where('supplier_id', $this->supplierFilter);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date range filter
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->where('purchase_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->where('purchase_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $purchases = $query->paginate($this->perPage);

        // Get suppliers for filter dropdown
        $suppliers = $supplierRepository->all();

        // Calculate statistics
        $stats = $this->calculateStats($repository);

        return view('livewire.purchase.purchase-index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'stats' => $stats,
        ]);
    }

    private function calculateStats(PurchaseRepository $repository)
    {
        $query = $repository->query();

        // Apply date range
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->where('purchase_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->where('purchase_date', '<=', $this->dateTo);
        }

        $received = (clone $query)->where('status', 'received')->get();
        $pending = (clone $query)->where('status', 'pending')->get();

        return [
            'total_purchases' => $received->count(),
            'total_amount' => $received->sum('total'),
            'pending_purchases' => $pending->count(),
            'pending_amount' => $pending->sum('total'),
        ];
    }
}
