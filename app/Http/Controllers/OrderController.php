<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\OrderLog;
use App\Models\Warehouse;
use App\Enums\OrderStatus;
use App\Http\Requests\AdminOrderAccessRequest;
use App\Http\Requests\AdminSupportAccessRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Empty_;
use App\Traits\LogsOrderChanges;
use Illuminate\Http\JsonResponse;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\MerchantAccessRequest;
use App\Http\Requests\AssignWarehouseRequest;
use App\Http\Requests\ShowAccessRequest;
use App\Http\Requests\ValidateWarehouseRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends BaseController
{
    use LogsOrderChanges;
    public function store(StoreOrderRequest $request)
    { //*
        $merchantId = $request->getMerchantId();
        $data =  $request->validated();
        try {
            $customer = Customer::where('phone', $data['customer_phone'])->first();
            if ($customer) {
                $customerId = $customer->id;
            }

            if (!Warehouse::where('id', $data['warehouse_id'])->merchantId($merchantId)->exists()) {
                return $this->errorResponse('Warehouse not found', null, 404);
            }

            $order = Order::create([
                'merchant_id' => $merchantId,
                'total_price' => $data['total_price'],
                'customer_id' => $customerId ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'] ?? null,
                'warehouse_id' => $data['warehouse_id']
            ]);
            Log::info("Order #{$order->id} created by merchant {$order->merchant_id}");

            $this->logOrderChange($order, 'create_order');
            return $this->successResponse(
                'Order Created Successfully',
                [
                    'order' => new OrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to create orders by {$merchantId}", ['error' => $e->getMessage()]);
            return $this->errorResponse(
                'Unexpected error.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    public function update(UpdateOrderRequest $request, $orderId)
    { //*
        $merchantId = $request->getMerchantId();
        $data = $request->validated();
        try {
            $order = Order::id($orderId)->merchantId($merchantId)->orderStatus(1)->firstOrFail();
            $originalData = $order->toArray();
            $updated = $order->update($data);
            if (!$updated) {
                return $this->errorResponse('Failed to update order.', null, 422);
            }
            Log::info("Order #{$order->id} updated by merchant {$order->merchant_id}");
            $this->logOrderChange($order, 'order_update', $originalData, $order->toArray());
            return $this->successResponse(
                'Order Updated Successfully',
                [
                    'order' => new OrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            Log::error("Order update failed for order #{$orderId} by merchant {$merchantId}: " . $e->getMessage());
            return $this->errorResponse(
                'Unexpected error.',
                [
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function destroy(MerchantAccessRequest $request, $orderId)
    { //*
        $merchantId = $request->getMerchantId();
        try {
            $order = Order::id($orderId)
                ->merchantId($merchantId)
                ->firstOrFail();

            if ($order->status !== OrderStatus::Cancelled->value) {
                return $this->errorResponse('Only cancelled orders can be deleted.', null, 403);
            }
            $this->logOrderChange($order, 'delete_order', $order->toArray());
            Log::info("Order #{$order->id} deleted by merchant {$merchantId}");
            $order->delete();
            return $this->successResponse('Order Deleted Successfully');
        } catch (ModelNotFoundException) {
            Log::error("Failed to delete order #{$orderId} by merchant {$merchantId}: ");
            return $this->errorResponse('Order not found.', null, 404);
        }
    }


    public function getAllOrder(AdminOrderAccessRequest $request)
    { //*
        $merchantId = $request->getMerchantId();
        Log::info("merchant {$merchantId} get All his orders");
        $orders = Order::merchantId($merchantId)->latest()->paginate(20);
        if (empty($orders->count())) {
            return $this->errorResponse('there is no orders', null, 404);
        }
        return $this->successResponse('Orders', [OrderResource::collection($orders)]);
    }


    public function getAllCancelOrder(AdminSupportAccessRequest $request)
    { //*

        $merchantId = $request->getMerchantId();
        $warehouse = Warehouse::merchantId($merchantId)->value('name');
        $orders = Order::orderStatus(6)
            ->merchantId($merchantId)
            ->latest()
            ->paginate(20);
        if ($orders->isEmpty()) {
            return $this->errorResponse('There is no Cancel Orders in ', null, 404);
        }
        Log::info("merchant {$merchantId} get All his Cancel orders");
        return $this->successResponse("Orders Cancel is {$orders->count()}", [OrderResource::collection($orders)]);
    }


    public function getSummary(MerchantAccessRequest $request, $warehouseId)
    { //*
        $merchantId = $request->getMerchantId();
        $warehouse = Warehouse::merchantId($merchantId)->where('id', $warehouseId)->value('name');
        $totalOrders = Order::merchantId($merchantId)
            ->warehouseId($warehouseId)
            ->count();

        $deliveredOrders = Order::merchantId($merchantId)
            ->warehouseId($warehouseId)
            ->orderStatus(5)
            ->count();

        $cancelledOrders = Order::merchantId($merchantId)
            ->warehouseId($warehouseId)
            ->orderStatus(6)
            ->count();
        Log::info("merchant {$merchantId} get Summary from {$warehouse}");

        return response()->json([
            'total_orders'     => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'cancelled_orders' => $cancelledOrders,
        ]);
    }
    public function getSummaryAll(MerchantAccessRequest $request)
    { //*
        $merchantId = $request->getMerchantId();
        $totalOrders = Order::merchantId($merchantId)
            ->count();

        $deliveredOrders = Order::merchantId($merchantId)
            ->orderStatus(5)
            ->count();

        $cancelledOrders = Order::merchantId($merchantId)
            ->orderStatus(6)
            ->count();
        Log::info("merchant {$merchantId} get Summary of All orders");

        return response()->json([
            'total_orders'     => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'cancelled_orders' => $cancelledOrders,
        ]);
    }


    public function getCancelOrder(MerchantAccessRequest $request, $orderId)
    { //*
        $merchantId = $request->getMerchantId();

        try {
            $order = Order::id($orderId)
                ->orderStatus(6)
                ->merchantId($merchantId)
                ->firstOrFail();

            Log::info('Cancel order fetched successfully', [
                'merchant_id' => $merchantId,
                'order_id' => $order->id
            ]);

            return $this->successResponse("Cancelled order retrieved", new OrderResource($order));
        } catch (ModelNotFoundException $e) {
            Log::warning('Cancelled order not found', [
                'merchant_id' => $merchantId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('This order not found.', null, 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error while fetching cancelled order', ['error' => $e->getMessage()]);

            return $this->errorResponse('An error occurred. Please try again later.', null, 500);
        }
    }

    public function getAllDelivered(MerchantAccessRequest $request)
    { //*
        $merchantId = $request->getMerchantId();
        $orders = Order::merchantId($merchantId)->orderStatus(5)->latest()->paginate(20);
        if ($orders->isEmpty()) {
            return $this->errorResponse('There is no deliverd Orders yet.', null, 404);
        }
        Log::info("merchant {$merchantId} get All his Delivered order.");
        return OrderResource::collection($orders);
    }


    public function getDeliveredWarehouse(MerchantAccessRequest $request, $warehouseId)
    { //*
        $merchantId = $request->getMerchantId();
        $warehouse = Warehouse::id($warehouseId)->merchantId($merchantId)->value('name');
        $orders = Order::merchantId($merchantId)->warehouseId($warehouseId)->orderStatus(5)->latest()->paginate(20);
        if ($orders->isEmpty()) {
            return $this->errorResponse('There is no deliverd Orders for ' . $warehouse, null, 404);
        }
        Log::info("merchant {$merchantId} get All his Delivered orders from {$warehouse}.");
        return $this->successResponse("Delivered orders for {$warehouse}.", [
            'orders' => OrderResource::collection($orders)
        ]);
    }


    public function getCancelledWarehouse(MerchantAccessRequest $request, $warehouseId)
    { //*
        $merchantId = $request->getMerchantId();
        $warehouse = Warehouse::id($warehouseId)->merchantId($merchantId)->value('name');
        $orders = Order::merchantId($merchantId)->warehouseId($warehouseId)->orderStatus(6)->latest()->paginate(20);
        if ($orders->isEmpty()) {
            return $this->errorResponse('There is no cancel Orders for ' . $warehouse, null, 404);
        }
        Log::info("merchant {$merchantId} get All his Cancel orders from {$warehouse}.");
        return $this->successResponse("Delivered orders for {$warehouse}.", [
            'orders' => OrderResource::collection($orders)
        ]);
    }


    public function getlatestOrders(MerchantAccessRequest $request)
    { //*
        $merchantId = $request->getMerchantId();
        Log::info("merchant {$merchantId} get latest orders.");
        $orders = Order::merchantId($merchantId)->latest()->paginate(20);
        if (empty($orders->count())) {
            return $this->errorResponse('there is no latest orders', null, 404);
        }

        return $this->successResponse('orders', [OrderResource::collection($orders)]);
    }

    public function show(ShowAccessRequest $request, $orderId)
    { //*
        try {
            $merchantId = $request->getMerchantId();
            $order = Order::id($orderId)->firstOrFail();
            return $this->successResponse('Order details.', new OrderResource($order));
        } catch (ModelNotFoundException) {
            Log::warning("Order not found", [
                'user_id' => Auth::id(),
                'order_id' => $orderId
            ]);
            return $this->errorResponse('Order not found.', 404);
        } catch (\Exception $e) {
            Log::error("Failed to retrieve order {$orderId}: " . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return $this->errorResponse('Unexpected error occurred.');
        }
    }
}
