<?php

namespace App\Models;

use App\Enums\LocationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Delivery_Company extends Model
{
    use HasUuids;
    protected $casts = [
        'status' => LocationStatus::class
    ];
}
