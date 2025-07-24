<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Models\DriverFeedback;
use App\Events\CustomerOtpLogin;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\CustomerLoginRequest;
use App\Http\Requests\CustomerOrderRequest;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Requests\SubmitFeedbackRequest;
use Illuminate\Queue\Middleware\RateLimited;
use App\Http\Resources\CustomerOrderResource;
use App\Http\Requests\CustomerVerifyOtpRequest;
use App\Http\Requests\CustomerOrderTrackrRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerController extends BaseController
{
    public function login(CustomerLoginRequest $request)

    {
        $data = $request->validated();
        $customer = Customer::where('phone', $data['phone'])->first();
        // if (Auth::guard('api')->check()) {
        //     return $this->successResponse('You are already logged in.');
        // }

        if (!$customer) {
            $order = Order::where('customer_phone', $data['phone'])
                ->select('customer_name', 'customer_phone', 'customer_address')
                ->first();

            if (!$order) {
                return $this->errorResponse('No account or order history found for this phone number.', null, 404);
            }
            $customer = Customer::create([
                'phone' => $order->customer_phone,
                'name' => $order->customer_name,
                'customer_address' => $order->customer_address,
            ]);
        }
        if (RateLimiter::tooManyAttempts('otp:' . $data['phone'], 4)) {
            return $this->errorResponse('Too many OTP requests. Try again later.', null, 429);
        }
        RateLimiter::hit('otp:' . $data['phone'], 60);
        event(new CustomerOtpLogin($customer));

        return $this->successResponse('OTP sent to your phone.');
    }


    public function verifyOtp(CustomerVerifyOtpRequest $request)
    {
        $data = $request->validated();
        $cacheKey = 'otp_' . $data['phone'];

        if (!Cache::has($cacheKey)) {
            return $this->errorResponse('OTP expired or not found.', null, 400);
        }
        $cachedOtp = Cache::get($cacheKey);

        if ($data['otp'] != $cachedOtp) {
            return $this->errorResponse('Invalid OTP.', null, 400);
        }

        Cache::forget($cacheKey);
        $customer = Customer::where('phone', $data['phone'])->first();
        if (!$customer) {
            return $this->errorResponse('Customer not found.', null, 404);
        }

        // if (isset($customer->is_verified) && !$customer->is_verified) {
        //     $customer->is_verified = true;
        //     $customer->save();
        // }
        $token = JWTAuth::fromUser($customer);

        return $this->successResponse(
            'OTP verified successfully.',
            ['token' => $token],
        );
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


    public function cancelOrder($orderId)
    {
        $customer = Auth::user()->customer;
        $order = Order::id($orderId)->phone($customer->phone)
            ->where('status', [OrderStatus::AtWarehouse->value])
            ->first();
        if (!$order) {
            return $this->errorResponse('Order not found or cannot be canceled.', null, 404);
        }
        $order->update(['status' => OrderStatus::Cancelled->value]);
        return $this->successResponse('Order has been canceled successfully.');
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


    // public function getCurrentOrders(CustomerOrderRequest $request)
    // {
    //     $data = $request->validated();
    //     $orders = Order::phone($data['phone'])
    //         ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::AtWarehouse->value, OrderStatus::OutForDelivery->value])
    //         ->latest()->paginate(10);

    //     if ($orders->isEmpty()) {
    //         return $this->errorResponse('No active orders found.', null, 404);
    //     }

    //     return CustomerOrderResource::collection($orders);
    // }


    public function getCompeleteOrders(CustomerOrderRequest $request)
    {
        $data = $request->validated();
        $orders = Order::orderStatus(5)
            ->phone($data['phone'])
            ->latest()->paginate(10);
        if ($orders->isEmpty()) {
            return $this->errorResponse('No delivered orders found.', null, 404);
        }
        return  CustomerOrderResource::collection($orders);
    }


    // public function cancelOrder(CustomerOrderTrackrRequest $request)
    // {
    //     $data = $request->validated();

    //     $order = Order::where('tracking_number', $data['tracking_number'])
    //         ->phone($data['phone'])
    //         ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::AtWarehouse->value])
    //         ->firstOrFail();

    //     if (!$order) {
    //         return $this->errorResponse('There is no orders', null, 404);
    //     }
    //     $order->update(['status' => OrderStatus::Cancelled->value]);

    //     return $this->successResponse('Order has been cancelled');
    // }

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
