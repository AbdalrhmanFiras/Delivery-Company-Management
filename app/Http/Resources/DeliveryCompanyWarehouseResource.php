<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryCompanyWarehouseResource extends JsonResource
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
            'company_name' => $this->company_name,
            'contact_info' => $this->contact_info,
            'governorate' => $this->when(!empty($this->governorate), $this->governorate),
            'join_at' => $this->created_at,
            'warehouse_id' => $this->when(!is_null($this->warehouse_id), $this->warehouse_id)

        ];
    }
}
