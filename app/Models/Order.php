<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\OrderStatus;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    const STATUS_NOT_SENT = 'not sent';
    const STATUS_SENT = 'sent';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function scopeUploaded($query, $status)
    {
        return $query->where('upload', $status);
    }

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function scopeOrderStatus($query, int $status)
    {
        return $query->where('status', $status);
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
