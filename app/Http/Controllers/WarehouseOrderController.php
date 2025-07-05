<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WarehouseOrderResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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




    public function getOrder($orderId)
    {
        try {
            $order = Order::id($orderId)->orderStatus(1)->whereHas('warehouseReceipts')->firstorFail();
            return new OrderResource($order);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Order Not Found', null, 404);
        }
    }


    public function getAllOrder()
    {
        return OrderResource::collection(Order::orderStatus(1)->whereHas('warehouseReceipts')->paginate(20));
    }


    public function getAllMerchantOrder($merchantid)
    {
        return OrderResource::collection(Order::merchantId($merchantid)->orderStatus(1)->whereHas('warehouseReceipts')->paginate(20));
    }
}
