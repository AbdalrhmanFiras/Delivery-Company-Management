<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{


    protected $guarded = ['id'];

    public function merchant(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function deliverycompany(): HasMany
    {
        return $this->hasMany(DeliveryCompany::class);
    }

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }


    public function warehouse(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
