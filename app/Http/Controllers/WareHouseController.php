<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Enums\Governorate;
use Illuminate\Http\Request;
use App\Models\DeliveryCompany;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\AddDeliveryCompanyRequest;
use App\Http\Resources\DeliveryCompanyWarehouseResource;
use App\Http\Requests\UpdateDeliveryCompanyWarehouseRequest;

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


    public function addDeliveryCompany(AddDeliveryCompanyRequest $request)
    {
        $data = $request->validated();
        $DeliveryCompany = DeliveryCompany::create($data);
        return $this->successResponse('Delivery Company Added Successfully', new DeliveryCompanyWarehouseResource($DeliveryCompany));
    }


    public function updateDeliveryCompany(UpdateDeliveryCompanyWarehouseRequest $request, $DeliveryCompanyId)
    {
        $data = $request->validated();
        try {
            $DeliveryCompany = DeliveryCompany::findorFail($DeliveryCompanyId);
            $updated = $DeliveryCompany->update($data);
            if (!$updated) {
                return $this->errorResponse('No changes detected or update failed.', null, 422);
            }
            $DeliveryCompany->refresh();
            return $this->successResponse('Update Delivery Company Successfully', $DeliveryCompany);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }

    public function destroyDeliveryCompany($DeliveryCompanyId)
    {
        try {
            $DeliveryCompany = DeliveryCompany::find($DeliveryCompanyId);
            if (!$DeliveryCompany) {
                return $this->errorResponse('Not Found Or Already Deletd', null, 404);
            }
            $DeliveryCompany->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getAllDeliveryCompany()
    {
        return DeliveryCompanyWarehouseResource::collection(DeliveryCompany::all());
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
