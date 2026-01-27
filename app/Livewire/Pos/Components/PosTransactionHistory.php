<?php

declare(strict_types=1);

namespace App\Livewire\Pos\Components;

use App\Models\Sale;
use App\Services\Pos\StatsService;
use App\Services\Pos\PrinterService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * Composant Historique des Transactions POS
 * Affiche un modal avec les factures du jour
 */
class PosTransactionHistory extends Component
{
    public array $transactions = [];
    public bool $showModal = false;
    public bool $showDetailModal = false;
    public ?array $selectedTransaction = null;
    public int $perPage = 15;
    public int $totalCount = 0;
    public bool $hasMore = false;
    public bool $loadingMore = false;

    private StatsService $statsService;
    private PrinterService $printerService;

    public function boot(StatsService $statsService, PrinterService $printerService): void
    {
        $this->statsService = $statsService;
        $this->printerService = $printerService;
    }

    public function mount(): void
    {
        $this->totalCount = $this->statsService->countTodayTransactions($this->getUserId());
        $this->loadTransactions();
    }

    /**
     * Obtient l'ID de l'utilisateur authentifié
     */
    private function getUserId(): int
    {
        $userId = auth()->id();
        if (!$userId) {
            throw new \RuntimeException('Utilisateur non authentifié');
        }
        return (int) $userId;
    }

    /**
     * Charge l'historique des transactions (reset)
     */
    public function loadTransactions(): void
    {
        $this->transactions = $this->statsService->loadTransactionHistory($this->getUserId(), $this->perPage, 0);
        $this->totalCount = $this->statsService->countTodayTransactions($this->getUserId());
        $this->hasMore = count($this->transactions) < $this->totalCount;
    }

    /**
     * Charge plus de transactions (infinite scroll)
     */
    public function loadMore(): void
    {
        if (!$this->hasMore || $this->loadingMore) {
            return;
        }

        $this->loadingMore = true;

        $offset = count($this->transactions);
        $moreTransactions = $this->statsService->loadTransactionHistory($this->getUserId(), $this->perPage, $offset);

        $this->transactions = array_merge($this->transactions, $moreTransactions);
        $this->hasMore = count($this->transactions) < $this->totalCount;
        $this->loadingMore = false;
    }

    /**
     * Ouvre le modal
     */
    public function openModal(): void
    {
        $this->loadTransactions();
        $this->showModal = true;
    }

    /**
     * Ferme le modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Réimprime une transaction
     */
    public function reprintTransaction(int $saleId): void
    {
        $sale = Sale::with(['client', 'user', 'invoice', 'items.productVariant.product'])->find($saleId);

        if (!$sale || !$sale->invoice) {
            $this->dispatch('show-toast', message: 'Vente introuvable', type: 'error');
            return;
        }

        $change = $sale->paid_amount - $sale->total;
        $receiptData = $this->printerService->prepareReceiptData($sale, $sale->invoice, max(0, $change));

        $this->dispatch('print-thermal-receipt', $receiptData);
        $this->showModal = false;
    }

    /**
     * Voir les détails d'une transaction (sans impression)
     */
    public function viewTransaction(int $saleId): void
    {
        $sale = Sale::with(['client', 'user', 'invoice', 'items.productVariant.product'])->find($saleId);

        if (!$sale) {
            $this->dispatch('show-toast', message: 'Vente introuvable', type: 'error');
            return;
        }

        $this->selectedTransaction = [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'invoice_number' => $sale->invoice?->invoice_number ?? 'N/A',
            'date' => $sale->created_at->format('d/m/Y'),
            'time' => $sale->created_at->format('H:i:s'),
            'client' => $sale->client?->name ?? 'Client Comptant',
            'cashier' => $sale->user?->name ?? 'N/A',
            'payment_method' => $this->formatPaymentMethod($sale->payment_method),
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount ?? 0,
            'tax' => $sale->tax ?? 0,
            'total' => $sale->total,
            'paid_amount' => $sale->paid_amount,
            'change' => max(0, $sale->paid_amount - $sale->total),
            'items' => $sale->items->map(fn($item) => [
                'name' => $item->productVariant?->product?->name ?? 'Produit',
                'variant' => $this->formatVariant($item->productVariant),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->toArray(),
        ];

        // Ouvrir le modal de détails (garde la liste ouverte en arrière-plan)
        $this->showDetailModal = true;
        $this->dispatch('open-detail-modal');
    }

    /**
     * Ferme le modal de détails
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedTransaction = null;
    }

    /**
     * Imprimer depuis le modal de détails
     */
    public function printFromDetail(): void
    {
        if ($this->selectedTransaction) {
            $this->reprintTransaction($this->selectedTransaction['id']);
            $this->closeDetailModal();
        }
    }

    /**
     * Retourner à la liste depuis les détails
     */
    public function backToList(): void
    {
        $this->showDetailModal = false;
        $this->selectedTransaction = null;
        $this->dispatch('close-detail-modal');
        // La liste reste ouverte en arrière-plan
    }

    /**
     * Formate la méthode de paiement
     */
    private function formatPaymentMethod(string $method): string
    {
        return match($method) {
            'cash' => 'Espèces',
            'card' => 'Carte bancaire',
            'mobile' => 'Paiement mobile',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            default => ucfirst($method),
        };
    }

    /**
     * Formate la variante
     */
    private function formatVariant($variant): string
    {
        if (!$variant) return '';
        $parts = [];
        if ($variant->size) $parts[] = $variant->size;
        if ($variant->color) $parts[] = $variant->color;
        return implode(' - ', $parts);
    }

    /**
     * Écoute le rafraîchissement après paiement
     */
    #[On('stats-refresh')]
    #[On('sale-completed')]
    public function refreshTransactions(): void
    {
        $this->loadTransactions();
    }

    /**
     * Écoute l'ouverture du modal depuis l'extérieur
     */
    #[On('open-transaction-history')]
    public function onOpenModal(): void
    {
        $this->openModal();
    }

    public function render()
    {
        return view('livewire.pos.components.pos-transaction-history');
    }
}
