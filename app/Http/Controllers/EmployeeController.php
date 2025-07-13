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
        $context = $this->getEmployeeType();
        $employees = Employee::where($context['from'], $context['id'])->paginate(25);
        return EmpolyeeResource::collection($employees);
    }


    public function show($employeeId)
    {
        try {
            $context = $this->getEmployeeType();
            $employee = Employee::id($employeeId)->where($context['from'], $context['id'])
                ->firstOrFail();
            return new EmpolyeeResource($employee);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Employee Not Found.', null, 404);
        }
    }


    public function update(UpdateEmployeeRequest $request, $employeeId)
    {
        $data = $request->validated();
        $context = $this->getEmployeeType();
        try {
            $employee = Employee::id($employeeId)
                ->where($context['from'], $context['id'])
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
            $context = $this->getEmployeeType();
            $employee = Employee::id($employeeId)
                ->where($context['from'], $context['id'])
                ->firstOrFail();
            $employee->delete();
            return $this->successResponse('Employee has been deleted.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Employee not found or already deleted.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    private function getEmployeeType(): array
    {
        $employee = Auth::user()?->employee;

        if (!$employee) {
            throw new \Exception('No employee profile associated with this user.');
        }

        if ($employee->delivery_company_id) {
            return [
                'from' => 'delivery_company_id',
                'id'     => $employee->delivery_company_id,
            ];
        }

        if ($employee->warehouse_id) {
            return [
                'from' => 'warehouse_id',
                'id'     => $employee->warehouse_id,
            ];
        }

        throw new \Exception('Employee context not defined: no delivery company or warehouse ID found.');
    }
}
