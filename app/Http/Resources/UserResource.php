<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CustomerProfileResource;
use App\Http\Resources\DriverProfileResource;
use App\Http\Resources\MerchantProfileResource;
use Illuminate\Http\Resources\Json\JsonResource;
use function Symfony\Component\Clock\now;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'join_at' => $this->created_at->format('Y-m-d'),
            'user_type' => $this->user_type,
            'profile' => match ($this->user_type) {
                'merchant' => new MerchantProfileResource($this->whenLoaded('merchant')),
                // 'driver' => new DriverProfileResource($this->whenLoaded('driver')),
                'customer' => new CustomerProfileResource($this->whenLoaded('customer')),
                default => null,
            }


        ];
    }
}
