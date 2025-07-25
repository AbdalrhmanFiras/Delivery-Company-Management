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
use App\Http\Requests\AssignDriverRequest;
use App\Http\Requests\OrderFiltersRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeliveryCompanyOrderController extends BaseController
{ //test role premission 
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
            $order = Order::id($orderId)->orderStatus(1)->firstOrFail();
            $orderReceipts = DeliveryCompanyReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant ? $order->merchant->user_id : null,
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
        $companyId = Auth::user()->employee->delivery_company_id;
        $lateOrdersCount = Order::where('driver_id', $driverId)
            ->forCompanyId($companyId)
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


    public function assignDriver(AssignDriverRequest $request, $orderId)
    {
        $data = $request->validated();
        try {
            $companyId = Auth::user()->employee->delivery_company_id;
            $order = Order::id($orderId)
                ->orderStatus(2)
                ->forCompanyId($companyId)
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
            $companyId = Auth::user()->employee->delivery_company_id;
            $order = Order::id($orderId)
                ->forCompanyId($companyId)
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
            $companyId = Auth::user()->employee->delivery_company_id;

            $order = Order::id($orderId)
                ->forCompanyId($companyId)
                ->orderStatus(2)
                ->firstOrFail();

            return new OrderResource($order);
        } catch (ModelNotFoundException) {
            Log::error("order not found with ID {$orderId}");
            return $this->errorResponse('Order Not Found', null, 404);
        }
    }


    public function getAllOrder()
    {
        $CompanyId = Auth::user()->employee->delivery_company_id;

        $orders = Order::forCompanyId($CompanyId)
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
        $CompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($CompanyId)
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
        $CompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($CompanyId)
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
        $CompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($CompanyId)
            ->orderStatus(4)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getDelivered()
    {
        $CompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::forCompanyId($CompanyId)
            ->orderStatus(5)
            ->get();

        return OrderResource::collection($orders);
    }


    public function filterOrders(OrderFiltersRequest $request)
    {
        $data = $request->validated();
        $CompanyId = Auth::user()->employee->delivery_company_id;
        $filters = [
            'delivery_company_id' => $CompanyId,
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
        $CompanyId = Auth::user()->employee->delivery_company_id;

        return response()->json([
            'total_orders' => Order::forCompanyId($CompanyId)->count(),
            'assigned' => Order::forCompanyId($CompanyId)->orderStatus(3)->count(),
            'out_for_delivery' => Order::forCompanyId($CompanyId)->orderStatus(4)->count(),
            'delivered' => Order::forCompanyId($CompanyId)->orderStatus(5)->count(),
            'cancelled' => Order::forCompanyId($CompanyId)->orderStatus(6)->count(),
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
