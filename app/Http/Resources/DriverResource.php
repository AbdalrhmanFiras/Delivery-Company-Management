<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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
            'driver_name' => $this->user?->name,
            'email' => $this->user?->email,
            'phone' => $this->phone,
            'age' => $this->user?->age,
            'address' => $this->address,
            'vehicle_number' => $this->vehicle_number,
            'status' => $this->status,
            'available' => $this->available,
            'join_at' => $this->created_at,
        ];
    }
}
