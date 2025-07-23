<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Prunable;

    const STATUS_NOT_SENT = 'not sent';
    const STATUS_SENT = 'sent';



    protected $guarded = ['id'];


    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function scopeOrderfilters($query, $filters)
    {

        return $query->when($filters['delivery_company_id'] ?? null, fn($q, $id) => $q->forCompanyId($id))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->orderStatus($s));
    }

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


    public function scopeWarehouseId($query, $id)
    {
        return $query->where('warehouse_id', $id);
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


    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }


    public function driverFeedback()
    {
        return $this->hasOne(DriverFeedback::class);
    }


    protected function schedule(Schedule $schedule)
    {
        $schedule->command('model:prune')->daily();
    }


    public function prunable()
    {
        return static::where('status',  OrderStatus::Cancelled->value)
            ->where('updated_at', '<', now()->subMonths(6));
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
