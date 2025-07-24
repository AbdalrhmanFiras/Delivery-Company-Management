<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\OrderLog;
use App\Models\Warehouse;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
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
use App\Http\Requests\AssignWarehouseRequest;
use App\Http\Requests\ValidateWarehouseRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends BaseController
{
    use LogsOrderChanges;
    public function store(StoreOrderRequest $request)
    { //! merchant , Superadmin
        $data =  $request->validated();
        // $merchantId = Auth::user()->merchant->id;
        try {
            $customer = Customer::where('phone', $data['customer_phone'])->first();
            if ($customer) {
                $customerId = $customer->id;
            }
            $order = Order::create([
                //'merchant_id' => $merchant
                'merchant_id' => $data['merchant_id'],
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
            ); //$merchantId
        } catch (\Exception $e) {
            Log::error("Failed to create orders by {}", ['error' => $e->getMessage()]);
            return $this->errorResponse(
                'Unexpected error.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    public function update(UpdateOrderRequest $request, $orderId)
    { //! merchant , Supportadmin
        $merchantId = Auth::user()->merchant->id;
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


    public function destroy($orderId)
    { //! merchant ,  admin
        $merchantId = Auth::user()->merchant->id;
        try {
            $order = Order::id($orderId)
                ->merchantId($merchantId)
                ->firstOrFail();

            $this->logOrderChange($order, 'delete_order', $order->toArray());
            Log::info("Order #{$order->id} deleted by merchant {$merchantId}");
            $order->delete();
            return $this->successResponse('Order Deleted Successfully');
        } catch (ModelNotFoundException) {
            Log::error("Failed to delete order #{$orderId} by merchant {$merchantId}: ");
            return $this->errorResponse('Order not found.', null, 404);
        }
    }


    public function assignOrder(AssignWarehouseRequest $request, $orderId)
    {
        // $warehouseId = Auth::user()->admin->warehouse_id;
        $data = $request->validated();
        $exists = Order::id($orderId)->orderStatus(2)->forCompanyId($data['delivery_company_id'])
            ->warehouseId($data['warehouse_id'])->exists();
        if ($exists) {
            return response()->json('This order has already been assigned to this delivery company.', 400);
        }
        try {
            $order = Order::id($orderId)->orderStatus(1)->warehouseId($data['warehouse_id'])->firstOrFail();
            $order->status = OrderStatus::AssignedDeliveryCompany->value;
            $order->delivery_company_id = $data['delivery_company_id'];
            $order->save();
            $this->logOrderChange($order, 'order_assign_to_delivery_company');

            return $this->successResponse('Order has been Assiged to Delivery Company.');
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }


    public function getAllOrder()
    { //! merchant,admin
        $merchantId = Auth::user()->merchant->id;
        Log::info("merchant {$merchantId} get All his orders");
        return OrderResource::collection(
            Order::merchantId($merchantId)->paginate(20)
        );
    }


    public function getAllCancelOrder(Request $request)
    { //! merchant,admin
        $data = $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        $merchantId = Auth::user()->merchant->id;
        $warehouse = Warehouse::id($data['warehouse_id'])->merchantId($merchantId)->value('name');
        $orders = Order::orderStatus(6)
            ->merchantId($merchantId)
            ->warehouseId($data['warehouse_id'])
            ->latest()
            ->paginate(20);
        if ($orders->isEmpty()) {
            return $this->successResponse('There is no Cancel Orders for ' . $warehouse);
        }
        Log::info("merchant {$merchantId} get All his Cancel orders");
        return $this->successResponse("Orders Cancel is {$orders->count()}", [OrderResource::collection($orders)]);
    }


    public function getSummary(ValidateWarehouseRequest $request)
    {
        $data = $request->validated();
        $merchantId = Auth::user()->merchant->id;
        $warehouse = Warehouse::merchantId($merchantId)->where('id', $data['warehouse_id'])->value('name');
        $totalOrders = Order::merchantId($merchantId)
            ->warehouseId($data['warehouse_id'])
            ->count();

        $deliveredOrders = Order::merchantId($merchantId)
            ->warehouseId($data['warehouse_id'])
            ->orderStatus(5)
            ->count();

        $cancelledOrders = Order::merchantId($merchantId)
            ->warehouseId($data['warehouse_id'])
            ->orderStatus(6)
            ->count();
        Log::info("merchant {$merchantId} get Summary from {$warehouse}");

        return response()->json([
            'total_orders'     => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'cancelled_orders' => $cancelledOrders,
        ]);
    }


    public function getCancelOrder($orderId)
    {
        $merchantId = Auth::user()->merchant->id;

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
            Log::error('Unexpected error while fetching cancelled order', [
                'merchant_id' => $merchantId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('An error occurred. Please try again later.', null, 500);
        }
    }

    public function getAllDelivered()
    {
        $merchantId = Auth::user()->merchant->id;
        $orders = Order::merchantId($merchantId)->orderStatus(5)->latest()->paginate(20);
        if ($orders->isEmpty()) {
            return $this->successResponse('There is no deliverd Orders yet.');
        }
        Log::info("merchant {$merchantId} get All his Delivered order.");
        return OrderResource::collection($orders);
    }


    public function getDeliveredWarehouse(Request $request)
    {
        $data = $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        $merchantId = Auth::user()->merchant->id;
        $warehouse = Warehouse::id($data['warehouse_id'])->merchantId($merchantId)->value('name');
        $orders = Order::merchantId($merchantId)->warehouseId($data['warehouse_id'])->orderStatus(5)->latest()->paginate(20);
        if ($orders->isEmpty()) {
            return $this->successResponse('There is no deliverd Orders for ' . $warehouse);
        }
        Log::info("merchant {$merchantId} get All his Delivered orders from {$warehouse}.");
        return $this->successResponse("Delivered orders for {$warehouse}.", [
            'orders' => OrderResource::collection($orders)
        ]);
    }


    public function getlatestOrders()
    { //! merchant , admin
        $merchantId = Auth::user()->merchant->id;
        Log::info("merchant {$merchantId} get latest orders.");
        return OrderResource::collection(
            Order::merchantId($merchantId)->paginate(20)
        );
    }

    // check it later 
    public function show($orderId)
    {
        try {
            $user = Auth::user();

            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId && !$user->hasRole('admin') && !$user->hasRole('supportadmin')) {
                Log::warning("Unauthorized attempt to access order {$orderId} by user {$user->id}");
                return $this->errorResponse('Unauthorized.', 403);
            }

            $query = Order::id($orderId);

            if ($merchantId) {
                $query->merchantId($merchantId);
            }

            $order = $query->firstOrFail();

            Log::info("User {$user->id} viewed order {$orderId}.");

            return $this->successResponse('Order details.', new OrderResource($order));
        } catch (ModelNotFoundException $e) {
            Log::warning("Order not found", [
                'user_id' => Auth::id(),
                'order_id' => $orderId
            ]);
            return $this->errorResponse('Order not found.', 404);
        } catch (\Throwable $e) {
            Log::error("Failed to retrieve order {$orderId}: " . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return $this->errorResponse('Unexpected error occurred.');
        }
    }
}
