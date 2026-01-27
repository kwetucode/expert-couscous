<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\DeleteSaleAction;
use App\Repositories\SaleRepository;
use App\Repositories\ClientRepository;
use App\Repositories\UserRepository;
use App\Services\SaleService;
use App\Services\SaleExcelExporter;
use App\Mail\SalesReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class SaleIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $clientFilter = '';
    public $statusFilter = '';
    public $paymentStatusFilter = '';
    public $periodFilter = 'today';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $sortField = 'sale_date';
    public $sortDirection = 'desc';

    public $saleToDelete = null;
    public $saleToComplete = null;
    public $saleToRestore = null;
    public $saleToForceDelete = null;

    // Email modal properties
    public $selectedUserId = null;
    public $selectedUserId2 = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'clientFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'paymentStatusFilter' => ['except' => ''],
        'periodFilter' => ['except' => 'today'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'sale_date'],
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

    public function updatingClientFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        // Switch to custom mode when user manually changes date
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
        // Switch to custom mode when user manually changes date
        if ($this->periodFilter !== 'custom') {
            $this->periodFilter = 'custom';
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

    public function completeSale(SaleService $service, SaleRepository $repository)
    {
        if (!$this->saleToComplete) {
            return;
        }

        try {
            $sale = $repository->find($this->saleToComplete);

            if ($sale) {
                $service->completeSale($sale->id);
                session()->flash('success', 'Vente complétée avec succès.');
            } else {
                session()->flash('error', 'Vente introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->saleToComplete = null;
    }

    public function delete(DeleteSaleAction $action, SaleRepository $repository)
    {
        if (!$this->saleToDelete) {
            return;
        }

        try {
            $sale = $repository->find($this->saleToDelete);

            if ($sale) {
                $action->execute($sale->id, 'Supprimé depuis la liste');
                session()->flash('success', 'Vente annulée avec succès.');
            } else {
                session()->flash('error', 'Vente introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->saleToDelete = null;
    }

    public function restoreSale(SaleRepository $repository)
    {
        if (!$this->saleToRestore) {
            return;
        }

        try {
            $sale = $repository->find($this->saleToRestore);

            if ($sale && $sale->status === 'cancelled') {
                $sale->update(['status' => 'pending']);
                session()->flash('success', 'Vente réactivée avec succès. Elle est maintenant en attente.');
            } else {
                session()->flash('error', 'Vente introuvable ou non annulée.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->saleToRestore = null;
    }

    public function forceDelete(SaleRepository $repository)
    {
        if (!$this->saleToForceDelete) {
            return;
        }

        try {
            $sale = $repository->find($this->saleToForceDelete);

            if ($sale) {
                // Supprimer les items liés
                $sale->items()->delete();
                // Supprimer les paiements liés
                $sale->payments()->delete();
                // Supprimer la vente
                $sale->delete();
                session()->flash('success', 'Vente supprimée définitivement du système.');
            } else {
                session()->flash('error', 'Vente introuvable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->saleToForceDelete = null;
    }

    public function exportExcel(SaleRepository $repository, SaleExcelExporter $exporter)
    {
        try {
            $query = $repository->query()
                ->with(['client', 'user', 'items']);

            // Apply search
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('sale_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('client', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            }

            // Apply client filter
            if ($this->clientFilter) {
                $query->where('client_id', $this->clientFilter);
            }

            // Apply status filter
            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            // Apply payment status filter
            if ($this->paymentStatusFilter) {
                $query->where('payment_status', $this->paymentStatusFilter);
            }

            // Apply date range filter
            if ($this->dateFrom && $this->dateTo) {
                $query->whereDate('sale_date', '>=', $this->dateFrom)
                      ->whereDate('sale_date', '<=', $this->dateTo);
            } elseif ($this->dateFrom) {
                $query->whereDate('sale_date', '>=', $this->dateFrom);
            } elseif ($this->dateTo) {
                $query->whereDate('sale_date', '<=', $this->dateTo);
            }

            // Apply sorting
            $query->orderBy($this->sortField, $this->sortDirection);

            $sales = $query->get();

            // Get period label for export
            $periodLabel = $this->getPeriodLabel();

            return $exporter->export($sales, $this->dateFrom, $this->dateTo, $periodLabel);
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
            'client_id' => $this->clientFilter,
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentStatusFilter,
        ];

        // Remove empty values
        $params = array_filter($params, fn($value) => $value !== '' && $value !== null);

        return redirect()->route('reports.sales', $params);
    }

    public function render(SaleRepository $repository, ClientRepository $clientRepository)
    {
        $query = $repository->query()
            ->with(['client', 'user', 'items']);

        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('sale_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply client filter
        if ($this->clientFilter) {
            $query->where('client_id', $this->clientFilter);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply payment status filter
        if ($this->paymentStatusFilter) {
            $query->where('payment_status', $this->paymentStatusFilter);
        }

        // Apply date range filter
        if ($this->dateFrom && $this->dateTo) {
            $query->whereDate('sale_date', '>=', $this->dateFrom)
                  ->whereDate('sale_date', '<=', $this->dateTo);
        } elseif ($this->dateFrom) {
            $query->whereDate('sale_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('sale_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $sales = $query->paginate($this->perPage);

        // Get clients for filter dropdown
        $clients = $clientRepository->all();

        // Calculate statistics
        $stats = $this->calculateStats($repository);

        return view('livewire.sale.sale-index', [
            'sales' => $sales,
            'clients' => $clients,
            'stats' => $stats,
        ]);
    }

    private function calculateStats(SaleRepository $repository)
    {
        $query = $repository->query();

        // Apply date range
        if ($this->dateFrom && $this->dateTo) {
            $query->whereDate('sale_date', '>=', $this->dateFrom)
                  ->whereDate('sale_date', '<=', $this->dateTo);
        } elseif ($this->dateFrom) {
            $query->whereDate('sale_date', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('sale_date', '<=', $this->dateTo);
        }

        $completed = (clone $query)->where('status', 'completed')->get();
        $pending = (clone $query)->where('status', 'pending')->get();

        return [
            'total_sales' => $completed->count(),
            'total_amount' => $completed->sum('total'),
            'pending_sales' => $pending->count(),
            'pending_amount' => $pending->sum('total'),
        ];
    }

    /**
     * Open email modal
     */
    public function openEmailModal()
    {
        $this->selectedUserId = null;
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
     * Get users for email modal dropdown
     */
    public function getUsersProperty()
    {
        $userRepository = app(UserRepository::class);
        return $userRepository->getAllWithFilters(perPage: 100)->items();
    }

    /**
     * Send sales report via email
     */
    public function sendReportEmail(SaleRepository $repository, SaleExcelExporter $exporter, UserRepository $userRepository)
    {
        Log::info('sendReportEmail called', ['selectedUserId' => $this->selectedUserId, 'selectedUserId2' => $this->selectedUserId2]);

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

            Log::info('Users found', ['count' => $users->count(), 'emails' => $users->pluck('email')]);

            if ($users->isEmpty()) {
                session()->flash('error', 'Aucun utilisateur valide sélectionné.');
                return;
            }

            // Build query with current filters
            $query = $repository->query()->with(['client', 'user', 'items']);

            // Apply search
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('sale_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('client', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            }

            // Apply client filter
            if ($this->clientFilter) {
                $query->where('client_id', $this->clientFilter);
            }

            // Apply status filter
            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            // Apply payment status filter
            if ($this->paymentStatusFilter) {
                $query->where('payment_status', $this->paymentStatusFilter);
            }

            // Apply date range filter
            if ($this->dateFrom && $this->dateTo) {
                $query->whereDate('sale_date', '>=', $this->dateFrom)
                      ->whereDate('sale_date', '<=', $this->dateTo);
            } elseif ($this->dateFrom) {
                $query->whereDate('sale_date', '>=', $this->dateFrom);
            } elseif ($this->dateTo) {
                $query->whereDate('sale_date', '<=', $this->dateTo);
            }

            // Apply sorting
            $query->orderBy($this->sortField, $this->sortDirection);

            $sales = $query->get();
            $periodLabel = $this->getPeriodLabel();

            // Calculate stats for email template
            $stats = $this->calculateStats($repository);

            // Calculate totals for PDF template (different keys expected)
            $pdfTotals = [
                'completed_count' => $sales->where('status', 'completed')->count(),
                'completed_amount' => $sales->where('status', 'completed')->sum('total'),
                'pending_count' => $sales->where('status', 'pending')->count(),
                'paid_amount' => $sales->sum('paid_amount'),
            ];

            // Create temp directory if not exists
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate temp PDF file
            $pdf = Pdf::loadView('reports.sales', [
                'title' => 'Rapport des Ventes',
                'date' => now()->format('d/m/Y H:i'),
                'sales' => $sales,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'periodLabel' => $periodLabel,
                'totals' => $pdfTotals,
            ]);
            $pdfPath = $tempDir . '/rapport_ventes_' . time() . '_' . uniqid() . '.pdf';
            $pdf->save($pdfPath);

            // Generate temp Excel file
            $excelPath = $tempDir . '/rapport_ventes_' . time() . '_' . uniqid() . '.xlsx';
            $exporter->exportToFile($sales, $this->dateFrom, $this->dateTo, $periodLabel, $excelPath);

            // Send email to each selected user
            $sentEmails = [];
            foreach ($users as $user) {
                Log::info('Sending sales report email to: ' . $user->email);

                $mailable = new SalesReportMail(
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

                Log::info('Sales report email sent successfully to: ' . $user->email);
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

            Log::error('Error sending sales report email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }
}
