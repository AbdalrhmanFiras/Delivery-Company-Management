<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer  extends Authenticatable implements JWTSubject
{
    use HasUuids, HasRoles;

    protected $guard_name = 'customer';

    protected $guarded = ['id'];

    public function merchants()
    {
        return $this->belongsTo(Merchant::class);
    }


    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
