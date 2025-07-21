<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuids, SoftDeletes;
    protected $guarded = ['id'];

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function scopeForCompanyId($query, $companyId)
    {
        return $query->where('delivery_company_id', $companyId);
    }


    public function scopeForWarehouseId($query, $warehouseID)
    {
        return $query->where('warehouse_id', $warehouseID);
    }


    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
