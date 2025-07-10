<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\DriverReceipts;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Tymon\JWTAuth\Exceptions\JWTException;

class DriverController extends BaseController
{

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
        $exists = DriverReceipts::orderId($orderId)->where('deilvery_company_id', $user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->firstOrFail();
        if ($exists) {
            return response()->json('This order Already been received.', 401);
        }
        try {
            DB::beginTransaction();
            $order = Order::id($orderId)->orderStatus(3)->where('deilvery_company_id', $user->driver->delivery_company_id)
                ->where('driver_id', $user->driver->id)->firstOrFail();
            $orderReceipts = DriverReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'received_at' => now(),
            ]);
            DB::commit();
            return $this->successResponse('Order received successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }



    public function getOrders()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(3)->where('delivery_company_id', $user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    public function getDeliverd()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(5)->where('delivery_company_id', $user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    public function getCancel()
    {
        $user = Auth::user();
        return OrderResource::collection(Order::orderStatus(6)->where('delivery_company_id', $user->driver->delivery_company_id)
            ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    }


    // public function getOrders()
    // {
    //     $user = Auth::user();
    //     return OrderResource::collection(Order::orderStatus(3)->where('delivery_company_id', $user->driver->delivery_company_id)
    //         ->where('driver_id', $user->driver->id)->latest()->paginate(25));
    // }
}
