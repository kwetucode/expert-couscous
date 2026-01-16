<?php

namespace App\Services;

use App\Events\SaleCompleted;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\SaleRepository;
use App\Repositories\ProductVariantRepository;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private SaleRepository $saleRepository,
        private ProductVariantRepository $variantRepository
    ) {}

    /**
     * Create a new sale with items.
     *
     * Includes retry mechanism for handling duplicate sale_number conflicts
     */
    public function createSale(array $data): Sale
    {
        $maxRetries = 3;
        $lastException = null;

        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                return $this->createSaleInternal($data);
            } catch (\Illuminate\Database\QueryException $e) {
                $lastException = $e;

                // Check if it's a duplicate entry error (MySQL error 1062)
                if ($e->errorInfo[1] === 1062 && str_contains($e->getMessage(), 'sale_number')) {
                    // Wait a bit before retrying
                    usleep(50000 * ($retry + 1)); // 50ms, 100ms, 150ms
                    continue;
                }

                // If it's not a duplicate sale_number error, throw immediately
                throw $e;
            }
        }

        // If all retries failed, throw the last exception
        throw $lastException;
    }

    /**
     * Internal method to create sale (used by retry mechanism)
     */
    private function createSaleInternal(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Validate stock availability
            if (isset($data['items'])) {
                $this->validateStockAvailability($data['items']);
            }

            // Récupérer le store_id avec plusieurs fallbacks
            $storeId = $this->resolveStoreId($data);

            // Générer le sale_number avec store_id pour garantir l'unicité
            $saleNumber = $this->generateUniqueSaleNumber($storeId);

            // Déterminer l'organization_id
            $organizationId = $data['organization_id'] ?? null;
            if (!$organizationId) {
                // Essayer de récupérer depuis le store
                if ($storeId) {
                    $store = \App\Models\Store::find($storeId);
                    $organizationId = $store?->organization_id;
                }
                // Sinon depuis le contexte de l'application
                if (!$organizationId) {
                    try {
                        $organizationId = app()->bound('current_organization') ? app('current_organization')?->id : null;
                    } catch (\Exception $e) {
                        // Ignorer si pas d'organisation dans le contexte
                    }
                }
            }

            // Create sale
            $saleData = [
                'organization_id' => $organizationId,
                'store_id' => $storeId,
                'client_id' => $data['client_id'] ?? null,
                'user_id' => $data['user_id'],
                'sale_date' => $data['sale_date'] ?? now(),
                'subtotal' => 0,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'total' => 0,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'] ?? 'pending',
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'sale_number' => $saleNumber,
            ];

            $sale = $this->saleRepository->create($saleData);

            // Create sale items
            if (isset($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItem($sale, $itemData);
                }
            }

            // Calculate totals
            $sale->calculateTotals();

            // Update payment status based on paid_amount
            if (isset($data['paid_amount']) && $data['paid_amount'] > 0) {
                $sale->updatePaymentStatus();
            }

            return $sale->fresh('items.productVariant.product', 'client', 'user');
        });
    }

    /**
     * Résout le store_id avec plusieurs fallbacks pour garantir une valeur valide
     */
    private function resolveStoreId(array $data): ?int
    {
        // 1. Utiliser le store_id fourni dans les données
        if (!empty($data['store_id'])) {
            return (int) $data['store_id'];
        }

        // 2. Utiliser le current_store_id de l'utilisateur
        $currentStoreId = current_store_id();
        if ($currentStoreId) {
            return $currentStoreId;
        }

        // 3. Récupérer le premier store de l'utilisateur
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if ($user) {
            // Essayer le store par défaut de l'utilisateur
            $defaultStore = $user->stores()
                ->wherePivot('is_default', true)
                ->first();

            if ($defaultStore) {
                // Mettre à jour le current_store_id pour les futures requêtes
                $user->update(['current_store_id' => $defaultStore->id]);
                return $defaultStore->id;
            }

            // Sinon, prendre le premier store disponible
            $firstStore = $user->stores()->first();
            if ($firstStore) {
                $user->update(['current_store_id' => $firstStore->id]);
                return $firstStore->id;
            }

            // Essayer les stores gérés par l'utilisateur
            $managedStore = $user->managedStores()->first();
            if ($managedStore) {
                $user->update(['current_store_id' => $managedStore->id]);
                return $managedStore->id;
            }
        }

        // 4. Récupérer depuis l'organisation courante
        try {
            $organization = app()->bound('current_organization') ? app('current_organization') : null;
            if ($organization) {
                $orgStore = \App\Models\Store::where('organization_id', $organization->id)->first();
                if ($orgStore) {
                    return $orgStore->id;
                }
            }
        } catch (\Exception $e) {
            // Ignorer
        }

        // 5. En dernier recours, prendre n'importe quel store actif
        $anyStore = \App\Models\Store::where('is_active', true)->first();
        if ($anyStore) {
            return $anyStore->id;
        }

        return null;
    }

    /**
     * Génère un numéro de vente unique par store.
     * Format: VT-S{store_id}-{année-mois}-{séquence}
     * Exemple: VT-S1-2026-01-0001, VT-S2-2026-01-0001
     *
     * Utilise un verrou exclusif et un identifiant unique en fallback pour éviter les doublons
     */
    private function generateUniqueSaleNumber(?int $storeId): string
    {
        $storeId = $storeId ?? 0;
        $date = now()->format('Y-m');
        $prefix = 'VT-S' . $storeId . '-' . $date . '-';
        $maxAttempts = 15;

        // Générer un identifiant unique de processus pour éviter les collisions
        $processId = substr(md5(uniqid((string) mt_rand(), true)), 0, 6);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // Récupère le MAX du numéro de séquence pour ce store et ce mois avec verrou
            $maxNumber = Sale::where('sale_number', 'like', $prefix . '%')
                            ->selectRaw('MAX(CAST(SUBSTRING(sale_number, -4) AS UNSIGNED)) as max_num')
                            ->lockForUpdate()
                            ->value('max_num');

            $nextNumber = ($maxNumber ?? 0) + 1 + $attempt;
            $saleNumber = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            // Double vérification avec verrou pour éviter les races conditions
            $exists = Sale::where('sale_number', $saleNumber)
                         ->lockForUpdate()
                         ->exists();

            if (!$exists) {
                return $saleNumber;
            }

            // Petite pause pour réduire la contention
            usleep(10000 * ($attempt + 1)); // 10ms, 20ms, 30ms...
        }

        // En dernier recours, utiliser timestamp + identifiant unique du processus
        return $prefix . now()->format('His') . '-' . $processId;
    }

    /**
     * Update a sale.
     */
    public function updateSale(int $saleId, array $data): Sale
    {
        $sale = $this->saleRepository->find($saleId);

        if (!$sale) {
            throw new \Exception("Sale not found");
        }

        $this->saleRepository->update($sale, $data);

        // Update payment status if paid_amount is set
        if (isset($data['paid_amount'])) {
            $sale->updatePaymentStatus();
        }

        return $sale->fresh('items.productVariant.product');
    }

    /**
     * Complete a sale (mark as completed and paid).
     */
    public function completeSale(int $saleId): Sale
    {
        return DB::transaction(function () use ($saleId) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status === Sale::STATUS_COMPLETED) {
                throw new \Exception("Sale is already completed");
            }

            // Update sale status
            $sale->status = Sale::STATUS_COMPLETED;
            // Only mark as paid if it's not already marked as partial or paid
            if ($sale->payment_status === Sale::PAYMENT_PENDING) {
                $sale->paid_amount = $sale->total;
                $sale->payment_status = Sale::PAYMENT_PAID;
            }
            $sale->save();

            // Load items with sale relationship to avoid lazy loading
            $sale->load('items');

            // Create stock movements for each item
            foreach ($sale->items as $item) {
                $item->setRelation('sale', $sale);
                $item->createStockMovement();
            }

            // Dispatch sale completed event for notifications
            event(new SaleCompleted($sale));

            return $sale->fresh('items.productVariant.product');
        });
    }

    /**
     * Cancel a sale.
     */
    public function cancelSale(int $saleId, string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status === Sale::STATUS_CANCELLED) {
                throw new \Exception("Sale is already cancelled");
            }

            // If sale was completed, restore stock
            if ($sale->status === Sale::STATUS_COMPLETED) {
                $sale->load('items.productVariant');
                foreach ($sale->items as $item) {
                    $item->productVariant->increaseStock($item->quantity);
                }
            }

            $sale->status = Sale::STATUS_CANCELLED;
            $sale->notes = ($sale->notes ? $sale->notes . "\n" : '') . "Cancelled: " . ($reason ?? 'No reason provided');
            $sale->save();

            return $sale->fresh();
        });
    }

    /**
     * Add an item to a sale.
     */
    public function addItem(Sale $sale, array $itemData): SaleItem
    {
        $variant = $this->variantRepository->find($itemData['product_variant_id']);

        if (!$variant) {
            throw new \Exception("Product variant not found");
        }

        // Check stock
        if (!$variant->hasStock($itemData['quantity'])) {
            throw new \Exception("Insufficient stock for {$variant->full_name}");
        }

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_variant_id' => $itemData['product_variant_id'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'] ?? $variant->final_price,
            'discount' => $itemData['discount'] ?? 0,
        ]);

        return $item;
    }

    /**
     * Validate stock availability for all items.
     */
    private function validateStockAvailability(array $items): void
    {
        foreach ($items as $itemData) {
            $variant = $this->variantRepository->find($itemData['product_variant_id']);

            if (!$variant) {
                throw new \Exception("Product variant ID {$itemData['product_variant_id']} not found");
            }

            if (!$variant->hasStock($itemData['quantity'])) {
                throw new \Exception("Insufficient stock for {$variant->full_name}. Available: {$variant->stock_quantity}, Requested: {$itemData['quantity']}");
            }
        }
    }

    /**
     * Get sales statistics.
     */
    public function getSalesStatistics(string $startDate, string $endDate): array
    {
        return $this->saleRepository->statistics($startDate, $endDate);
    }

    /**
     * Get today's sales summary.
     */
    public function getTodaySummary(): array
    {
        $sales = $this->saleRepository->today();

        return [
            'total_sales' => $sales->count(),
            'completed_sales' => $sales->where('status', Sale::STATUS_COMPLETED)->count(),
            'total_amount' => $sales->where('status', Sale::STATUS_COMPLETED)->sum('total'),
            'pending_amount' => $sales->where('status', Sale::STATUS_PENDING)->sum('total'),
        ];
    }

    /**
     * Refund a sale.
     */
    public function refundSale(int $saleId, string $reason, bool $restoreStock = true): Sale
    {
        return DB::transaction(function () use ($saleId, $reason, $restoreStock) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status === Sale::STATUS_CANCELLED) {
                throw new \Exception("Cannot refund a cancelled sale");
            }

            if ($sale->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \Exception("Sale is already refunded");
            }

            // Restore stock if requested
            if ($restoreStock) {
                foreach ($sale->items as $item) {
                    $item->productVariant->increaseStock($item->quantity);
                }
            }

            // Update sale status
            $sale->status = Sale::STATUS_CANCELLED;
            $sale->payment_status = Sale::PAYMENT_REFUNDED;
            $sale->notes = ($sale->notes ? $sale->notes . "\n" : '') . "Refunded: " . $reason;
            $sale->save();

            // Cancel invoice if exists
            if ($sale->invoice && $sale->invoice->status !== 'cancelled') {
                $sale->invoice->status = 'cancelled';
                $sale->invoice->save();
            }

            return $sale->fresh('items.productVariant.product', 'invoice');
        });
    }

    /**
     * Add item to existing sale.
     */
    public function addItemToSale(int $saleId, array $itemData): Sale
    {
        return DB::transaction(function () use ($saleId, $itemData) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status !== Sale::STATUS_PENDING) {
                throw new \Exception("Cannot add items to a completed or cancelled sale");
            }

            $this->addItem($sale, $itemData);
            $sale->calculateTotals();

            return $sale->fresh('items.productVariant.product');
        });
    }

    /**
     * Record a payment for a sale.
     */
    public function recordPayment(int $saleId, array $paymentData): Sale
    {
        return DB::transaction(function () use ($saleId, $paymentData) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status === Sale::STATUS_CANCELLED) {
                throw new \Exception("Cannot record payment for a cancelled sale");
            }

            if ($sale->payment_status === Sale::PAYMENT_REFUNDED) {
                throw new \Exception("Cannot record payment for a refunded sale");
            }

            $amount = $paymentData['amount'];

            if ($amount <= 0) {
                throw new \Exception("Payment amount must be greater than 0");
            }

            if (($sale->paid_amount + $amount) > $sale->total) {
                throw new \Exception("Payment amount exceeds remaining balance");
            }

            // Create payment record
            $sale->payments()->create([
                'user_id' => $paymentData['user_id'],
                'amount' => $amount,
                'payment_method' => $paymentData['payment_method'],
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Update sale paid_amount by summing all payments
            $sale->paid_amount = $sale->payments()->sum('amount');
            $sale->updatePaymentStatus();

            return $sale->fresh(['items.productVariant.product', 'payments']);
        });
    }

    /**
     * Remove item from sale.
     */
    public function removeItemFromSale(int $saleId, int $itemId): Sale
    {
        return DB::transaction(function () use ($saleId, $itemId) {
            $sale = $this->saleRepository->find($saleId);

            if (!$sale) {
                throw new \Exception("Sale not found");
            }

            if ($sale->status !== Sale::STATUS_PENDING) {
                throw new \Exception("Cannot remove items from a completed or cancelled sale");
            }

            $item = $sale->items()->find($itemId);

            if (!$item) {
                throw new \Exception("Item not found in this sale");
            }

            $item->delete();
            $sale->calculateTotals();

            return $sale->fresh('items.productVariant.product');
        });
    }
}
