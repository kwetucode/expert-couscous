<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller API Mobile - Rapports de Stock
 *
 * Fournit les endpoints pour les alertes et statistiques de stock
 */
class MobileStockReportController extends Controller
{
    public function __construct(
        private MobileReportService $reportService
    ) {}

    /**
     * Toutes les alertes de stock
     *
     * GET /api/mobile/stock/alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $this->reportService->getStockAlerts($user);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Résumé du stock (cohérent avec Livewire StockOverview)
     *
     * GET /api/mobile/stock/summary
     * 
     * Retourne les mêmes KPIs que la vue Livewire État du Stock :
     * - total_products : nombre total de variantes
     * - in_stock_count : variantes avec stock > 0
     * - out_of_stock_count : variantes en rupture (stock <= 0)
     * - low_stock_count : variantes en stock faible
     * - total_stock_value : valeur totale du stock (coût)
     * - total_retail_value : valeur de vente potentielle
     * - total_units : nombre total d'unités en stock
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Utiliser StockOverviewService pour cohérence avec Livewire
            $stockOverviewService = app(\App\Services\StockOverviewService::class);
            $kpis = $stockOverviewService->calculateKPIs();

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'total_products' => $kpis['total_products'],
                        'in_stock_count' => $kpis['in_stock_count'],
                        'out_of_stock_count' => $kpis['out_of_stock_count'],
                        'low_stock_count' => $kpis['low_stock_count'],
                        'total_units' => $kpis['total_units'],
                    ],
                    'value' => [
                        'total_stock_value' => $kpis['total_stock_value'],
                        'total_stock_value_formatted' => number_format($kpis['total_stock_value'], 2, ',', ' '),
                        'total_retail_value' => $kpis['total_retail_value'],
                        'total_retail_value_formatted' => number_format($kpis['total_retail_value'], 2, ',', ' '),
                        'potential_profit' => $kpis['potential_profit'],
                        'potential_profit_formatted' => number_format($kpis['potential_profit'], 2, ',', ' '),
                        'profit_margin_percentage' => $kpis['profit_margin_percentage'],
                    ],
                    'alerts' => [
                        'total' => $kpis['out_of_stock_count'] + $kpis['low_stock_count'],
                        'out_of_stock' => $kpis['out_of_stock_count'],
                        'low_stock' => $kpis['low_stock_count'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Produits en stock bas
     *
     * GET /api/mobile/stock/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit') ? (int) $request->input('limit') : null;

            // Limiter la valeur si fournie
            if ($limit) {
                $limit = min(max($limit, 10), 100);
            }

            $data = $this->reportService->getLowStockProducts($user, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $data,
                    'count' => count($data),
                    'status' => 'low_stock',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits en stock bas',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Produits en rupture de stock
     *
     * GET /api/mobile/stock/out-of-stock
     */
    public function outOfStock(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit') ? (int) $request->input('limit') : null;

            // Limiter la valeur si fournie
            if ($limit) {
                $limit = min(max($limit, 10), 100);
            }

            $data = $this->reportService->getOutOfStockProducts($user, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $data,
                    'count' => count($data),
                    'status' => 'out_of_stock',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits en rupture',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Valeur totale du stock (admin/manager uniquement)
     *
     * GET /api/mobile/stock/value
     */
    public function stockValue(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Seuls admin/manager peuvent voir la valeur du stock
            if (!user_can_access_all_stores() && !$user->hasPermission('reports.stock')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }

            $summary = $this->reportService->getStockSummary($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'value' => $summary['value'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la valeur du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Stock par magasin (admin/manager uniquement)
     *
     * GET /api/mobile/stock/by-store
     */
    public function byStore(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!user_can_access_all_stores()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Réservé aux administrateurs et managers',
                ], 403);
            }

            $data = $this->reportService->getStockByStore($user);

            // Calculer les totaux
            $totalAlerts = collect($data)->sum('total_alerts');
            $totalValue = collect($data)->sum('stock_value');

            return response()->json([
                'success' => true,
                'data' => [
                    'stores' => $data,
                    'totals' => [
                        'alerts' => $totalAlerts,
                        'value' => $totalValue,
                        'value_formatted' => number_format($totalValue, 2, ',', ' '),
                    ],
                    'chart' => [
                        'type' => 'bar',
                        'labels' => collect($data)->pluck('name')->toArray(),
                        'datasets' => [
                            [
                                'label' => 'Stock bas',
                                'data' => collect($data)->pluck('low_stock_count')->toArray(),
                                'color' => '#F59E0B',
                            ],
                            [
                                'label' => 'Rupture',
                                'data' => collect($data)->pluck('out_of_stock_count')->toArray(),
                                'color' => '#EF4444',
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du stock par magasin',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Statistiques combinées des alertes (pour widget mobile)
     *
     * GET /api/mobile/stock/widget
     */
    public function widget(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $summary = $this->reportService->getStockSummary($user);
            $alerts = $this->reportService->getStockAlerts($user);

            // Récupérer les 5 alertes les plus critiques
            $criticalAlerts = array_merge(
                array_slice($alerts['out_of_stock']['variants']->toArray(), 0, 3),
                array_slice($alerts['low_stock']['variants']->toArray(), 0, 2)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_alerts' => $summary['alerts']['total'],
                        'critical' => $summary['alerts']['out_of_stock'],
                        'warning' => $summary['alerts']['low_stock'],
                    ],
                    'critical_alerts' => $criticalAlerts,
                    'has_alerts' => $summary['alerts']['total'] > 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du widget',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
