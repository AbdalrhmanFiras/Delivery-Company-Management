<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id,
        'merchant_id' => $this->merchant_id,
        'customer_id' =>$this->customer_id,
        'total_price' =>$this->total_price,
        'create_at' => $this->created_at->format('Y-m-d')
    ];
    }
}
