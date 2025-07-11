<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tracking_number' => $this->tracking_number,
            'status_label' => $this->status->labelForCustomer(),
            'create_at' => $this->created_at->format('Y-m-d'),
            'total_price' => $this->total_price,
            'customer_address' => $this->customer_address,
            'estimated_delivery' => optional($this->created_at)->addDays(3)->format('Y-m-d'),
        ];
    }
}
