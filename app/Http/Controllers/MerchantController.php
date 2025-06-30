<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWarehouseRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\StoreOrderResource;
use App\Models\Order;
use App\Jobs\SendAllNotSentOrdersJob;
use App\Models\WarehouseReceipts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MerchantController extends Controller
{


    public function sendToWarehouse(AssignWarehouseRequest $request, $orderId)
    {
        $check = WarehouseReceipts::where('order_id', $orderId)->first();
        if ($check) {
            return $this->successResponse('This Order Had Already Sent');
        }
        try {

            DB::beginTransaction();
            $data =  $request->validated();
            $order = Order::findOrFail($orderId);

            $order->status = 1;
            $order->upload = 'sent';
            $order->warehouse_id = $data['warehouse_id'];
            $order->save();
            $orderReceipts = WarehouseReceipts::create([
                'order_id' => $order->id,
                'received_by' => $order->merchant->user_id,
                'received_at' => now()
            ]);
            DB::commit();

            return $this->successResponse(
                'Order pushed to warehouse successfully.',
                ['receipt' => new MerchantResource($orderReceipts)]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }

    public function sentAllToWarehouse()
    {
        dispatch(new SendAllNotSentOrdersJob());
        return $this->successResponse('All Order Are Sent');
    }


    public function deleteNotSent($orderId)
    {

        $order = Order::where('id', $orderId)->where('status', 'not sent')->first();
        if (!$order) {
            return $this->errorResponse('Order does not exist or has already been sent.');
        }

        $order->delete();
        return $this->successResponse('Order Deleted Successfuly.');
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

    private function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'status' => $status
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    private function errorResponse(string $message, mixed $data = null, int $status = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status' => $status
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }
}
