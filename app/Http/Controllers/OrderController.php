<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\StoreOrderResource;
use Illuminate\Http\JsonResponse;
use App\Models\Merchant;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;

class OrderController extends BaseController
{
    public function store(StoreOrderRequest $request)
    {
        $data =  $request->validated();
        try {
            $order = Order::create([
                'merchant_id' => $data['merchant_id'],
                'total_price' => $data['total_price'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'] ?? null,
                'upload' => $data['upload'] ?? 'not sent'
            ]);

            return $this->successResponse(
                'Order Created Successfully',
                [
                    'order' => new StoreOrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Unexpected error.',
                [
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function update(UpdateOrderRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $order = Order::findOrFail($id);
            $order->update($data);
            return $this->successResponse(
                'Order Updated Successfully',
                [
                    'order' => new StoreOrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Unexpected error.',
                [
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            return $this->successResponse(
                'Order Deleted Successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Unexpected error.',
                [
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function getSentOrder()
    {
        return StoreOrderResource::collection(Order::uploaded('sent')->latest()->paginate(20));
    }


    public function getAllOrder()
    {
        return StoreOrderResource::collection(Order::paginate(20)->all());
    }


    public function getnotSentOrder()
    {
        return StoreOrderResource::collection(Order::uploaded('not sent')->latest()->paginate(20));
    }


    public function show($id)
    {
        return new StoreOrderResource(Order::findorfail($id));
    }


    public function index()
    {
        return StoreOrderResource::collection(Order::latest()->get());
    }
}
