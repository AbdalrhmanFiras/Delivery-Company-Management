<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    public function users()
    {
        return $this->belongsTo(User::class);
    }

}
