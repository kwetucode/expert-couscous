<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferItemResource extends JsonResource
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

            // Product info
            'product' => [
                'id' => $this->variant?->product?->id,
                'name' => $this->variant?->product?->name,
                'reference' => $this->variant?->product?->reference,
            ],

            // Variant info
            'variant' => [
                'id' => $this->product_variant_id,
                'name' => $this->variant?->name,
                'sku' => $this->variant?->sku,
            ],

            // Quantities
            'quantity_requested' => $this->quantity_requested,
            'quantity_sent' => $this->quantity_sent,
            'quantity_received' => $this->quantity_received,
            'quantity_difference' => $this->quantity_sent
                ? ($this->quantity_sent - ($this->quantity_received ?? 0))
                : null,

            // Status
            'is_complete' => $this->quantity_received && $this->quantity_received == $this->quantity_sent,
            'has_shortage' => $this->quantity_received && $this->quantity_received < $this->quantity_sent,

            // Notes
            'notes' => $this->notes,
        ];
    }
}
