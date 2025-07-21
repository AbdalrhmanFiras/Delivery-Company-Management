<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Models\DriverReceipts;
use Illuminate\Validation\Rule;
use App\Traits\LogsOrderChanges;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\CancelDriverOrderRequest;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DriverController extends BaseController
{
    use LogsOrderChanges;

    public function Login(LoginRequest $request)
    {
        $data = $request->validated();
        try {
            if (!$token = JWTAuth::attempt($data)) {
                return $this->errorResponse('Invaild credentials', null, 401);
            }
        } catch (JWTException $e) {
            return $this->errorResponse('token creation faild', $e->getMessage(), 500);
        }
        $user = Auth::user();
        if ($user->user_type !== 'driver') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $this->successResponse(
            'Login successful',
            [
                'user' => new UserResource($user->load($user->user_type)),
                'token' => $token
            ]
        );
    }


    public function receiveOrder(Request $request, $orderId)
    {
        $user = Auth::user();
        $exists = DriverReceipts::orderId($orderId)->forCompanyId($user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->exists();
        if ($exists) {
            return response()->json('This order Already been received.', 401);
        }
        try {
            DB::beginTransaction();
            $order = Order::id($orderId)->orderStatus(3)->forCompanyId($user->driver->delivery_company_id)
                ->where('driver_id', $user->driver->id)->firstOrFail();
            $orderReceipts = DriverReceipts::create([
                'order_id' => $order->id,
                'driver_id' => $user->driver->id,
                'delivery_company_id' => $user->driver->delivery_company_id,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (ModelNotFoundException $e) {
            log::error("order not found with ID {$orderId} ");
            return $this->errorResponse('Driver not found.',  404);
        }
    }


    public function notAvailable()
    {
        $driver = Auth::user()?->driver;
        if (!$driver) {
            return $this->errorResponse('No driver profile found.', 404);
        }
        $driver->available = 0;
        $driver->save();
        return $this->successResponse('you are Not Available now.', [
            'available' => $driver->available,
        ]);
    }


    public function assignOutDelivery($orderId)
    {
        $user = Auth::user();
        $order  = Order::id($orderId)
            ->forCompanyId($user->driver->delivery_company_id)
            ->orderStatus(3)
            ->where('driver_id', $user->driver->id)
            ->firstOrFail();
        DB::beginTransaction();
        try {
            $order->status = OrderStatus::OutForDelivery->value;
            $driver = $user->driver;
            $driver->available = false;
            $driver->save();
            $order->save();
            $this->logOrderChange($order, 'order_out_delivered');

            DB::commit();
            return $this->successResponse('Order Assign out for Delivery.');
        } catch (ModelNotFoundException) {
            log::error("order not found with ID {$orderId} ");
            DB::rollBack();
            return $this->errorResponse('Failed to assign out for delivery.', null, 500);
        }
    }


    public function assignDelivery($orderId)
    {
        $user = Auth::user();
        $order  = Order::id($orderId)
            ->forCompanyId($user->driver->delivery_company_id)
            ->orderStatus(4)
            ->where('driver_id', $user->driver->id)
            ->firstOrFail();
        DB::beginTransaction();
        try {
            $order->status = OrderStatus::Delivered->value;
            $driver = $user->driver;
            $driver->available = true;
            $order->delivered_at = now();

            $driver->save();
            $order->save();
            $this->logOrderChange($order, 'order_Assign_delivered');

            DB::commit();
            return $this->successResponse('Order Assign to Deliverd');
        } catch (ModelNotFoundException $e) {
            log::error("order not found with ID {$orderId} ");
            DB::rollBack();
            return $this->errorResponse('Failed to assign delivered.', $e->getMessage(), 500);
        }
    }


    public function searchByTrackNumber(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string'
        ]);
        $order = Order::forCompanyId(Auth::user()->driver->delivery_company_id)
            ->where('tracking_number', $data['tracking_number'])
            ->first();
        if (!$order) {
            return $this->errorResponse('Order not found.', null, 404);
        }
        return new OrderResource($order);
    }


    public function getOrderSummery()
    {
        $user = Auth::user();
        $driverId = $user->driver->id;
        $deliveryId = $user->driver->delivery_company_id;

        return response()->json([
            'Assign' =>  Order::forCompanyId($deliveryId)->where('driver_id', $driverId)->orderStatus(3)->count(),
            'Out' =>     Order::forCompanyId($deliveryId)->where('driver_id', $driverId)->orderStatus(4)->count(),
            'Deliverd' => Order::forCompanyId($deliveryId)->where('driver_id', $driverId)->orderStatus(5)->count(),
            'Cancel' =>  Order::forCompanyId($deliveryId)->where('driver_id', $driverId)->orderStatus(6)->count(),
        ]);
    }


    public function assignCancel(CancelDriverOrderRequest $request, $orderId)
    {
        $data = $request->validated();
        $reason = $request->input('cancel_reason') ?: $request->input('preset_reason', 'no reason provided');
        $user = Auth::user();
        $order  = Order::id($orderId)->forCompanyId($user->driver->delivery_company_id)->whereBetween('status', [OrderStatus::AssignedDriver->value, OrderStatus::OutForDelivery->value])->where('driver_id', $user->driver->id)
            ->firstOrFail();

        $order->status = OrderStatus::Cancelled->value;
        $order->save();
        $this->logOrderChange($order, 'order_cancelled');

        return $this->successResponse('Order Cancelled', $reason);
    }


    public function getRating()
    {
        $ratings = Driver::id(Auth::user()->driver->id)->pluck('rating');
        if ($ratings->isEmpty()) {
            return response()->json(['message' => 'There is no rating yet.']);
        }
        return response()->json(['Rating' => $ratings]);
    }


    public function countRating()
    {
        $ratings = Driver::id(Auth::user()->driver->id)->pluck('rating');
        $group = [
            'low' => $ratings->filter(fn($r) => $r >= 1 && $r <= 2)->count(),
            'mid' => $ratings->filter(fn($r) => $r >= 3 && $r <= 4)->count(),
            'high' => $ratings->filter(fn($r) => $r == 5)->count(),
        ];
        return response()->json(['Summery' => $group]);
    }


    public function getOrders()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(3)->forCompanyId($user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    public function getDeliverd()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(5)->forCompanyId($user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    public function getCancel()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(6)->forCompanyId($user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    public function getOutForDelivery()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(4)->forCompanyId($user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }
}
