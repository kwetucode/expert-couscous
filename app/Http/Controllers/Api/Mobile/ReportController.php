<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller API Mobile - Rapports et Statistiques
 *
 * Fournit les données de rapports pour l'écran mobile avec KPIs, graphiques et top produits
 */
class ReportController extends Controller
{
    /**
     * Get reports data for mobile app
     *
     * GET /api/mobile/reports
     *
     * Query Parameters:
     * - period (required): week|month|year|custom
     * - start_date (required if custom): YYYY-MM-DD
     * - end_date (required if custom): YYYY-MM-DD
     * - store_id (optional): Filter by store
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'required|in:week,month,year,custom',
                'start_date' => 'required_if:period,custom|date|date_format:Y-m-d',
                'end_date' => 'required_if:period,custom|date|date_format:Y-m-d|after_or_equal:start_date',
                'store_id' => 'nullable|integer|exists:stores,id',
                'year' => 'nullable|integer|min:2020|max:2100',
                'month_number' => 'nullable|integer|min:1|max:12',
            ]);

            $storeId = $request->query('store_id') ? (int) $request->query('store_id') : null;
            
            // Utiliser effective_store_id() si pas de store_id fourni
            if (!$storeId) {
                $storeId = effective_store_id();
            }

            // 1. Déterminer les dates de la période
            [$startDate, $endDate] = $this->getPeriodDates($request);
            
            // 2. Déterminer les dates de la période précédente pour comparaison
            [$prevStartDate, $prevEndDate] = $this->getPreviousPeriodDates($startDate, $endDate);

            // 3. Calculer les KPIs pour les deux périodes
            $currentKpis = $this->calculateKpis($startDate, $endDate, $storeId);
            $previousKpis = $this->calculateKpis($prevStartDate, $prevEndDate, $storeId);

            // 4. Préparer la réponse
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'type' => $request->period,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'label' => $this->formatPeriodLabel($startDate, $endDate),
                    ],
                    'kpis' => $this->formatKpis($currentKpis, $previousKpis),
                    'chart_data' => $this->getChartData($startDate, $endDate, $storeId, $request->period),
                    'detailed_stats' => $this->getDetailedStats($startDate, $endDate, $storeId),
                    'top_products' => $this->getTopProducts($startDate, $endDate, $storeId),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rapports',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get period dates based on request
     */
    private function getPeriodDates(Request $request): array
    {
        // Si year et month_number sont fournis, utiliser ce mois spécifique
        if ($request->has('year') && $request->has('month_number')) {
            $year = (int) $request->year;
            $month = (int) $request->month_number;
            
            return [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ];
        }

        return match($request->period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ],
        };
    }

    /**
     * Get previous period dates for comparison
     */
    private function getPreviousPeriodDates(Carbon $startDate, Carbon $endDate): array
    {
        $duration = $startDate->diffInDays($endDate) + 1;
        
        return [
            $startDate->copy()->subDays($duration),
            $endDate->copy()->subDays($duration),
        ];
    }

    /**
     * Calculate KPIs for a period
     */
    private function calculateKpis(Carbon $startDate, Carbon $endDate, ?int $storeId): array
    {
        $salesQuery = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $revenue = (float) ((clone $salesQuery)->sum('total') ?? 0);
        $salesCount = (clone $salesQuery)->count();
        $averageBasket = $salesCount > 0 ? $revenue / $salesCount : 0;

        // Calcul de la marge brute
        $grossMargin = $this->calculateGrossMargin($startDate, $endDate, $storeId, $revenue);

        return [
            'revenue' => $revenue,
            'sales_count' => $salesCount,
            'average_basket' => $averageBasket,
            'gross_margin' => $grossMargin,
        ];
    }

    /**
     * Calculate gross margin percentage
     */
    private function calculateGrossMargin(Carbon $startDate, Carbon $endDate, ?int $storeId, float $revenue): float
    {
        if ($revenue <= 0) {
            return 0.0;
        }

        // Calculer le coût total des produits vendus
        $totalCost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId))
            ->selectRaw('SUM(sale_items.quantity * COALESCE(products.cost_price, 0)) as total_cost')
            ->value('total_cost') ?? 0;

        $totalCost = (float) $totalCost;

        // Marge brute = ((CA - Coûts) / CA) × 100
        $grossMargin = (($revenue - $totalCost) / $revenue) * 100;

        return round($grossMargin, 1);
    }

    /**
     * Format KPIs with change percentage
     */
    private function formatKpis(array $current, array $previous): array
    {
        return [
            'revenue' => [
                'value' => round($current['revenue'], 2),
                'formatted' => $this->formatCurrency($current['revenue']),
                'change' => $this->calculateChange($current['revenue'], $previous['revenue']),
            ],
            'sales_count' => [
                'value' => $current['sales_count'],
                'formatted' => number_format($current['sales_count']),
                'change' => $this->calculateChange($current['sales_count'], $previous['sales_count']),
            ],
            'average_basket' => [
                'value' => round($current['average_basket'], 2),
                'formatted' => $this->formatCurrency($current['average_basket']),
                'change' => $this->calculateChange($current['average_basket'], $previous['average_basket']),
            ],
            'gross_margin' => [
                'value' => round($current['gross_margin'], 1),
                'formatted' => round($current['gross_margin'], 1) . '%',
                'change' => $this->calculateChange($current['gross_margin'], $previous['gross_margin']),
            ],
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculateChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get chart data
     */
    private function getChartData(Carbon $startDate, Carbon $endDate, ?int $storeId, string $period): array
    {
        // Récupérer les données de vente groupées par date
        $data = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('DATE(sale_date) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];
        
        // Générer les labels et valeurs pour chaque jour
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $this->formatChartLabel($current, $period);
            $values[] = (float) ($data[$dateKey]->total ?? 0);
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Format chart label based on period
     */
    private function formatChartLabel(Carbon $date, string $period): string
    {
        return match($period) {
            'week' => $date->locale('fr')->isoFormat('ddd'),  // Lun, Mar, Mer, ...
            'month' => $date->format('d'),                     // 01, 02, 03, ...
            'year' => $date->locale('fr')->isoFormat('MMM'),   // Jan, Fév, Mar, ...
            default => $date->format('d/m'),                   // 01/01, 02/01, ...
        };
    }

    /**
     * Get detailed statistics
     */
    private function getDetailedStats(Carbon $startDate, Carbon $endDate, ?int $storeId): array
    {
        // Nombre de transactions
        $transactionsCount = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->count();

        // Nouveaux clients créés pendant la période
        $newCustomers = DB::table('clients')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($storeId, function($q) use ($storeId) {
                // Si les clients ont un store_id, sinon ignorer ce filtre
                if (DB::getSchemaBuilder()->hasColumn('clients', 'store_id')) {
                    $q->where('store_id', $storeId);
                }
            })
            ->count();

        // Nombre total de produits vendus (quantité)
        $productsSold = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId))
            ->sum('sale_items.quantity');

        // Nombre de retours (ventes avec statut "returned" ou "cancelled")
        $returnsCount = DB::table('sales')
            ->whereIn('status', ['returned', 'cancelled'])
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->count();

        return [
            'transactions_count' => $transactionsCount,
            'new_customers' => $newCustomers,
            'products_sold' => (int) $productsSold,
            'returns_count' => $returnsCount,
        ];
    }

    /**
     * Get top 5 products
     */
    private function getTopProducts(Carbon $startDate, Carbon $endDate, ?int $storeId, int $limit = 5): array
    {
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId))
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.subtotal) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity_sold' => (int) $item->quantity_sold,
                'revenue' => round((float) $item->revenue, 2),
            ])
            ->toArray();

        return $topProducts;
    }

    /**
     * Format period label
     */
    private function formatPeriodLabel(Carbon $startDate, Carbon $endDate): string
    {
        // Si même jour
        if ($startDate->isSameDay($endDate)) {
            return $startDate->locale('fr')->isoFormat('D MMM YYYY');
        }
        
        // Si même mois
        if ($startDate->isSameMonth($endDate)) {
            return $startDate->format('d') . ' - ' . $endDate->locale('fr')->isoFormat('D MMM YYYY');
        }
        
        // Mois différents
        return $startDate->locale('fr')->isoFormat('D MMM') . ' - ' . $endDate->locale('fr')->isoFormat('D MMM YYYY');
    }

    /**
     * Format currency (using organization currency)
     */
    private function formatCurrency(float $amount): string
    {
        $currency = current_currency();
        
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1, ',', ' ') . 'M ' . $currency;
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 1, ',', ' ') . 'K ' . $currency;
        }
        return number_format($amount, 0, ',', ' ') . ' ' . $currency;
    }
}
