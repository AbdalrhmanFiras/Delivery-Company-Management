<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{

    protected $guarded = ['id'];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }


}
