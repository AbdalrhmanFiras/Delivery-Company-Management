<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Warehouse;
use App\Enums\Governorate;
use Illuminate\Http\Request;
use App\Models\DeliveryCompany;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\EmpolyeeResource;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\EmployeeNameRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\AddDeliveryCompanyRequest;
use App\Http\Resources\DeliveryCompanyWarehouseResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateDeliveryCompanyWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;



class WareHouseController extends BaseController
{
    public function store(StoreWarehouseRequest $request)
    { //! merchant
        // $merchantId = Auth::user()->merchant->id;
        $data = $request->validated();
        // $data['merchant_id'] = $merchantId;
        $warehouse = Warehouse::create($data);
        return response()->json(new WarehouseResource($warehouse));
    }


    public function update(UpdateWarehouseRequest $request, $warehouseId)
    { //! merchant
        try {
            $merchantId = Auth::user()->merchant->id;
            $data = $request->validated();
            $warehouse = Warehouse::where('warehouse_id', $warehouseId)->merchantId($merchantId)->firstOrFail();
            $updated = $warehouse->update($data);
            if (!$updated) {
                return $this->errorResponse('No changes detected or update failed.', null, 422);
            }
            return response()->json(new WarehouseResource($warehouse));
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Warehouse not found.', null, 404);
        }
    }


    public function destroy($warehouseId)
    { //! merchant, admin
        try {
            $merchantId = Auth::user()->merchant->id;
            $warehouse = Warehouse::where('id', $warehouseId)
                ->merchantId($merchantId)
                ->firstOrFail();

            $warehouse->delete();
            return $this->successResponse('Warehouse deleted successfully.');
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Warehouse not found.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete warehouse.', ['error' => $e->getMessage()], 500);
        }
    }


    // public function store(StoreWarehouseRequest $request)
    // { //! merchant
    //     $data = $request->validated();
    //     $warehouse = Warehouse::create($data);
    //     return response()->json(new WarehouseResource($warehouse));
    // }


    //? for main Admin 
    public function addDeliveryCompany(AddDeliveryCompanyRequest $request)
    { //! Admin from main 
        $data = $request->validated();
        $DeliveryCompany = DeliveryCompany::create($data);
        return $this->successResponse('Delivery Company Added Successfully', new DeliveryCompanyWarehouseResource($DeliveryCompany));
    }


    public function updateDeliveryCompany(UpdateDeliveryCompanyWarehouseRequest $request, $DeliveryCompanyId)
    { //! Admin from main
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
    { //!  Admin from main
        try {
            $DeliveryCompany = DeliveryCompany::findOrFail($DeliveryCompanyId);
            $DeliveryCompany->delete();
            return $this->successResponse('Delivery Company has been deleted.');
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Delivery Company not found or already deleted.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getAllDeliveryCompany()
    { //! Admin from main
        $warehouseId = Auth::user()->employee->warehouse_id;
        return DeliveryCompanyWarehouseResource::collection(DeliveryCompany::where('warehouse_id', $warehouseId)->get());
    }


    public function getDeliveryCompany($DeliveryCompanyId)
    { //! Admin from main
        $warehouseId = Auth::user()->employee->warehouse_id;
        try {
            $DeliveryCompany = DeliveryCompany::where('id', $DeliveryCompanyId)->where('warehouse_id', $warehouseId)->firstOrFail();
            return new DeliveryCompanyWarehouseResource($DeliveryCompany);
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Delivery Company Not Found.', null, 404);
        }
    }


    public function deliveryCompaniesByGovernorate($governorate)
    { //! Admin from main
        $warehouseId = Auth::user()->employee->warehouse_id;
        $allowedGovernorates = array_column(Governorate::cases(), 'value');
        validator(['governorate' => $governorate], [
            'governorate' => ['required', Rule::in($allowedGovernorates)]
        ])->validate();
        $companies = DeliveryCompany::where('governorate', $governorate)
            ->where('warehouse_id', $warehouseId)->get();
        return DeliveryCompanyWarehouseResource::collection($companies);
    }


    public function deliveryCompaniesByStatus($status)
    { //! Admin from main
        $warehouseId = Auth::user()->employee->warehouse_id;
        $companies = DeliveryCompany::where('status', $status)
            ->where('warehouse_id', $warehouseId)->get();
        return DeliveryCompanyWarehouseResource::collection($companies);
    }


    // public function getEmployees()
    // {
    //     $warehouseId = Auth::user()->employee->warehouse_id;
    //     $employees = Employee::forWarehouseId($warehouseId)->paginate(25);

    //     if ($employees->isEmpty()) {
    //         return response()->json(['message' => 'There is no any Employee']);
    //     }

    //     return EmpolyeeResource::collection($employees);
    // }


    // public function getEmployeebyName(EmployeeNameRequest $request)
    // {
    //     try {
    //         $data = $request->validated();
    //         $warehouseId = Auth::user()->employee->warehouse_id;
    //         $employee = Employee::forWarehouseId($warehouseId)->whereHas('user', fn($q) => $q->where('name', $data['name']))->firstOrFail();
    //         return new EmpolyeeResource($employee);
    //     } catch (ModelNotFoundException) {
    //         return $this->errorResponse('this employee is not found', null, 404);
    //     }
    // }


    // public function getEmployee($employeeId)
    // {
    //     try {
    //         $warehouseId = Auth::user()->employee->warehouse_id;
    //         $employee = Employee::id($employeeId)->forWarehouseId($warehouseId)->firstOrFail();

    //         return new EmpolyeeResource($employee);
    //     } catch (ModelNotFoundException) {
    //         return $this->errorResponse('Employee not found', null, 404);
    //     }
    // }
}
