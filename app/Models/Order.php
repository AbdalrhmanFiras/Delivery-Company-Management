<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    const STATUS_NOT_SENT = 'not sent';
    const STATUS_SENT = 'sent';


    protected $guarded = ['id'];


    protected $casts = [
        'status' => OrderStatus::class,
    ];


    public function scopePhone($query, $phone)
    {
        return $query->where('customer_phone', $phone);
    }


    public function scopeForCompanyId($query, $companyId)
    {
        return $query->where('delivery_company_id', $companyId);
    }


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


    public function scopeMerchantId($query, $id)
    {
        return $query->where('merchant_id', $id);
    }


    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }


    public function deliverycompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }


    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }


    public function warehouseReceipts(): HasMany
    {
        return $this->hasMany(WarehouseReceipts::class);
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            do {
                $tracking = 'ORD-' . strtoupper(Str::random(8));
            } while (self::where('tracking_number', $tracking)->exists());

            $order->tracking_number = $tracking;
        });
    }
}
