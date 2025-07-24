<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{

    protected $casts = [
        'status' => ComplaintStatus::class,
    ];
    protected $guarded = ['id'];

    public function scopeCustomerId($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeComplainStatus($query, string $status)
    {
        return $query->where('status', $status);
    }


    public function scopeId($query, $Id)
    {
        return $query->where('id', $Id);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
