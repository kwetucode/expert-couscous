<?php

namespace App\Livewire\Transfer;

use App\Services\StoreTransferService;
use Livewire\Component;

class TransferShow extends Component
{
    public $transferId;
    public $showReceiveModal = false;
    public $receivedQuantities = [];

    protected $listeners = [
        'transferUpdated' => '$refresh',
    ];

    public function mount($transferId)
    {
        $this->transferId = $transferId;
    }

    public function approveTransfer(StoreTransferService $service)
    {
        try {
            $service->approveTransfer($this->transferId, auth()->id());
            $this->dispatch('show-toast', message: 'Transfert approuvé avec succès !', type: 'success');
            $this->dispatch('transferUpdated');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function openReceiveModal(StoreTransferService $service)
    {
        try {
            $transfer = $service->findTransfer($this->transferId);

            // Initialize received quantities with sent quantities (what was actually sent)
            $this->receivedQuantities = [];
            foreach ($transfer->items as $item) {
                // Use quantity_sent as default, fallback to quantity_requested if not yet sent
                $this->receivedQuantities[$item->id] = $item->quantity_sent ?? $item->quantity_requested;
            }

            $this->showReceiveModal = true;

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeReceiveModal()
    {
        $this->showReceiveModal = false;
        $this->receivedQuantities = [];
    }

    public function receiveTransfer(StoreTransferService $service)
    {
        $this->validate([
            'receivedQuantities' => 'required|array',
            'receivedQuantities.*' => 'required|integer|min:0',
        ]);

        try {
            // Convert array keys to integers (item IDs)
            $quantities = [];
            foreach ($this->receivedQuantities as $itemId => $quantity) {
                $quantities[(int)$itemId] = (int)$quantity;
            }

            $service->receiveTransfer($this->transferId, $quantities, auth()->id());

            $this->dispatch('show-toast', message: 'Transfert réceptionné avec succès !', type: 'success');
            $this->dispatch('transferUpdated');
            $this->closeReceiveModal();

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function cancelTransfer(StoreTransferService $service)
    {
        try {
            $service->cancelTransfer($this->transferId, auth()->id(), 'Annulé par l\'utilisateur');
            $this->dispatch('show-toast', message: 'Transfert annulé avec succès !', type: 'success');
            $this->dispatch('transferUpdated');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function render(StoreTransferService $service)
    {
        $transfer = $service->findTransfer($this->transferId);

        if (!$transfer) {
            abort(404, 'Transfert non trouvé');
        }

        // Load relationships
        $transfer->load([
            'fromStore',
            'toStore',
            'items.variant.product',
            'requester',
            'approver',
            'receiver',
        ]);

        // Check user permissions
        $canApprove = $transfer->status === 'pending' &&
            (auth()->user()->isAdmin() ||
                auth()->user()->current_store_id == $transfer->from_store_id);

        $canReceive = $transfer->status === 'in_transit' &&
            (auth()->user()->isAdmin() ||
                auth()->user()->current_store_id == $transfer->to_store_id);

        $canCancel = in_array($transfer->status, ['pending', 'in_transit']) &&
            (auth()->user()->isAdmin() ||
                auth()->user()->current_store_id == $transfer->from_store_id);

        return view('livewire.transfer.show', [
            'transfer' => $transfer,
            'canApprove' => $canApprove,
            'canReceive' => $canReceive,
            'canCancel' => $canCancel,
        ]);
    }
}
