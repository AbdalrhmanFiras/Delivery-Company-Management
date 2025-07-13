<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use App\Http\Resources\DriverResource;
use App\Http\Requests\UpdateDriverRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeliveryCompanyController extends BaseController
{

    public function getDrivers()
    {
        $user = Auth::user();
        $drivers = Driver::forCompanyId($user->employee->delivery_company_id)
            ->where('status', 'Active')
            ->latest()
            ->paginate(25);

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers yet']);
        }
        return DriverResource::collection($drivers);
    }


    public function getAvailableDriver()
    {
        $user = Auth::user();

        $drivers = Driver::forCompanyId($user->employee->delivery_company_id)
            ->where('status', 'Active')->where('available', 1)
            ->latest()
            ->paginate(25);

        if ($drivers->isEmpty()) {
            return response()->json(['message' => 'there is no Drivers found.']);
        }
        return DriverResource::collection($drivers);
    }


    public function getBestDrivers()
    {
        $user = Auth::user();

        $drivers = Driver::forCompanyId($user->employee->delivery_company_id)
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

    public function getAvgDrivers()
    {
        $user = Auth::user();
        $drivers = Driver::forCompanyId($user->employee->delivery_company_id)
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


    public function getDriver($driverID)
    {
        $user = Auth::user();
        try {
            $driver = Driver::forCompanyId($user->employee->delivery_company_id)
                ->where('status', 'Active')
                ->where('id', $driverID)
                ->firstOrFail();
            return new DriverResource($driver);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Driver not found.');
        }
    }


    public function UpdateDriver(UpdateDriverRequest $request, $driverID)
    {
        $data = $request->validated();
        $user = Auth::user();

        try {
            $driver = Driver::forCompanyId($user->employee->delivery_company_id)
                ->where('status', 'Active')
                ->where('id', $driverID)
                ->firstOrFail();

            $driver->update($data);
            return $this->successResponse('Driver updated Succssfully.', new DriverResource($driver));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Driver not found.', 404);
        }
    }


    public function destroyDriver($driverID)
    {
        $user = Auth::user();
        try {
            $driver = Driver::forCompanyId($user->employee->delivery_company_id)
                ->where('id', $driverID)
                ->firstOrFail();

            $driver->delete();

            return $this->successResponse('Driver deleted Succssfully.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Driver not found.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', $e->getMessage(), 500);
        }
    }


    public function getDriverOrders($driverID)
    {
        $user = Auth::user();
        $driver = Driver::where('id', $driverID)
            ->forCompanyId($user->employee->delivery_company_id)
            ->first();

        if (!$driver) {
            return $this->errorResponse('Driver not found or does not belong to your company.', 404);
        }
        $orders = Order::where('driver_id', $driverID)->forCompanyId($user->employee->delivery_company_id)
            ->latest()->get();

        if ($orders->count() === 0) {
            return $this->errorResponse('No orders for driver.', 404);
        }
        return OrderResource::collection($orders);
    }


    public function getDriverSummery($driverID)
    {
        $user = Auth::user();
        $deliveryID = $user->employee->delivery_company_id;
        $driverID = Driver::where('id', $driverID)->value('id');
        return response()->json([
            'Assign' => Order::where('driver_id', $driverID)->forCompanyId($deliveryID)->orderStatus(3)->count(),
            'Out' => Order::where('driver_id', $driverID)->forCompanyId($deliveryID)->orderStatus(4)->count(),
            'Delivered' => Order::where('driver_id', $driverID)->forCompanyId($deliveryID)->orderStatus(5)->count(),
            'Cancel' => Order::where('driver_id', $driverID)->forCompanyId($deliveryID)->orderStatus(6)->count(),
        ]);
    }


    public function toggleAvailability($driverID)
    {
        $driver = Driver::forCompanyId(Auth::user()->employee->delivery_company_id)
            ->findOrFail($driverID);

        $driver->available = 1;
        $driver->save();

        return $this->successResponse('Driver availability updated.', ['available' => $driver->available]);
    }

    public function getStuckOrders()
    {
        return  OrderResource::collection(Order::orderStatus(3)
            ->where('updated_at', '<', now()->subHours(12))
            ->get());
    }
}
