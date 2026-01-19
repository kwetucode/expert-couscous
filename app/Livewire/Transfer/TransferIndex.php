<?php

namespace App\Livewire\Transfer;

use App\Services\StoreTransferService;
use App\Services\StoreService;
use Livewire\Component;
use Livewire\WithPagination;

class TransferIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $storeFilter = '';
    public $statusFilter = '';
    public $directionFilter = 'all'; // all, outgoing, incoming
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // Modal state
    public $transferToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'storeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'directionFilter' => ['except' => 'all'],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $listeners = [
        'transferCreated' => '$refresh',
        'transferUpdated' => '$refresh',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStoreFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDirectionFilter()
    {
        $this->resetPage();
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

    public function confirmCancel($transferId)
    {
        $this->transferToDelete = $transferId;
    }

    public function cancelTransfer(StoreTransferService $service)
    {
        if (!$this->transferToDelete) {
            return;
        }

        try {
            $service->cancelTransfer($this->transferToDelete, auth()->id(), 'Annulé par l\'utilisateur');

            $this->transferToDelete = null;
            $this->dispatch('show-toast', message: 'Transfert annulé avec succès !', type: 'success');
            $this->resetPage();

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function deleteTransfer($transferId)
    {
        try {
            $transfer = \App\Models\StoreTransfer::findOrFail($transferId);

            // Vérifier que le transfert est bien en statut pending
            if ($transfer->status !== 'pending') {
                throw new \Exception('Seuls les transferts en attente peuvent être supprimés.');
            }

            // Supprimer les items du transfert
            $transfer->items()->delete();

            // Supprimer le transfert
            $transfer->delete();

            $this->dispatch('show-toast', message: 'Transfert supprimé avec succès !', type: 'success');
            $this->resetPage();

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function approveTransfer($transferId, StoreTransferService $service)
    {
        try {
            $service->approveTransfer($transferId, auth()->id());
            $this->dispatch('show-toast', message: 'Transfert approuvé avec succès !', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function render(StoreTransferService $service, StoreService $storeService)
    {
        $currentStoreId = auth()->user()->current_store_id;

        // Build query
        $query = $service->getAllTransfers(
            search: $this->search,
            status: $this->statusFilter ?: null,
            sortBy: $this->sortField,
            sortDirection: $this->sortDirection
        );

        // Apply store filter
        if ($this->storeFilter) {
            $query->where(function ($q) {
                $q->where('from_store_id', $this->storeFilter)
                    ->orWhere('to_store_id', $this->storeFilter);
            });
        }

        // Apply direction filter based on current store
        if ($this->directionFilter === 'outgoing') {
            $query->where('from_store_id', $currentStoreId);
        } elseif ($this->directionFilter === 'incoming') {
            $query->where('to_store_id', $currentStoreId);
        } else {
            // Show all transfers related to current store
            $query->where(function ($q) use ($currentStoreId) {
                $q->where('from_store_id', $currentStoreId)
                    ->orWhere('to_store_id', $currentStoreId);
            });
        }

        $transfers = $query->with(['fromStore', 'toStore', 'requester', 'approver', 'receiver', 'items'])
            ->paginate($this->perPage);

        // Get available stores for filter
        $stores = $storeService->getAllStores(
            sortBy: 'name',
            sortDirection: 'asc',
            perPage: 100
        )->items();

        // Get statistics
        $stats = $service->getTransferStatistics($currentStoreId);

        return view('livewire.transfer.index', [
            'transfers' => $transfers,
            'stores' => $stores,
            'statistics' => $stats,
        ]);
    }
}
