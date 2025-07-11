<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\CustomerOrderRequest;
use App\Http\Requests\CustomerOrderTrackrRequest;
use App\Http\Resources\CustomerOrderResource;

class CustomerController extends BaseController
{

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

    // future 
    // public function submitFeedback(Request $request)
    // {
    //     $data = $request->validate([
    //         'tracking_number' => 'required|string',
    //         'phone' => 'required|string',
    //         'rating' => 'required|integer|min:1|max:5',
    //         'comment' => 'nullable|string',
    //     ]);

    //     $order = Order::where('tracking_number', $data['tracking_number'])
    //         ->where('customer_phone', $data['phone'])
    //         ->where('status', OrderStatus::Delivered->value)
    //         ->firstOrFail();

    //     $order->feedback()->create([
    //         'rating' => $data['rating'],
    //         'comment' => $data['comment'] ?? null,
    //     ]);

    //     return $this->successResponse('Thanks for your feedback!');
    // }
}
