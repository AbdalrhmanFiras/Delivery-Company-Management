<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CustomerController extends BaseController
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
        if ($user->user_type !== 'customer') {
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



    public function getCustomerOrderStatus(Request $request)
    {
        $user = Auth::guard('customer')->user();

        $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $order = Order::where('tracking_number', $request->tracking_number)
            ->where('customer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => ''], 404);
        }

        return response()->json([
            'tracking_number' => $order->tracking_number,
            'status' => OrderStatus::from($order->status)->labelForCustomer(),
            'last_updated' => $order->updated_at->diffForHumans(),
        ]);
    }
}
