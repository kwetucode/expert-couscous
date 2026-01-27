<?php

namespace App\Http\Controllers;

use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\StockMovementRepository;
use App\Repositories\SaleRepository;
use App\Repositories\ClientRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductVariantRepository $productVariantRepository,
        protected StockMovementRepository $stockMovementRepository,
        protected CategoryRepository $categoryRepository,
        protected SaleRepository $saleRepository,
        protected ClientRepository $clientRepository
    ) {}

    /**
     * Rapport de tous les produits
     */
    public function products(Request $request)
    {
        $products = $this->productRepository->forReport(
            $request->filled('category_id') ? (int) $request->category_id : null,
            $request->filled('status') ? $request->status : null
        );

        $categoryName = $request->category_id
            ? $this->categoryRepository->find($request->category_id)?->name
            : 'Toutes';

        $data = [
            'title' => 'Liste des Produits',
            'date' => now()->format('d/m/Y H:i'),
            'products' => $products,
            'filters' => [
                'category' => $categoryName,
                'status' => $request->status ?? 'Tous',
            ],
            'totals' => [
                'products' => $products->count(),
                'variants' => $products->sum(fn($p) => $p->variants->count()),
                'total_stock' => $products->sum(fn($p) => $p->variants->sum('stock_quantity')),
                'total_value' => $products->sum(fn($p) => $p->variants->sum('stock_quantity') * ($p->cost_price ?? 0)),
            ],
        ];

        $pdf = Pdf::loadView('reports.products', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('produits_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Rapport de l'état du stock
     */
    public function stock(Request $request)
    {
        $variants = $this->productVariantRepository->forStockReport(
            $request->filled('stock_level') ? $request->stock_level : null,
            $request->filled('category_id') ? (int) $request->category_id : null
        );

        $categoryName = $request->category_id
            ? $this->categoryRepository->find($request->category_id)?->name
            : 'Toutes';

        $data = [
            'title' => 'État du Stock',
            'date' => now()->format('d/m/Y H:i'),
            'variants' => $variants,
            'filters' => [
                'stock_level' => match($request->stock_level) {
                    'out' => 'Rupture de stock',
                    'low' => 'Stock faible',
                    'normal' => 'Stock normal',
                    default => 'Tous'
                },
                'category' => $categoryName,
            ],
            'totals' => [
                'variants' => $variants->count(),
                'total_stock' => $variants->sum('stock_quantity'),
                'total_value' => $variants->sum(fn($v) => $v->stock_quantity * ($v->product->cost_price ?? 0)),
                'out_of_stock' => $variants->where('stock_quantity', '<=', 0)->count(),
                'low_stock' => $variants->filter(fn($v) => $v->stock_quantity > 0 && $v->stock_quantity <= $v->low_stock_threshold)->count(),
            ],
        ];

        $pdf = Pdf::loadView('reports.stock', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('etat_stock_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Rapport des mouvements de stock
     */
    public function stockMovements(Request $request)
    {
        $movements = $this->stockMovementRepository->forReport(
            $request->filled('date_from') ? $request->date_from : null,
            $request->filled('date_to') ? $request->date_to : null,
            $request->filled('type') ? $request->type : null,
            $request->filled('movement_type') ? $request->movement_type : null,
            $request->filled('product_variant_id') ? (int) $request->product_variant_id : null
        );

        $data = [
            'title' => 'Mouvements de Stock',
            'date' => now()->format('d/m/Y H:i'),
            'movements' => $movements,
            'filters' => [
                'date_from' => $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/Y') : 'N/A',
                'date_to' => $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/Y') : 'N/A',
                'type' => match($request->type) {
                    'in' => 'Entrées',
                    'out' => 'Sorties',
                    default => 'Tous'
                },
                'movement_type' => $request->movement_type ?? 'Tous',
            ],
            'totals' => [
                'movements' => $movements->count(),
                'entries' => $movements->where('type', 'in')->sum('quantity'),
                'exits' => $movements->where('type', 'out')->sum('quantity'),
                'total_value' => $movements->sum(fn($m) => $m->quantity * ($m->unit_price ?? 0)),
            ],
        ];

        $pdf = Pdf::loadView('reports.stock-movements', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('mouvements_stock_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Rapport d'inventaire valorisé
     */
    public function inventory(Request $request)
    {
        $variants = $this->productVariantRepository->forInventoryReport();

        // Grouper par catégorie
        $byCategory = $variants->groupBy(fn($v) => $v->product->category?->name ?? 'Sans catégorie');

        $data = [
            'title' => 'Inventaire Valorisé',
            'date' => now()->format('d/m/Y H:i'),
            'byCategory' => $byCategory,
            'totals' => [
                'variants' => $variants->count(),
                'total_stock' => $variants->sum('stock_quantity'),
                'total_cost_value' => $variants->sum(fn($v) => $v->stock_quantity * ($v->product->cost_price ?? 0)),
                'total_sale_value' => $variants->sum(fn($v) => $v->stock_quantity * ($v->product->price ?? 0)),
            ],
        ];

        $pdf = Pdf::loadView('reports.inventory', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('inventaire_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Rapport des alertes de stock
     */
    public function stockAlerts(Request $request)
    {
        $outOfStock = $this->productVariantRepository->outOfStockWithDetails();
        $lowStock = $this->productVariantRepository->lowStockWithDetails();

        $data = [
            'title' => 'Alertes de Stock',
            'date' => now()->format('d/m/Y H:i'),
            'outOfStock' => $outOfStock,
            'lowStock' => $lowStock,
            'totals' => [
                'out_of_stock_count' => $outOfStock->count(),
                'low_stock_count' => $lowStock->count(),
                'total_alerts' => $outOfStock->count() + $lowStock->count(),
            ],
        ];

        $pdf = Pdf::loadView('reports.stock-alerts', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('alertes_stock_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Get period label for display
     */
    private function getPeriodLabel(?string $period): string
    {
        return match($period) {
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

    /**
     * Rapport des ventes
     */
    public function sales(Request $request)
    {
        $query = $this->saleRepository->query()
            ->with(['client', 'user', 'items']);

        // Apply filters
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Get period filter
        $period = $request->input('period', 'this_month');
        $periodLabel = $this->getPeriodLabel($period);

        // Apply date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Build period display text
        if ($period === 'all') {
            $periodText = 'Toutes les dates';
        } elseif ($dateFrom && $dateTo) {
            $periodText = $periodLabel . ' (' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y') . ')';
            $query->whereDate('sale_date', '>=', $dateFrom)
                  ->whereDate('sale_date', '<=', $dateTo);
        } elseif ($dateFrom) {
            $periodText = 'À partir du ' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y');
            $query->whereDate('sale_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $periodText = 'Jusqu\'au ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y');
            $query->whereDate('sale_date', '<=', $dateTo);
        } else {
            $periodText = 'Toutes les dates';
        }

        $query->orderBy('sale_date', 'desc');

        $sales = $query->get();

        // Get filter labels
        $clientName = $request->filled('client_id')
            ? $this->clientRepository->find($request->client_id)?->name
            : 'Tous';

        $statusLabels = [
            'pending' => 'En attente',
            'completed' => 'Complétée',
            'cancelled' => 'Annulée',
        ];

        $paymentLabels = [
            'pending' => 'En attente',
            'paid' => 'Payé',
            'partial' => 'Partiel',
            'refunded' => 'Remboursé',
        ];

        $data = [
            'title' => 'Rapport des Ventes',
            'date' => now()->format('d/m/Y H:i'),
            'sales' => $sales,
            'showDetails' => $request->boolean('details', false),
            'filters' => [
                'period' => $periodText,
                'client' => $clientName ?? 'Tous',
                'status' => $request->filled('status') ? ($statusLabels[$request->status] ?? $request->status) : 'Tous',
                'payment_status' => $request->filled('payment_status') ? ($paymentLabels[$request->payment_status] ?? $request->payment_status) : 'Tous',
            ],
            'totals' => [
                'completed_count' => $sales->where('status', 'completed')->count(),
                'completed_amount' => $sales->where('status', 'completed')->sum('total'),
                'pending_count' => $sales->where('status', 'pending')->count(),
                'pending_amount' => $sales->where('status', 'pending')->sum('total'),
                'paid_amount' => $sales->sum('paid_amount'),
            ],
        ];

        $pdf = Pdf::loadView('reports.sales', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('ventes_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Rapport des factures
     */
    public function invoices(Request $request)
    {
        $invoiceRepository = app(\App\Repositories\InvoiceRepository::class);

        $query = $invoiceRepository->query()
            ->with(['sale.client', 'sale.items']);

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get period filter
        $period = $request->input('period', 'today');
        $periodLabel = $this->getPeriodLabel($period);

        // Apply date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Build period display text
        if ($period === 'all') {
            $periodText = 'Toutes les dates';
        } elseif ($dateFrom && $dateTo) {
            $periodText = $periodLabel . ' (' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y') . ')';
            $query->whereDate('invoice_date', '>=', $dateFrom)
                  ->whereDate('invoice_date', '<=', $dateTo);
        } elseif ($dateFrom) {
            $periodText = 'À partir du ' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y');
            $query->whereDate('invoice_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $periodText = 'Jusqu\'au ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y');
            $query->whereDate('invoice_date', '<=', $dateTo);
        } else {
            $periodText = 'Toutes les dates';
        }

        $query->orderBy('invoice_date', 'desc');

        $invoices = $query->get();

        $statusLabels = [
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            'overdue' => 'En retard',
        ];

        $data = [
            'title' => 'Rapport des Factures',
            'date' => now()->format('d/m/Y H:i'),
            'invoices' => $invoices,
            'filters' => [
                'period' => $periodText,
                'status' => $request->filled('status') ? ($statusLabels[$request->status] ?? $request->status) : 'Tous',
            ],
            'totals' => [
                'total_invoices' => $invoices->count(),
                'paid_invoices' => $invoices->where('status', 'paid')->count(),
                'unpaid_invoices' => $invoices->whereIn('status', ['draft', 'sent'])->count(),
                'cancelled_invoices' => $invoices->where('status', 'cancelled')->count(),
                'total_amount' => $invoices->sum(fn($i) => $i->sale ? $i->sale->total : 0),
                'paid_amount' => $invoices->where('status', 'paid')->sum(fn($i) => $i->sale ? $i->sale->total : 0),
                'unpaid_amount' => $invoices->whereIn('status', ['draft', 'sent'])->sum(fn($i) => $i->sale ? $i->sale->total : 0),
            ],
        ];

        $pdf = Pdf::loadView('reports.invoices', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('factures_' . now()->format('Y-m-d_His') . '.pdf');
    }
}
