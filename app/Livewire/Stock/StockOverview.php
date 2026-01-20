<?php

namespace App\Livewire\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Services\StockOverviewService;
use App\Dtos\Stock\StockOverviewDto;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class StockOverview extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $categoryId = '';
    public $stockLevel = '';
    public $sortField = 'stock_quantity';
    public $sortDirection = 'asc';
    public $perPage = 15;

    // Modals
    public $showAdjustModal = false;
    public $adjustingVariant = null;
    public $newQuantity = null;
    public $adjustReason = '';

    protected $listeners = [
        'storeChanged' => '$refresh',
        'store-switched' => '$refresh',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'stockLevel' => ['except' => ''],
        'sortField' => ['except' => 'stock_quantity'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function render(StockOverviewService $overviewService)
    {
        // Calculate KPIs
        $kpis = $overviewService->calculateKPIs();

        // Get filters DTO
        $filtersDto = $this->getFiltersDto();

        // Get inventory variants with filters
        $variants = $overviewService->getInventoryVariants($filtersDto->toRepositoryParams());

        // Paginate results
        $paginatedVariants = $this->paginateCollection($variants, $this->perPage);

        // Get categories for filter dropdown
        $categories = $overviewService->getCategories();

        return view('livewire.stock.stock-overview', [
            'kpis' => $kpis,
            'variants' => $paginatedVariants,
            'categories' => $categories,
            'filtersDto' => $filtersDto,
        ]);
    }

    /**
     * Get filters DTO from component properties.
     */
    private function getFiltersDto(): StockOverviewDto
    {
        return StockOverviewDto::fromLivewire([
            'search' => $this->search,
            'categoryId' => $this->categoryId,
            'stockLevel' => $this->stockLevel,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
        ]);
    }

    /**
     * Manually paginate a collection.
     */
    private function paginateCollection($collection, $perPage)
    {
        $currentPage = $this->paginators['page'] ?? 1;
        $total = $collection->count();
        $items = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Sort by field.
     */
    public function sortBy(string $field)
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
     * Reset filters.
     */
    public function resetFilters()
    {
        $this->reset(['search', 'categoryId', 'stockLevel']);
        $this->resetPage();
    }

    /**
     * Open adjust stock modal for a variant.
     */
    public function openAdjustModal(int $variantId)
    {
        $variant = \App\Models\ProductVariant::with('product')->find($variantId);

        if (!$variant) {
            session()->flash('error', 'Variante non trouvée.');
            return;
        }

        $this->adjustingVariant = $variant;
        $this->newQuantity = $variant->stock_quantity;
        $this->adjustReason = '';
        $this->showAdjustModal = true;
    }

    /**
     * Adjust stock for the selected variant.
     */
    public function adjustStock(AdjustStockAction $adjustAction)
    {
        if (!$this->adjustingVariant) {
            session()->flash('error', 'Aucune variante sélectionnée.');
            return;
        }

        $this->validate([
            'newQuantity' => ['required', 'integer', 'min:0'],
            'adjustReason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $data = [
                'product_variant_id' => $this->adjustingVariant->id,
                'new_quantity' => $this->newQuantity,
                'reason' => $this->adjustReason,
                'user_id' => auth()->id(),
            ];

            $adjustAction->execute($data);

            $this->closeAdjustModal();
            session()->flash('message', 'Stock ajusté avec succès.');
        } catch (\Exception $e) {
            Log::error('Error adjusting stock: ' . $e->getMessage());
            $this->addError('newQuantity', $e->getMessage());
        }
    }

    /**
     * Close adjust modal.
     */
    public function closeAdjustModal()
    {
        $this->showAdjustModal = false;
        $this->adjustingVariant = null;
        $this->newQuantity = null;
        $this->adjustReason = '';
        $this->resetValidation();
    }

    /**
     * Navigate to movements page for a specific variant.
     */
    public function viewHistory(int $variantId)
    {
        return redirect()->route('stock.index', ['variant_id' => $variantId]);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryId()
    {
        $this->resetPage();
    }

    public function updatingStockLevel()
    {
        $this->resetPage();
    }

    /**
     * Export stock overview to Excel.
     */
    public function exportExcel()
    {
        // Build query parameters with current filters
        $queryParams = http_build_query([
            'search' => $this->search,
            'categoryId' => $this->categoryId,
            'stockLevel' => $this->stockLevel,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);

        return redirect()->route('stock.export.excel', ['?' . $queryParams]);
    }

    /**
     * Export stock overview to PDF.
     */
    public function exportPdf()
    {
        // Build query parameters with current filters
        $queryParams = http_build_query([
            'search' => $this->search,
            'categoryId' => $this->categoryId,
            'stockLevel' => $this->stockLevel,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);

        return redirect()->route('stock.export.pdf', ['?' . $queryParams]);
    }
}
