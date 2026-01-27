<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion des statistiques POS
 */
class StatsService
{
    /**
     * Charge les statistiques du jour pour un utilisateur
     */
    public function loadTodayStats(int $userId): array
    {
        $storeId = current_store_id();
        $cacheKey = "pos.stats.today.user.{$userId}.store.{$storeId}." . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () use ($userId, $storeId) {
            $query = Sale::with('items')
                ->whereDate('created_at', today())
                ->where('user_id', $userId)
                ->where('status', 'completed');

            // Filter by current store if user is not admin
            if (!user_can_access_all_stores() && $storeId) {
                $query->where('store_id', $storeId);
            }

            $sales = $query->get();

            return $this->calculateStats($sales);
        });
    }

    /**
     * Charge l'historique des transactions du jour
     */
    public function loadTransactionHistory(int $userId, int $limit = 10, int $offset = 0): array
    {
        $query = Sale::with(['client', 'invoice'])
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->where('status', 'completed');

        // Filter by current store if user is not admin
        if (!user_can_access_all_stores() && current_store_id()) {
            $query->where('store_id', current_store_id());
        }

        return $query->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn($sale) => $this->formatTransaction($sale))
            ->toArray();
    }

    /**
     * Compte le nombre total de transactions du jour
     */
    public function countTodayTransactions(int $userId): int
    {
        $query = Sale::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->where('status', 'completed');

        if (!user_can_access_all_stores() && current_store_id()) {
            $query->where('store_id', current_store_id());
        }

        return $query->count();
    }

    /**
     * Invalide le cache des statistiques
     */
    public function invalidateStatsCache(int $userId): void
    {
        $storeId = current_store_id();
        $cacheKey = "pos.stats.today.user.{$userId}.store.{$storeId}." . now()->format('Y-m-d');
        Cache::forget($cacheKey);
    }

    /**
     * Calcule les statistiques à partir des ventes
     */
    private function calculateStats(Collection $sales): array
    {
        $count = $sales->count();

        return [
            'sales_count' => $count,
            'revenue' => $sales->sum('total'),
            'transactions' => $count,
            'average_sale' => $count > 0 ? $sales->average('total') : 0,
            'total_items' => $sales->sum(fn($sale) => $sale->items->sum('quantity')),
            'cash_sales' => $sales->where('payment_method', 'cash')->count(),
            'card_sales' => $sales->where('payment_method', 'card')->count(),
        ];
    }

    /**
     * Formate une transaction pour l'affichage
     */
    private function formatTransaction(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice?->invoice_number ?? 'N/A',
            'client' => $sale->client?->name ?? 'Comptant',
            'total' => $sale->total,
            'time' => $sale->created_at->format('H:i'),
            'payment_method' => $sale->payment_method,
        ];
    }
}
