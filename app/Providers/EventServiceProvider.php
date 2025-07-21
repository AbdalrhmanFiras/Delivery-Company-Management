<?php

namespace App\Providers;

use App\Events\CustomerOtpLogin;
use App\Listeners\GenerateCustomerOtp;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\CustomerOtpLogin::class => [
            \App\Listeners\GenerateCustomerOtp::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
