<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use App\Jobs\ReceiveMerchantOrdersJob;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\WarehouseOrderResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WarehouseOrderController extends BaseController
{


    public function receiveOrder(Request $request, $orderId)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        Log::info("Warehouse employee receiving order #{$orderId}.");
        // $check = WarehouseReceipts::orderId($orderId)->first();
        $exists = WarehouseReceipts::orderId($orderId)->exists();
        if ($exists) {
            return $this->successResponse('This Order Had Already Accepted');
        }
        try {
            DB::beginTransaction();
            $order = Order::Id($orderId)->orderStatus(1)->where('warehouse_id', $warehouseId)->firstOrFail();
            $orderReceipts = WarehouseReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'received_at' => now()
            ]);
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (\Exception $e) {
            Log::error("order not found with ID {$orderId} ");
            DB::rollBack();
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }


    public function receiveAllMerchantOrders($merchantId)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        Log::info("Receiving all orders for merchant #{$merchantId} at warehouse #{1}");
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);
            $orders = Order::merchantId($merchantId)
                ->where('warehouse_id', $warehouseId)
                ->whereNotIn('id', function ($q) {
                    $q->select('order_id')->from('warehouse_receipts');
                })
                ->orderStatus(1)
                ->latest()
                ->paginate(20);

            if ($orders->isEmpty()) {
                return $this->successResponse('No pending orders to receive for this merchant.');
            }

            foreach ($orders as $order) {
                WarehouseReceipts::create([
                    'order_id' => $order->id,
                    'received_by' => $order->merchant->user_id,
                    'received_at' => now(),
                ]);

                DB::commit();
                return $this->successResponse("Received {$orders->count()} orders successfully.", [OrderResource::collection($orders)]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to receive merchant orders #{$merchantId}", ['error' => $e->getMessage()]);
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }


    public function receiveAllMerchantOrdersAuto($merchantId)
    { //?
        try {
            $warehouseId = Auth::user()->employee->warehouse_id;
            $merchant = Merchant::findOrFail($merchantId);
            ReceiveMerchantOrdersJob::dispatch($merchantId, $warehouseId);
            return $this->successResponse("Receiving merchant orders started in background job.");
        } catch (\Exception $e) {
            Log::error("Failed to dispatch receive orders job for merchant #{$merchantId}", ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to start receiving orders.', ['error' => $e->getMessage()]);
        }
    }

    public function getAllMerchantOrderBeforeAccepet($merchantId)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        Log::info("Receiving all orders for merchant #{$merchantId} at warehouse #{$warehouseId}"); // warehouse value 
        try {
            $merchant = Merchant::findOrFail($merchantId);

            $orders = Order::merchantId($merchantId)
                ->where('warehouse_id', $warehouseId)
                ->whereNotIn('id', function ($q) {
                    return $q->select('order_id')->from('warehouse_receipts');
                })
                ->orderStatus(1)
                ->paginate(20);

            if ($orders->isEmpty()) {
                Log::error("Failed to receive merchant orders #{$merchantId}");
                return $this->successResponse('No pending orders to receive for this merchant.');
            }

            return OrderResource::collection($orders);
        } catch (ModelNotFoundException) {
            Log::error("Failed to find merchant #{$merchantId}");
            return $this->errorResponse('no merchant found with this ID', null, 404);
        }
    }



    public function getOrder($orderId)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        try {
            $order = Order::id($orderId)->orderStatus(1)->where('warehouse_id', $warehouseId)->whereHas('warehouseReceipts')->firstorFail();
            return new OrderResource($order);
        } catch (ModelNotFoundException $e) {
            Log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Order Not Found', null, 404);
        }
    }


    public function getAllOrder()
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        return OrderResource::collection(Order::orderStatus(1)->where('warehouse_id', $warehouseId)->whereHas('warehouseReceipts')->orderBy('id')->cursorPaginate(20));
    }


    public function getAllMerchantOrder($merchantid)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        return OrderResource::collection(Order::merchantId($merchantid)->where('warehouse_id', $warehouseId)->orderStatus(1)->whereHas('warehouseReceipts')->paginate(20));
    }


    public function assignOrder(Request $request, $orderId)
    {
        $warehouseId = Auth::user()->employee->warehouse_id;
        $data = $request->validate(['delivery_company_id' => 'required|uuid|exists:delivery_companies,id']);
        $exists = Order::id($orderId)->orderStatus(2)->where('delivery_company_id', $data['delivery_company_id'])
            ->where('warehouse_id', $warehouseId)->exists();
        if ($exists) {
            return response()->json('This order has already been assigned to this delivery company.', 400);
        }

        try {
            $order = Order::id($orderId)->orderStatus(1)->where('warehouse_id', $warehouseId)->firstOrFail();
            $order->status = OrderStatus::AssignedDeliveryCompany->value;
            $order->delivery_company_id = $data['delivery_company_id'];
            $order->save();
            $this->logOrderChange($order, 'order_status_update');

            return $this->successResponse('Order has been Assiged to Delivery Company.');
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }
}
