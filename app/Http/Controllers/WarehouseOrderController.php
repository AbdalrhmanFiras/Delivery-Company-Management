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
    public function receiveOrder(Request $request, $orderId)
    {
        Log::info("Warehouse employee receiving order #{$orderId}.");

        $check = WarehouseReceipts::orderId($orderId)->first();
        if ($check) {
            return $this->successResponse('This Order Had Already Accepted');
        }
        try {
            DB::beginTransaction();
            $order = Order::Id($orderId)->orderStatus(1)->firstOrFail();
            $orderReceipts = WarehouseReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'received_at' => now()
            ]);
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }
}
