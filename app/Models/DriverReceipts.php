<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DriverReceipts extends Model
{
    use HasUuids;

    protected $guarded = ['id'];



    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function deliveryCompany()
    {
        return $this->belongsTo(DeliveryCompany::class);
    }


    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
