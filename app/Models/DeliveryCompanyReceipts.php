<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DeliveryCompanyReceipts extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function scopeOrderId($query, $id)
    {
        return $query->where('order_id', $id);
    }
}
