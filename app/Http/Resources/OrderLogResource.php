<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLogResource extends JsonResource
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
            'merchant_id' => $this->merchant_id,
            'order_id' => $this->order_id,
            'action' => $this->action,
            'original_data' => json_decode($this->original_data),
            'new_data' => json_decode($this->new_data),
            'processed_by' => $this->processed_by,
            'created_at' => $this->created_at,
        ];
    }
}
