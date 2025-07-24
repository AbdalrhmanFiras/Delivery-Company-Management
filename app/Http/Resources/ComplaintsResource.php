<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintsResource extends JsonResource
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
            'type' => $this->type,
            'message' => $this->message,
            'merchant_id' => $this->order->merchant_id ?? null,
            'warehouse_id' => $this->order->warehouse_id ?? null,
            'delivery_company_id' => $this->order->delivery_company_id ?? null,
            'created_at' => $this->created_at->format('Y-m-d')
        ];
    }
}
