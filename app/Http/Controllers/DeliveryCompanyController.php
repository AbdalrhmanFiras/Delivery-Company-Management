<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Employee;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use App\Http\Resources\DriverResource;
use App\Http\Resources\EmpolyeeResource;
use App\Http\Requests\EmployeeNameRequest;
use App\Http\Requests\UpdateDriverRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\EmployeeAccessRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeliveryCompanyController extends BaseController
{

    public function getDrivers(EmployeeAccessRequest $request)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $drivers = Driver::forCompanyId($CompanyId)
            ->where('status', 'Active')
            ->latest()
            ->paginate(25);

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers yet']);
        }
        return DriverResource::collection($drivers);
    }


    public function getAvailableDriver(EmployeeAccessRequest $request)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $drivers = Driver::forCompanyId($CompanyId)
            ->where('status', 'Active')->where('available', 1)
            ->latest()
            ->paginate(25);

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers found.']);
        }
        return DriverResource::collection($drivers);
    }


    public function getBestDrivers(EmployeeAccessRequest $request)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $drivers = Driver::forCompanyId($CompanyId)
            ->where('status', 'Active')->where('rating' >= 4)->orderByDesc('rating')
            ->get();

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers found.']);
        }
        return response()->json([
            'count' => $drivers->count(),
            'drivers' => $drivers
        ]);
    }


    public function getAvgDrivers(EmployeeAccessRequest $request)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $drivers = Driver::forCompanyId($CompanyId)
            ->where('status', 'Active')->whereBetween('rating', [1, 3])->orderByDesc('rating')
            ->get();

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers found.']);
        }
        return response()->json([
            'count' => $drivers->count(),
            'drivers' => $drivers
        ]);
    }


    public function getDriver(EmployeeAccessRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        try {
            $driver = Driver::forCompanyId($CompanyId)
                ->where('status', 'Active')
                ->where('id', $driverID)
                ->firstOrFail();
            return new DriverResource($driver);
        } catch (ModelNotFoundException $e) {
            Log::error("Driver not found with ID {$driverID} for company {$CompanyId}");

            return $this->errorResponse('Driver not found.', null, 404);
        }
    }


    public function UpdateDriver(UpdateDriverRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $data = $request->validated();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        try {
            $driver = Driver::forCompanyId($CompanyId)
                ->where('status', 'Active')
                ->where('id', $driverID)
                ->firstOrFail();

            $driver->update($data);
            return $this->successResponse('Driver updated Succssfully.', new DriverResource($driver));
        } catch (ModelNotFoundException $e) {
            Log::error("Driver not found with ID {$driverID} for company {$CompanyId}");
            return $this->errorResponse('Driver not found.', null, 404);
        } catch (\Exception $e) {
            Log::error("Failed to update driver ID {$driverID}: " . $e->getMessage());
            return $this->errorResponse('Failed to update driver.', null, 500);
        }
    }


    public function destroyDriver(EmployeeAccessRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        try {
            $driver = Driver::forCompanyId($CompanyId)
                ->where('id', $driverID)
                ->firstOrFail();

            $driver->delete();

            return $this->successResponse('Driver deleted Succssfully.');
        } catch (ModelNotFoundException) {
            Log::error("Driver not found with ID {$driverID} for company {$CompanyId}");
            return $this->errorResponse('Driver not found.', null, 404);
        } catch (\Exception $e) {
            Log::error("Failed to destroy driver ID {$driverID}: " . $e->getMessage());
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getDriverOrders(EmployeeAccessRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $driver = Driver::where('id', $driverID)
            ->forCompanyId($CompanyId)
            ->first();

        if (!$driver) {
            Log::error("Driver not found with ID {$driverID} for company {$CompanyId}");
            return $this->errorResponse('Driver not found or does not belong to your company.', 404);
        }
        $orders = Order::where('driver_id', $driverID)->forCompanyId($CompanyId)
            ->latest()->get();

        if ($orders->count() === 0) {
            return $this->errorResponse('No orders for driver.', 404);
        }
        return OrderResource::collection($orders);
    }


    public function getDriverSummary(EmployeeAccessRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $driverID = Driver::where('id', $driverID)->value('id');
        return response()->json([
            'Assign' => Order::where('driver_id', $driverID)->forCompanyId($CompanyId)->orderStatus(3)->count(),
            'Out' => Order::where('driver_id', $driverID)->forCompanyId($CompanyId)->orderStatus(4)->count(),
            'Delivered' => Order::where('driver_id', $driverID)->forCompanyId($CompanyId)->orderStatus(5)->count(),
            'Cancel' => Order::where('driver_id', $driverID)->forCompanyId($CompanyId)->orderStatus(6)->count(),
        ]);
    }


    public function toggleAvailability(EmployeeAccessRequest $request, $driverID)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $driver = Driver::forCompanyId($CompanyId)
            ->findOrFail($driverID);

        $driver->available = 1;
        $driver->save();

        return $this->successResponse('Driver availability updated.', ['available' => $driver->available]);
    }


    public function getStuckOrders()
    { //*
        return  OrderResource::collection(Order::orderStatus(3)
            ->where('updated_at', '<', now()->subHours(12))
            ->get());
    }


    public function getEmployees(EmployeeAccessRequest $request)
    { //*
        $employeeId = $request->getEmployeeId();
        $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
        $employees = Employee::forCompanyId($CompanyId)->paginate(25);

        if ($employees->isEmpty()) {
            return response()->json(['message' => 'There is no any Employee']);
        }

        return EmpolyeeResource::collection($employees);
    }


    public function getEmployee(EmployeeAccessRequest $request, $employeeId)
    { //*
        try {
            $employeeId = $request->getEmployeeId();
            $CompanyId = Employee::findOrFail($employeeId)->delivery_company_id;
            $employee = Employee::id($employeeId)->forCompanyId($CompanyId)->firstOrFail();

            return new EmpolyeeResource($employee);
        } catch (ModelNotFoundException) {
            Log::error("employee not found with name {$employeeId} for company {$CompanyId}");

            return $this->errorResponse('Employee not found', null, 404);
        }
    }
}
