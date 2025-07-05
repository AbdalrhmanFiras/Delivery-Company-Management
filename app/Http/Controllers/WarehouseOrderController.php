<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\WarehouseOrderResource;

class WarehouseOrderController extends BaseController
{

    public function sendToWarehouse(Request $request, $orderId)
    {
        Log::info("Attempting to send order #{$orderId} to warehouse.");

        $check = WarehouseReceipts::where('order_id', $orderId)->first();
        if ($check) {
            return $this->successResponse('This Order Had Already Sent');
        }
        try {

            DB::beginTransaction();
            Log::info("Order #{$orderId} validated data.");
            $order = Order::findOrFail($orderId);
            $order->status = 1;
            $order->upload = 'sent';
            $order->warehouse_id = $order->merchant->warehouse_id;
            $order->save();
            $orderReceipts = WarehouseReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'received_at' => now()
            ]);
            DB::commit();
            Log::info("Warehouse receipt created for order #{$orderId}.");


            return $this->successResponse(
                'Order pushed to warehouse successfully.',
                ['receipt' => new WarehouseOrderResource($orderReceipts)]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }
}
