<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Models\DriverFeedback;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\CustomerOrderRequest;
use App\Http\Requests\SubmitFeedbackRequest;
use App\Http\Resources\CustomerOrderResource;
use App\Http\Requests\CustomerOrderTrackrRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerController extends BaseController
{
    public function login(Request $request)
    {
        $credentials = $request->only('phone');

        $user = User::where('phone', $credentials['phone'])->first();

        if (!$user) {
            return $this->errorResponse('number not found', null, 404);
        }

        $token = JWTAuth::fromUser($user);

        return $this->successResponse('Otp code has Send to your phone', null);
    }


    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
    }

    public function trackOrder(CustomerOrderTrackrRequest $request)
    {
        $data = $request->validated();
        $order = Order::where('tracking_number', $data['tracking_number'])
            ->phone($data['phone'])
            ->first();

        if (!$order) {
            return $this->errorResponse('There is no orders', null, 404);
        }
        return new CustomerOrderResource($order);
    }


    public function getOrders(CustomerOrderRequest $request)
    {
        $data = $request->validated();
        $orders = Order::phone($data['phone'])
            ->latest()->paginate(10);

        if ($orders->isEmpty()) {
            return $this->errorResponse('No active orders found.', null, 404);
        }
        return  CustomerOrderResource::collection($orders);
    }


    public function getCurrentOrders(CustomerOrderRequest $request)
    {
        $data = $request->validated();
        $orders = Order::phone($data['phone'])
            ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::AtWarehouse->value, OrderStatus::OutForDelivery->value])
            ->latest()->paginate(10);

        if ($orders->isEmpty()) {
            return $this->errorResponse('No active orders found.', null, 404);
        }

        return CustomerOrderResource::collection($orders);
    }


    public function getCompeleteOrders(CustomerOrderRequest $request)
    {
        $data = $request->validated();
        $orders = Order::orderStatus(5)
            ->phone($data['phone'])
            ->latest()->paginate(10);
        if ($orders->isEmpty()) {
            return $this->errorResponse('No active orders found.', null, 404);
        }
        return  CustomerOrderResource::collection($orders);
    }


    public function cancelOrder(CustomerOrderTrackrRequest $request)
    {
        $data = $request->validated();

        $order = Order::where('tracking_number', $data['tracking_number'])
            ->phone($data['phone'])
            ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::AtWarehouse->value])
            ->firstOrFail();

        if (!$order) {
            return $this->errorResponse('There is no orders', null, 404);
        }
        $order->update(['status' => OrderStatus::Cancelled->value]);

        return $this->successResponse('Order has been cancelled');
    }

    public function submitFeedback(SubmitFeedbackRequest $request)
    {
        $data = $request->validated();
        try {
            $order = Order::where('tracking_number', $data['tracking_number'])
                ->where('customer_phone', $data['phone'])
                ->orderStatus(5)
                ->firstOrFail();

            $order->feedback()->create([
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            DriverFeedback::create([
                'driver_id' => $order->driver_id,
                'order_id' => $order->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $avgRating = DriverFeedback::where('driver_id', $order->driver_id)->avg('rating');
            $driver = $order->driver;
            $driver->rating = round($avgRating, 1);
            $driver->save();
            return $this->successResponse('Thanks for your feedback!');
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Order is not found.', null, 404);
        }
    }
}
