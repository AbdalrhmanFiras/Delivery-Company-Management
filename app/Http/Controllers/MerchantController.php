<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWarehouseRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\StoreOrderResource;
use App\Models\Order;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MerchantController extends Controller
{


    public function sendToWarehouse(AssignWarehouseRequest $request, $orderId)
    {
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


    public function getSendOrder()
    {
        return StoreOrderResource::collection(Order::where('upload', 'sent')->latest()->get());
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

    private function errorResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
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
