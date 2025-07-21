<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverFeedback extends Model
{

    protected $guarded = ['id'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
