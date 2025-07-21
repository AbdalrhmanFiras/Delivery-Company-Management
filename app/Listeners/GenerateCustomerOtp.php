<?php

namespace App\Listeners;

use App\Events\CustomerOtpLogin;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class GenerateCustomerOtp
{

    public function handle(CustomerOtpLogin $event): void
    {
        $cacheKey = 'otp_' . $event->customer->phone;
        if (Cache::has($cacheKey)) {
            Log::info('OTP already exists for ' . $event->customer->phone);
            return;
        }

        $otp = rand(100000, 9999999);
        Cache::put($cacheKey, $otp, now()->addMinutes(5));
        Log::info('otp generate successfuly ' . $otp);
    }
}
