<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'contact_info' => $this->when(!empty($this->contact_info), $this->contact_info),
            'governorate' => $this->when(!empty($this->governorate), $this->governorate),
            'merchant_id' => $this->merchant_id
        ];
    }
}
