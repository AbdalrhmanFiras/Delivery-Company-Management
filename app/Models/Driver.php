<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function users()
    {
        return $this->belongsTo(User::class);
    }


    public function deliverycompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }
}
