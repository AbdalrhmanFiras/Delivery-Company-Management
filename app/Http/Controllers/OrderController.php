<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use App\Models\OrderLog;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use Illuminate\Http\JsonResponse;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;

class OrderController extends BaseController
{
    use LogsOrderChanges;
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
            Log::info("Order #{$order->id} created by merchant {$order->merchant_id}");

            $this->logOrderChange($order, 'create_order');
            return $this->successResponse(
                'Order Created Successfully',
                [
                    'order' => new OrderResource($order)
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
            $originalData = $order->toArray();
            $order->update($data);
            Log::info("Order #{$order->id} updated by merchant {$order->merchant_id}");
            $this->logOrderChange($order, 'order_update', $originalData, $order->toArray());
            return $this->successResponse(
                'Order Updated Successfully',
                [
                    'order' => new OrderResource($order)
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
            Log::info("Order deleted by merchant {$order->merchant_id}");
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
        return OrderResource::collection(Order::uploaded('sent')->latest()->paginate(20));
    }


    public function getAllOrder()
    {
        return OrderResource::collection(Order::paginate(20)->all());
    }


    public function getnotSentOrder()
    {
        return OrderResource::collection(Order::uploaded('not sent')->latest()->paginate(20));
    }


    public function show($id)
    {
        return new OrderResource(Order::findorfail($id));
    }


    public function index()
    {
        return OrderResource::collection(Order::latest()->get());
    }
}
