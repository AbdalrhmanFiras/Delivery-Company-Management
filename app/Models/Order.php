<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    const STATUS_NOT_SENT = 'not sent';
    const STATUS_SENT = 'sent';


    protected $guarded = ['id'];
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'status' => OrderStatus::class,
    ];



    public function scopeUploaded($query, $status)
    {
        return $query->where('upload', $status);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
