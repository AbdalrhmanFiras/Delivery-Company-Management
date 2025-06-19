<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'phone' => $this->phone,
            'country' => $this->country,
            'city' => $this->city,
            'status' => $this->status,
            'business_name' => $this->business_name,
            'business_type' => $this->when(!is_null($this->business_type), $this->business_type),
            'business_license_url' => $this->when(
                !is_null($this->business_license),
                asset('storage/' . $this->business_license)
            ),





        ];
    }
}
