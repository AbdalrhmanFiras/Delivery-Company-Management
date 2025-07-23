<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasUuids;
    protected $guarded = ['id'];

    // public function customers()
    // {
    //     return $this->hasMany(Customer::class);
    // }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function warehouse(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}
