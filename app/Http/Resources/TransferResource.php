<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'reference' => $this->reference,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Stores
            'from_store' => [
                'id' => $this->from_store_id,
                'name' => $this->fromStore->name,
                'code' => $this->fromStore->code,
                'address' => $this->fromStore->address,
            ],
            'to_store' => [
                'id' => $this->to_store_id,
                'name' => $this->toStore->name,
                'code' => $this->toStore->code,
                'address' => $this->toStore->address,
            ],

            // Items
            'items' => TransferItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items->count(),

            // Users
            'requester' => [
                'id' => $this->requested_by,
                'name' => $this->requester?->name,
                'email' => $this->requester?->email,
            ],
            'approver' => $this->when($this->approved_by, [
                'id' => $this->approved_by,
                'name' => $this->approver?->name,
                'email' => $this->approver?->email,
            ]),
            'receiver' => $this->when($this->received_by, [
                'id' => $this->received_by,
                'name' => $this->receiver?->name,
                'email' => $this->receiver?->email,
            ]),
            'canceller' => $this->when($this->cancelled_by, [
                'id' => $this->cancelled_by,
                'name' => $this->canceller?->name,
                'email' => $this->canceller?->email,
            ]),

            // Dates
            'transfer_date' => $this->transfer_date?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'received_at' => $this->received_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),

            // Additional info
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,

            // Permissions
            'can_approve' => $this->canBeApproved(),
            'can_receive' => $this->canBeReceived(),
            'can_cancel' => $this->canBeCancelled(),
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente d\'approbation',
            'in_transit' => 'En transit',
            'completed' => 'Complété',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }
}
