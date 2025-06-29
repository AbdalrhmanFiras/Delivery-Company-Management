<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
        protected $guarded = ['id'];
    use HasFactory , HasUuids;

    protected $casts = [
    'status' => OrderStatus::class,
    ];


    public function customer() : BelongsTo 
    {
        return $this->belongsTo(Customer::class);
    }

     public function merchant() : BelongsTo 
    {
        return $this->belongsTo(Merchant::class);
    }



}
