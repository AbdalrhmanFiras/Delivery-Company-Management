<?php

namespace App\Listeners;

use Log;
use App\Models\Driver;
use App\Enums\OrderStatus;
use App\Traits\LogsOrderChanges;
use App\Events\AutoAssignDriverEvent;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\BaseController;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AssignAvailableDriverListener extends BaseController
{
    use LogsOrderChanges;

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AutoAssignDriverEvent $event)
    {
        $order = $event->order;
        $companyId = $order->delivery_company_id;

        $driver = Driver::where('delivery_company_id', $companyId)
            ->where('available', true)
            ->first();

        // if (!$driver) {
        //     Log::warning('No available driver for order: ' . $order->id);
        //     return;
        // }

        $order->driver_id = $driver->id;
        $order->status = OrderStatus::AssignedDriver->value;
        $order->save();
        $this->logOrderChange($order, 'order_assign_driver');

        $driver->available = false;
        $driver->save();

        //  Log::info('Auto-assigned driver ' . $driver->id . ' to order ' . $order->id);
    }
}
