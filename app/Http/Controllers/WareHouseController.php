<?php

namespace App\Http\Controllers;

use App\Enums\Governorate;
use App\Http\Resources\WarehouseResource;
use App\Models\DeliveryCompany;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WareHouseController extends Controller
{
    public function store(Request $request)
    { //? i should make register and login for it 
        // employe
        // manager
        // Admin 

        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
        ]);

        $warehouse = Warehouse::create($data);

        return response()->json(new WarehouseResource($warehouse));
    }


    public function addDeliveryCompany(Request $request)
    {
        //governorate
        $data = $request->validate([
            'company_name' => 'required|string',
            'contact_info' => 'required|string',
            'governorate' => [
                'sometimes',
                'nullable',
                Rule::in(array_column(Governorate::cases(), 'value'))
            ]
        ]);

        $DeliveryCompany = DeliveryCompany::create($data);

        return response($data);
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
