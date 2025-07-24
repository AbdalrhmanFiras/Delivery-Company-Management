<?php

namespace App\Traits;

use App\Models\OrderLog;
use Illuminate\Support\Facades\Auth;

trait LogsOrderChanges
{
    public function logOrderChange($order, string $action, array $originalData = null, array $newData = null, $processed_by = null)
    {
        $user = Auth::user();

        $adminId = null;
        $merchantId = $order->merchant_id;
        $processedBy = 'system';

        if ($user) {
            $class = class_basename($user);

            if ($class === 'Admin') {
                $adminId = $user->id;
                $processedBy = $processed_by ?? $user->name;
                $merchantId = $order->merchant_id;
            } elseif ($class === 'Merchant') {
                $merchantId = $user->id;
                $processedBy = $processed_by ?? $user->name;
            } else {
                $processedBy = $processed_by ?? $class;
            }
        } else {
            $processedBy = $processed_by ?? 'system';
        }

        OrderLog::create([
            'admin_id'      => $adminId,
            'merchant_id'   => $merchantId,
            'order_id'      => $order->id,
            'driver_id'     => $order->driver_id ?? null,
            'action'        => $action,
            'original_data' => $originalData ? json_encode($originalData) : null,
            'new_data'      => $newData ? json_encode($newData) : null,
            'processed_by'  => $processedBy,
        ]);
    }
}
