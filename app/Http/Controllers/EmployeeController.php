<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\EmpolyeeResource;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeController extends Controller
{

    public function index()
    {
        return EmpolyeeResource::collection(Employee::paginate(25));
    }

    public function show($employeeId)
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            return new EmpolyeeResource($employee);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Delivery Company Not Found.', null, 404);
        }
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $employee = Employee::create($data);
        return $this->successResponse('Employee Added Successfully', new EmpolyeeResource($employee));
    }

    public function update(UpdateEmployeeRequest $request, $employeeId)
    {
        $data = $request->validated();
        try {
            $employee = Employee::findorFail($employeeId);
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
            $employee = Employee::findOrFail($employeeId);
            $employee->delete();
            return $this->successResponse('Employee has been deleted.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Employee not found or already deleted.', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
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
