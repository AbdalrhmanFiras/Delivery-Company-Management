<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
        $merchantId = Auth::user()->merchant->id;
        Log::info("Merchant is sending order #{$orderId} to warehouse.");
        $check = Order::Id($orderId)->merchantId($merchantId)->where('upload', Order::STATUS_SENT)->first();
        if ($check) {
            return response()->json('This order was already sent to the warehouse.');
        }
        try {
            DB::beginTransaction();
            $order = Order::Id($orderId)
                ->merchantId($merchantId)
                ->orderStatus(0)
                ->firstOrFail();
            $order->status = OrderStatus::AtWarehouse->value;
            $order->upload = Order::STATUS_SENT;
            $order->warehouse_id = $order->merchant->warehouse_id;
            $order->save();
            DB::commit();
            $this->logOrderChange($order, 'order_status_update');
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
        return $this->successResponse('All Order Are Sent.');
    }


    public function delete($orderId)
    {
        $merchantId = Auth::user()->merchant->id;
        Log::info("Attempting to delete not sent order #{$orderId}.");
        $order = Order::Id($orderId)->merchantId($merchantId)->where('status', Order::STATUS_NOT_SENT)->first();
        if (!$order) {
            return $this->errorResponse('Order does not exist or has already been sent.');
            Log::error("order not found with ID {$orderId} ");
        }
        Log::info("Order #{$orderId} soft deleted successfully.");
        $order->delete();
        return $this->successResponse('Order Deleted Successfuly.');
    }
}
