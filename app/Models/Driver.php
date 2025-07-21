<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];


    public function scopeForCompanyId($query, $companyId)
    {
        return $query->where('delivery_company_id', $companyId);
    }


    public function users()
    {
        return $this->belongsTo(User::class);
    }


    public function deliverycompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(DriverFeedback::class);
    }

    public function averageRating()
    {
        return $this->feedbacks()->avg('rating');
    }
}
