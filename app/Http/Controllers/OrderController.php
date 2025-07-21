<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\OrderLog;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use Illuminate\Http\JsonResponse;
use PhpParser\Node\Stmt\TryCatch;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends BaseController
{
    use LogsOrderChanges;
    public function store(StoreOrderRequest $request)
    {
        $data =  $request->validated();

        try {
            $customer = Customer::where('phone', $data['customer_phone'])->first();

            if ($customer) {
                $customerId = $customer->id;
            }

            $order = Order::create([
                //'merchant_id' => Auth::user()->merchant->id
                'merchant_id' => $data['merchant_id'],
                'total_price' => $data['total_price'],
                'customer_id' => $customerId ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'] ?? null,
                'upload' => $data['upload'] ?? 'not sent',

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
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    public function update(UpdateOrderRequest $request, $id)
    {
        $merchantId = Auth::user()->merchant->id;
        $data = $request->validated();
        try {
            $order = Order::id($id)->merchantId($merchantId)->firstOrFail();
            $originalData = $order->toArray();
            $updated = $order->update($data);
            if (!$updated) {
                return $this->errorResponse('Failed to update order.', null, 422);
            }
            Log::info("Order #{$order->id} updated by merchant {$order->merchant_id}");
            $this->logOrderChange($order, 'order_update', $originalData, $order->toArray());
            return $this->successResponse(
                'Order Updated Successfully',
                [
                    'order' => new OrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            Log::error("Order update failed for order #{$id} by merchant {$merchantId}: " . $e->getMessage());
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
        $merchantId = Auth::user()->merchant->id;
        try {
            $order = Order::id($id)
                ->merchantId($merchantId)
                ->firstOrFail();

            Log::info("Order #{$order->id} deleted by merchant {$merchantId}");

            $order->delete();
            return $this->successResponse('Order Deleted Successfully');
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Order not found.', null, 404);
        }
    }



    public function getSentOrder()
    {
        $merchantId = Auth::user()->merchant->id;
        return OrderResource::collection(Order::merchantId($merchantId)->uploaded('sent')->latest()->paginate(20));
    }


    public function getAllOrder()
    {
        $merchantId = Auth::user()->merchant->id;
        return OrderResource::collection(Order::merchantId($merchantId)->paginate(20));
    }


    public function getnotSentOrder()
    {
        $merchantId = Auth::user()->merchant->id;
        return OrderResource::collection(Order::merchantId($merchantId)->uploaded('not sent')->latest()->paginate(20));
    }


    public function show($id)
    {
        $merchantId = Auth::user()->merchant->id;
        try {
            $order = Order::id($id)->merchantId($merchantId)->firstOrFail();
            return $this->successResponse('Order details.', new OrderResource($order));
        } catch (ModelNotFoundException) {
            return $this->errorResponse('there is no Order', null, 404);
        }
    }
}
