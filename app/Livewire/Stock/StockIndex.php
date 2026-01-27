<?php

namespace App\Livewire\Stock;

use App\Actions\Stock\AddStockAction;
use App\Actions\Stock\RemoveStockAction;
use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\UpdateStockMovementAction;
use App\Livewire\Forms\StockMovementForm;
use App\Repositories\StockMovementRepository;
use App\Repositories\ProductVariantRepository;
use App\Models\StockMovement;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class StockIndex extends Component
{
    use WithPagination;

    public StockMovementForm $form;

    public $search = '';
    public $showAddModal = false;
    public $showRemoveModal = false;
    public $showAdjustModal = false;
    public $showEditModal = false;
    public $showDetailsModal = false;
    public $editingMovement = null;
    public $selectedProductMovements = [];
    public $selectedProductName = '';
    public $perPage = 10;
    public $type = '';
    public $movementType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $viewMode = 'grouped'; // 'grouped' or 'detailed'

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'movementType' => ['except' => ''],
        'viewMode' => ['except' => 'grouped'],
    ];

    public function mount()
    {
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render(StockMovementRepository $movementRepository, ProductVariantRepository $variantRepository)
    {
        $query = StockMovement::query()
            ->with(['productVariant.product', 'user'])
            ->orderBy('date', 'desc');

        // Filter by current store
        if (current_store_id()) {
            $query->where('store_id', current_store_id());
        }

        // Filter by search (product name or reference)
        if ($this->search) {
            $query->whereHas('productVariant.product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            })->orWhere('reference', 'like', '%' . $this->search . '%');
        }

        // Filter by type (in/out)
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // Filter by movement type
        if ($this->movementType) {
            $query->where('movement_type', $this->movementType);
        }

        // Filter by date range
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        // Get all movements for grouping
        $allMovements = $query->get();

        // Group movements by product variant
        $groupedMovements = $allMovements->groupBy('product_variant_id')->map(function ($movements) {
            $firstMovement = $movements->first();
            $totalIn = $movements->where('type', 'in')->sum('quantity');
            $totalOut = $movements->where('type', 'out')->sum('quantity');
            $lastDate = $movements->max('date');
            $movementCount = $movements->count();

            return (object) [
                'product_variant_id' => $firstMovement->product_variant_id,
                'productVariant' => $firstMovement->productVariant,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net_change' => $totalIn - $totalOut,
                'movement_count' => $movementCount,
                'last_date' => $lastDate,
                'movements' => $movements,
            ];
        })->values();

        // Paginate grouped movements manually
        $page = request()->get('page', 1);
        $perPage = $this->perPage;
        $total = $groupedMovements->count();
        $paginatedGrouped = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedMovements->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // For detailed view, paginate normally
        $movements = $query->paginate($this->perPage);

        // Get all variants for dropdowns
        $variants = $variantRepository->all();

        return view('livewire.stock.index', [
            'movements' => $movements,
            'groupedMovements' => $paginatedGrouped,
            'variants' => $variants,
        ]);
    }

    /**
     * Toggle between grouped and detailed view
     */
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grouped' ? 'detailed' : 'grouped';
        $this->resetPage();
    }

    /**
     * Open details modal for a specific product
     */
    public function openDetailsModal(int $productVariantId)
    {
        $query = StockMovement::query()
            ->with(['productVariant.product', 'user', 'store'])
            ->where('product_variant_id', $productVariantId)
            ->orderBy('date', 'desc');

        // Apply store filter if a specific store is selected
        $storeId = effective_store_id();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Apply same filters
        if ($this->type) {
            $query->where('type', $this->type);
        }
        if ($this->movementType) {
            $query->where('movement_type', $this->movementType);
        }
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        $this->selectedProductMovements = $query->get()->toArray();

        if (!empty($this->selectedProductMovements)) {
            $firstMovement = $this->selectedProductMovements[0];
            $this->selectedProductName = $firstMovement['product_variant']['product']['name'] ?? 'Produit';
        }

        $this->showDetailsModal = true;
    }

    /**
     * Close details modal
     */
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedProductMovements = [];
        $this->selectedProductName = '';
    }

    /**
     * Called when product_variant_id changes - prefill unit_price with cost_price
     */
    public function updatedFormProductVariantId($value)
    {
        if ($value && $this->form->type === 'add') {
            $this->form->prefillUnitPrice();
        }
    }

    /**
     * Called when movement_type changes - regenerate reference
     */
    public function updatedFormMovementType($value)
    {
        if ($value) {
            $this->form->generateReference();
        }
    }

    public function openAddModal()
    {
        Log::info('openAddModal was called!');
        $this->form->reset();
        $this->form->setType('add');
        $this->showAddModal = true;
        Log::info('showAddModal set to true', ['showAddModal' => $this->showAddModal]);
    }

    public function openRemoveModal()
    {
        $this->form->reset();
        $this->form->setType('remove');
        $this->showRemoveModal = true;
    }

    public function openAdjustModal()
    {
        $this->form->reset();
        $this->form->setType('adjust');
        Log::info('openAdjustModal called', ['form_type' => $this->form->type]);
        $this->showAdjustModal = true;
    }

    public function addStock(AddStockAction $addAction)
    {
        $this->form->validate();

        try {
            $data = $this->form->toArray();
            $data['user_id'] = auth()->id();

            $addAction->execute($data);

            $this->showAddModal = false;
            $this->form->reset();
            $this->dispatch('$refresh');
            session()->flash('message', 'Stock ajouté avec succès.');
        } catch (\Exception $e) {
            Log::error('Error adding stock: ' . $e->getMessage());
            session()->flash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function removeStock(RemoveStockAction $removeAction)
    {
        $this->form->validate();

        try {
            $data = $this->form->toArray();
            $data['user_id'] = auth()->id();

            $removeAction->execute($data);

            $this->showRemoveModal = false;
            $this->form->reset();
            $this->dispatch('$refresh');
            session()->flash('message', 'Stock retiré avec succès.');
        } catch (\Exception $e) {
            Log::error('Error removing stock: ' . $e->getMessage());
            session()->flash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function adjustStock(AdjustStockAction $adjustAction)
    {
        Log::info('adjustStock called', [
            'form_type' => $this->form->type,
            'movement_type' => $this->form->movement_type,
            'product_variant_id' => $this->form->product_variant_id,
            'new_quantity' => $this->form->new_quantity,
            'reason' => $this->form->reason,
        ]);

        $this->form->validate();

        try {
            $data = $this->form->toArray();
            $data['user_id'] = auth()->id();

            Log::info('Adjusting stock with data:', $data);

            $adjustAction->execute($data);

            $this->showAdjustModal = false;
            $this->form->reset();
            $this->dispatch('$refresh');
            session()->flash('message', 'Stock ajusté avec succès.');
        } catch (\Exception $e) {
            Log::error('Error adjusting stock: ' . $e->getMessage(), [
                'data' => $this->form->toArray(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('form.new_quantity', $e->getMessage());
        }
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->form->reset();
    }

    public function closeRemoveModal()
    {
        $this->showRemoveModal = false;
        $this->form->reset();
    }

    public function closeAdjustModal()
    {
        $this->showAdjustModal = false;
        $this->form->reset();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function updatingMovementType()
    {
        $this->resetPage();
    }

    /**
     * Open edit modal for a movement
     */
    public function openEditModal(int $movementId)
    {
        $movement = StockMovement::with('productVariant.product')->find($movementId);

        if (!$movement) {
            session()->flash('error', 'Mouvement non trouvé.');
            return;
        }

        // Prevent editing of sale movements
        if ($movement->movement_type === 'sale') {
            session()->flash('error', 'Les mouvements de vente ne peuvent pas être modifiés depuis le module de stock.');
            return;
        }

        $this->editingMovement = $movement;
        $this->form->reset();
        $this->form->product_variant_id = $movement->product_variant_id;
        $this->form->quantity = $movement->quantity;
        $this->form->movement_type = $movement->movement_type;
        $this->form->reference = $movement->reference;
        $this->form->reason = $movement->reason;
        $this->form->unit_price = $movement->unit_price;
        $this->form->date = $movement->date?->format('Y-m-d');
        $this->form->type = $movement->type === 'in' ? 'add' : 'remove';

        $this->showEditModal = true;
    }

    /**
     * Update an existing movement
     */
    public function updateMovement(UpdateStockMovementAction $updateAction)
    {
        if (!$this->editingMovement) {
            session()->flash('error', 'Aucun mouvement sélectionné.');
            return;
        }

        // Prevent editing of sale movements
        if ($this->editingMovement->movement_type === 'sale') {
            session()->flash('error', 'Les mouvements de vente ne peuvent pas être modifiés depuis le module de stock.');
            $this->closeEditModal();
            return;
        }

        // For edit, we validate quantity (not new_quantity)
        $this->validate([
            'form.quantity' => ['required', 'integer', 'min:1'],
            'form.reference' => ['nullable', 'string', 'max:255'],
            'form.reason' => ['nullable', 'string', 'max:500'],
            'form.unit_price' => ['nullable', 'numeric', 'min:0'],
            'form.date' => ['nullable', 'date'],
        ]);

        try {
            $data = [
                'quantity' => $this->form->quantity,
                'reference' => $this->form->reference,
                'reason' => $this->form->reason,
                'unit_price' => $this->form->unit_price,
                'date' => $this->form->date,
            ];

            $updateAction->execute($this->editingMovement->id, $data);

            $this->showEditModal = false;
            $this->editingMovement = null;
            $this->form->reset();
            session()->flash('message', 'Mouvement mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error updating movement: ' . $e->getMessage());
            session()->flash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    /**
     * Close edit modal
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingMovement = null;
        $this->form->reset();
    }
}
