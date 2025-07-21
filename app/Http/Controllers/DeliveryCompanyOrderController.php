<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Driver;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\AutoAssignDriverEvent;
use App\Http\Resources\OrderResource;
use App\Models\DeliveryCompanyReceipts;
use App\Http\Requests\OrderFiltersRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeliveryCompanyOrderController extends BaseController
{
    use LogsOrderChanges;

    public function __construct()
    {
        $this->middleware(['auth:api', 'employee.delivery']);
    } //auth:jwt // employee role and premission 


    public function receiveOrder(Request $request, $orderId)
    {
        $exists = DeliveryCompanyReceipts::orderId($orderId)->exists();
        if ($exists) {
            return response()->json('This order Already been received.', 401);
        }
        try {
            DB::beginTransaction();
            $order = Order::id($orderId)->orderStatus(2)->firstOrFail();
            $orderReceipts = DeliveryCompanyReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'warehouse_id' => $order->warehouse_id,
                'received_at' => now(),
            ]);
            $order->expected_delivery_time = now()->addHours(12);
            $order->save();
            $this->logOrderChange($order, 'order_time_update');
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (\Exception $e) {
            Log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }

    public function getLateOrders($driverId)
    {
        $user = Auth::user();
        $deliveryCompanyId = $user->employee->delivery_company_id;

        $lateOrdersCount = Order::where('driver_id', $driverId)
            ->forCompanyId($deliveryCompanyId)
            ->where('status', OrderStatus::Delivered->value)
            ->whereNotNull('expected_delivery_time')
            ->whereNotNull('delivered_at')
            ->whereColumn('delivered_at', '>', 'expected_delivery_time')
            ->count();

        return response()->json([
            'driver_id' => $driverId,
            'late_deliveries' => $lateOrdersCount
        ]);
    }


    public function assignOrderDriver(Request $request, $orderId)
    {
        $data = $request->validate(['driver_id' => 'required|uuid|exists:drivers,id']);
        try {
            $employee = Auth::user();
            $order = Order::id($orderId)
                ->orderStatus(2)
                ->forCompanyId($employee->employee->delivery_company_id)
                ->firstOrFail();
            $order->status = OrderStatus::AssignedDriver->value;
            $order->driver_id = $data['driver_id'];
            $order->save();
            $this->logOrderChange($order, 'order_assign_driver');
            return $this->successResponse('Order assigned to driver.');
        } catch (ModelNotFoundException) {
            Log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Order not found or not available for this company.', null, 404);
        } catch (\Exception $e) {
            Log::error("order not found with ID {$orderId}  , {$e->getMessage()}");
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function autoAssignDriver($orderId)
    {
        try {
            $order = Order::id($orderId)
                ->forCompanyId(Auth::user()->employee->delivery_company_id)
                ->orderStatus(2)
                ->firstOrFail();
            event(new AutoAssignDriverEvent($order));
        } catch (ModelNotFoundException) {
            Log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Order not found.', null, 404);
        } catch (\Exception $e) {
            Log::error("order not found with ID {$orderId}  , {$e->getMessage()}");
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getOrder($orderId)
    {
        try {
            $referenceOrder = Order::id($orderId)
                ->orderStatus(2)
                ->firstOrFail();
            $order = Order::forCompanyId($referenceOrder->delivery_company_id)->where('warehouse_id', $referenceOrder->warehouse_id)
                ->orderStatus(2)
                ->first();
            return new OrderResource($order);
        } catch (ModelNotFoundException) {
            Log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Order Not Found', null, 404);
        }
    }


    public function getAllOrder()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;

        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(2)->orderByDesc('id')
            ->cursorPaginate(25);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'next_cursor' => $orders->nextCursor()?->encode(),
            'prev_cursor' => $orders->previousCursor()?->encode(),
        ]);
    }


    public function getLatestOrder()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(2)
            ->latest()->cursorPaginate(20);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'next_cursor' => $orders->nextCursor()?->encode(),
            'prev_cursor' => $orders->previousCursor()?->encode(),
        ]);
    }


    public function getOrderAssign()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(3)->orderBy('id')
            ->cursorPaginate(20);
        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'next_cursor' => $orders->nextCursor()?->encode(),
            'prev_cursor' => $orders->previousCursor()?->encode(),
        ]);
    }


    public function getOutDelivery()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(4)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getDelivered()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(5)
            ->get();

        return OrderResource::collection($orders);
    }


    public function filterOrders(OrderFiltersRequest $request)
    {
        $data = $request->validated();
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $filters = [
            'delivery_company_id' => $deliveryCompanyId,
            'status' => $data['status'] ?? null
        ];

        $orders = Order::orderfilters($filters)->latest()->paginate(25);

        return OrderResource::collection($orders);
    }


    public function cancelOrder(Request $request, $orderId)
    {
        $order = Order::id($orderId)
            ->forCompanyId(Auth::user()->employee->delivery_company_id)
            ->firstOrFail();

        $order->status = OrderStatus::Cancelled->value;
        $order->save();

        return $this->successResponse('Order cancelled successfully.');
    }


    public function getSummary()
    {
        $companyId = Auth::user()->employee->delivery_company_id;

        return response()->json([
            'total_orders' => Order::where('delivery_company_id', $companyId)->count(),
            'assigned' => Order::where('delivery_company_id', $companyId)->orderStatus(3)->count(),
            'out_for_delivery' => Order::where('delivery_company_id', $companyId)->orderStatus(4)->count(),
            'delivered' => Order::where('delivery_company_id', $companyId)->orderStatus(5)->count(),
            'cancelled' => Order::where('delivery_company_id', $companyId)->orderStatus(6)->count(),
        ]);
    }


    public function searchByTrackNumber(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string'
        ]);
        $order = Order::forCompanyId(Auth::user()->employee->delivery_company_id)
            ->where('tracking_number', $data['tracking_number'])
            ->first();
        if (!$order) {
            return $this->errorResponse('Order not found.', null, 404);
        }
        return new OrderResource($order);
    }
}
