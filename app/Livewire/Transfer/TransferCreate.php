<?php

namespace App\Livewire\Transfer;

use App\Services\StoreTransferService;
use App\Services\StoreService;
use Livewire\Component;
use Livewire\Attributes\Validate;

class TransferCreate extends Component
{
    #[Validate('required|exists:stores,id')]
    public $from_store_id = '';

    #[Validate('required|exists:stores,id|different:from_store_id')]
    public $to_store_id = '';

    #[Validate('nullable|string')]
    public $notes = '';

    // Transfer items
    public $items = [];
    public $searchProduct = '';
    public $selectedVariant = null;
    public $quantity = 1;

    // Selected variant details for display
    public $selectedVariantName = '';
    public $selectedVariantSku = '';

    public function mount()
    {
        // Set current store as default from_store
        $this->from_store_id = auth()->user()->current_store_id;
    }

    public function resetForm()
    {
        $this->reset([
            'from_store_id',
            'to_store_id',
            'notes',
            'items',
            'searchProduct',
            'selectedVariant',
            'selectedVariantName',
            'selectedVariantSku',
            'quantity',
        ]);
        $this->resetErrorBag();
    }

    public function selectVariant($variantId)
    {
        $variant = \App\Models\ProductVariant::with('product')->find($variantId);

        if ($variant) {
            $this->selectedVariant = $variantId;
            $this->selectedVariantName = $variant->product->name . ' - ' . $variant->name;
            $this->selectedVariantSku = $variant->sku;
            $this->searchProduct = ''; // Clear search to hide dropdown
        }
    }

    public function clearSelection()
    {
        $this->selectedVariant = null;
        $this->selectedVariantName = '';
        $this->selectedVariantSku = '';
    }

    public function addItem(StoreService $service)
    {
        $this->validate([
            'selectedVariant' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // Check stock availability
            $available = $service->checkStockAvailability(
                $this->from_store_id,
                $this->selectedVariant,
                $this->quantity
            );

            if (!$available) {
                $this->dispatch('show-toast', message: 'Stock insuffisant dans le magasin source !', type: 'error');
                return;
            }

            // Get variant details
            $variant = \App\Models\ProductVariant::with('product')->find($this->selectedVariant);

            // Check if item already exists
            $existingIndex = collect($this->items)->search(function ($item) {
                return $item['product_variant_id'] == $this->selectedVariant;
            });

            if ($existingIndex !== false) {
                // Update quantity
                $this->items[$existingIndex]['quantity'] += $this->quantity;
            } else {
                // Add new item
                $this->items[] = [
                    'product_variant_id' => $this->selectedVariant,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'quantity' => $this->quantity,
                ];
            }

            // Reset selection
            $this->selectedVariant = null;
            $this->selectedVariantName = '';
            $this->selectedVariantSku = '';
            $this->quantity = 1;
            $this->searchProduct = '';

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindex array
    }

    public function updateQuantity($index, $newQuantity)
    {
        if ($newQuantity < 1) {
            $this->removeItem($index);
            return;
        }

        $this->items[$index]['quantity'] = $newQuantity;
    }

    public function save(StoreTransferService $service)
    {
        $this->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'items' => 'required|array|min:1',
        ]);

        try {
            // Prepare items array for the service
            $items = collect($this->items)->map(function ($item) {
                return [
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'notes' => null,
                ];
            })->toArray();

            // Create transfer data array
            $data = [
                'from_store_id' => $this->from_store_id,
                'to_store_id' => $this->to_store_id,
                'items' => $items,
                'notes' => $this->notes,
                'requested_by' => auth()->id(),
            ];

            $service->createTransfer($data);

            $this->dispatch('show-toast', message: 'Transfert créé avec succès !', type: 'success');
            $this->dispatch('close-transfer-modal');
            $this->resetForm();
            $this->dispatch('transferCreated');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function render(StoreService $service)
    {
        // Get available stores
        $stores = $service->getAllStores(
            sortBy: 'name',
            sortDirection: 'asc',
            perPage: 100
        )->items();

        // Get available products/variants if searching
        $variants = [];
        if ($this->searchProduct && $this->from_store_id) {
            $searchTerm = $this->searchProduct;

            $variants = \App\Models\ProductVariant::query()
                ->with('product')
                ->where(function ($query) use ($searchTerm) {
                    // Recherche dans le SKU de la variante
                    $query->where('sku', 'like', '%' . $searchTerm . '%')
                        // Ou dans le nom du produit
                        ->orWhereHas('product', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('reference', 'like', '%' . $searchTerm . '%');
                        });
                })
                ->whereIn('id', function ($query) {
                    $query->select('product_variant_id')
                        ->from('store_stock')
                        ->where('store_id', $this->from_store_id)
                        ->where('quantity', '>', 0);
                })
                ->limit(10)
                ->get();

            // Add stock quantity to each variant
            $variantIds = $variants->pluck('id')->toArray();

            if (!empty($variantIds)) {
                $stockData = \App\Models\StoreStock::where('store_id', $this->from_store_id)
                    ->whereIn('product_variant_id', $variantIds)
                    ->pluck('quantity', 'product_variant_id');

                $variants->each(function ($variant) use ($stockData) {
                    $variant->available_stock = $stockData[$variant->id] ?? 0;
                });
            }
        }

        return view('livewire.transfer.create', [
            'stores' => $stores,
            'variants' => $variants,
        ]);
    }
}
