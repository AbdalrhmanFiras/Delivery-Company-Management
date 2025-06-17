<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchant extends Model
{

    protected $guarded = ['id'];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
