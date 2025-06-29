<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = ['id'];
    use HasUuids;

    public function merchants()
    {
        return $this->belongsTo(Merchant::class);
    }


    public function order():HasMany
    {
        return $this->hasMany(Order::class);
    }
}
