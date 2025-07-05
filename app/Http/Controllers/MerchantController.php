<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendAllNotSentOrdersJob;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\StoreOrderResource;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Requests\AssignWarehouseRequest;


class MerchantController extends BaseController
{

    public function sendToWarehouse(Request $request, $orderId)
    {
        Log::info("Merchant is sending order #{$orderId} to warehouse.");

        try {
            DB::beginTransaction();
            $order = Order::where('id', $orderId)
                ->where('status', 0)
                ->firstOrFail();
            if ($order->status != OrderStatus::Pending) {
                return $this->errorResponse('Order is not in pending status.');
            }
            $order->status = OrderStatus::AtWarehouse->value;
            $order->upload = Order::STATUS_SENT;
            $order->warehouse_id = $order->merchant->warehouse_id;
            $order->save();
            DB::commit();
            Log::info("Order #{$orderId} marked as AtWarehouse.");

            return $this->successResponse('Order sent to warehouse successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error sending order #{$orderId}: {$e->getMessage()}");

            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }


    public function sentAllToWarehouse()
    { // php artisan queue:work
        Log::info("Dispatching SendAllNotSentOrdersJob to process all not sent orders.");
        dispatch(new SendAllNotSentOrdersJob());
        return $this->successResponse('All Order Are Sent');
    }


    public function deleteNotSent($orderId)
    {
        Log::info("Attempting to delete not sent order #{$orderId}.");
        $order = Order::where('id', $orderId)->where('status', Order::STATUS_NOT_SENT)->first();
        if (!$order) {
            return $this->errorResponse('Order does not exist or has already been sent.');
        }

        $order->delete();
        Log::info("Order #{$orderId} soft deleted successfully.");

        return $this->successResponse('Order Deleted Successfuly.');
    }
}
