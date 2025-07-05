<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Enums\Governorate;
use Illuminate\Http\Request;
use App\Models\DeliveryCompany;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\AddDeliveryCompanyRequest;
use App\Http\Resources\DeliveryCompanyWarehouseResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateDeliveryCompanyWarehouseRequest;

// employe
// Admin 
//DeliveryCompanyLog

class WareHouseController extends BaseController
{
    public function store(StoreWarehouseRequest $request)
    { //! Admin
        $data = $request->validated();
        $warehouse = Warehouse::create($data);
        return response()->json(new WarehouseResource($warehouse));
    }


    public function addDeliveryCompany(AddDeliveryCompanyRequest $request)
    { //! Admin
        $data = $request->validated();
        $DeliveryCompany = DeliveryCompany::create($data);
        return $this->successResponse('Delivery Company Added Successfully', new DeliveryCompanyWarehouseResource($DeliveryCompany));
    }


    public function updateDeliveryCompany(UpdateDeliveryCompanyWarehouseRequest $request, $DeliveryCompanyId)
    { //! Admin
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
    { //! Admin
        try {
            $DeliveryCompany = DeliveryCompany::findOrFail($DeliveryCompanyId);
            $DeliveryCompany->delete();
            return $this->successResponse('Delivery Company has been deleted.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Delivery Company not found or already deleted.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getAllDeliveryCompany()
    {
        return DeliveryCompanyWarehouseResource::collection(DeliveryCompany::whereNotNull('warehouse_id')->get());
    }


    public function getDeliveryCompany($DeliveryCompanyId)
    {
        try {
            $DeliveryCompany = DeliveryCompany::where('id', $DeliveryCompanyId)->whereNotNull('warehouse_id')->firstOrFail();
            return new DeliveryCompanyWarehouseResource($DeliveryCompany);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Delivery Company Not Found.', null, 404);
        }
    }


    public function deliveryCompaniesByGovernorate($governorate)
    {
        $allowedGovernorates = array_column(Governorate::cases(), 'value');
        validator(['governorate' => $governorate], [
            'governorate' => ['required', Rule::in($allowedGovernorates)]
        ])->validate();
        $companies = DeliveryCompany::where('governorate', $governorate)
            ->whereNotNull('warehouse_id')->get();
        return DeliveryCompanyWarehouseResource::collection($companies);
    }


    public function deliveryCompaniesByStatus($status)
    {
        $companies = DeliveryCompany::where('status', $status)
            ->whereNotNull('warehouse_id')->get();
        return DeliveryCompanyWarehouseResource::collection($companies);
    }
}
