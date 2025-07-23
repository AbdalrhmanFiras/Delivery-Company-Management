<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $guarded = ['id'];

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }




    public function scopeMerchantId($query, $id)
    {
        return $query->where('merchant_id', $id);
    }



    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    // public function deliverycompany(): HasMany
    // {
    //     return $this->hasMany(DeliveryCompany::class);
    // }

    // public function order(): HasMany
    // {
    //     return $this->hasMany(Order::class);
    // }


    // public function warehouse(): HasMany
    // {
    //     return $this->hasMany(Employee::class);
    // }
}
