<?php

namespace App\Traits;

use App\Models\OrderLog;

trait LogsOrderChanges
{
    public function logOrderChange($order, string $action, array $originalData = null, array $newData = null)
    {
        OrderLog::create([
            'merchant_id'   => $order->merchant_id,
            'order_id'      => $order->id,
            'action'        => $action,
            'original_data' => $originalData ? json_encode($originalData) : null,
            'new_data'      => $newData ? json_encode($newData) : null,
            'processed_by'  => 'system',
        ]);
    }
}
