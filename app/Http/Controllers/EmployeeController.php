<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\EmpolyeeResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeController extends BaseController
{

    public function index()
    {
        $companyId = Auth::user()->employee->delivery_company_id;
        return EmpolyeeResource::collection(Employee::where('delivery_company_id', $companyId)
            ->paginate(25));
    }


    public function show($employeeId)
    {
        try {
            $employee = Employee::id($employeeId)
                ->where('delivery_company_id', Auth::user()->employee->delivery_company_id)
                ->firstOrFail();
            return new EmpolyeeResource($employee);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Employee Not Found.', null, 404);
        }
    }


    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $data['delivery_company_id'] = Auth::user()->employee->delivery_company_id;

        $employee = Employee::create($data);

        return $this->successResponse('Employee Added Successfully', new EmpolyeeResource($employee));
    }


    public function update(UpdateEmployeeRequest $request, $employeeId)
    {
        $data = $request->validated();
        try {
            $employee = Employee::id($employeeId)
                ->where('delivery_company_id', Auth::user()->employee->delivery_company_id)
                ->firstOrFail();
            $updated = $employee->update($data);
            if (!$updated) {
                return $this->errorResponse('No changes detected or update failed.', null, 422);
            }
            $employee->refresh();
            return $this->successResponse('Employee Updated Successfully', new EmpolyeeResource($employee));
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function destroy($employeeId)
    {
        try {
            $employee = Employee::id('id', $employeeId)
                ->where('delivery_company_id', Auth::user()->employee->delivery_company_id)
                ->firstOrFail();
            $employee->delete();
            return $this->successResponse('Employee has been deleted.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Employee not found or already deleted.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }
}
