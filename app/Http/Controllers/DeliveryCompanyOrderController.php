<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;
use App\Models\DeliveryCompanyReceipts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class DeliveryCompanyOrderController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth:jwt', 'employee.delivery']);
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
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function assignOrderDriver(Request $request, $orderId)
    {
        $data = $request->validate(['driver_id' => 'required|uuid|exists:drivers,id']);
        try {
            $employee = Auth::user();
            $order = Order::id($orderId)
                ->orderStatus(2)
                ->where('delivery_company_id', $employee->employee->delivery_company_id)
                ->firstOrFail();
            $order->status = OrderStatus::AssignedDriver->value;
            $order->driver_id = $data['driver_id'];
            $order->save();

            return $this->successResponse('Order assigned to driver.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Order not found or not available for this company.', null, 404);
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
            $order = Order::where('delivery_company_id', $referenceOrder->delivery_company_id)->where('warehouse_id', $referenceOrder->warehouse_id)
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

        $orders = Order::where('delivery_company_id', $deliveryCompanyId)
            ->where('status', 2)
            ->paginate(20);

        return OrderResource::collection($orders);
    }


    public function getLatestOrder()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::where('delivery_company_id', $deliveryCompanyId)
            ->where('status', 2)
            ->latest()->paginate(20);

        return OrderResource::collection($orders);
    }


    public function getaAssignDriver()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::where('delivery_company_id', $deliveryCompanyId)
            ->where('status', 3)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getOutDelivery()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::where('delivery_company_id', $deliveryCompanyId)
            ->where('status', 4)
            ->get();

        return OrderResource::collection($orders);
    }


    public function getdeliverd()
    {
        $deliveryCompanyId = Auth::user()->employee->delivery_company_id;
        $orders = Order::where('delivery_company_id', $deliveryCompanyId)
            ->where('status', 5)
            ->get();

        return OrderResource::collection($orders);
    }
}
