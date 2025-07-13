<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Driver;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\AutoAssignDriverEvent;
use App\Http\Resources\OrderResource;
use App\Models\DeliveryCompanyReceipts;
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
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Order not found or not available for this company.', null, 404);
        } catch (\Exception $e) {
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
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Order not found.', null, 404);
        } catch (\Exception $e) {
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
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Order Not Found', null, 404);
        }
    }


    public function getAllOrder()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;

        $orders = Order::forCompanyId($deliveryCompanyId)
            ->where('status', 2)
            ->paginate(20);

        return OrderResource::collection($orders);
    }


    public function getLatestOrder()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->where('status', 2)
            ->latest()->paginate(20);

        return OrderResource::collection($orders);
    }


    public function getOrderAssign()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->orderStatus(3)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getOutDelivery()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->where('status', 4)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getDelivered()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($deliveryCompanyId)
            ->where('status', 5)
            ->get();

        return OrderResource::collection($orders);
    }


    public function filterOrders(Request $request)
    {
        $query = Order::query();

        $companyId = Auth::user()->employee->delivery_company_id;
        $query->where('delivery_company_id', $companyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        return OrderResource::collection($query->latest()->paginate(25));
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
