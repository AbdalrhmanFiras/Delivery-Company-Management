<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\StoreOrderResource;
use Illuminate\Http\JsonResponse;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request){

           $data =  $request->validated();
   
   try{
            $order = Order::create([
            'merchant_id' => $data['merchant_id'],
            'customer_id' => $data['customer_id'],
            'total_price' => $data['total_price'],
            
            ]);

            return $this->successResponse(
                'Order Created Successfully',[
                'order' => new StoreOrderResource($order)
            ]);   

        }catch(\Exception $e) {
        return $this->errorResponse(
             'Unexpected error.',[
            'error' => $e->getMessage()
        ], 500);
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

    public function show($id){
        return new StoreOrderResource(Order::findorfail($id));
    }

    public function index( Request $request){
        return StoreOrderResource::collection(Order::latest()->get());
    }

     private function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);

    }

    private function errorResponse(string $message, mixed $data = null, int $status = 401): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }


    
}
