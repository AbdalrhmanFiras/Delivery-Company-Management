<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\CreateUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;

//? done
//! review all code
class AuthController extends BaseController
{

    public function Register(RegisterRequest $request)
    {

        $data = $request->validated();
        DB::beginTransaction();

        try {
            $user = $this->createUser($data);
            $profile = $this->createProfile($request, $user);

            DB::commit();
            return $this->successResponse(
                'Registration successful',
                [
                    'user' => new UserResource($user->load($data['user_type'])),
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse(
                'Registration failed',
                config('app.debug') ? $e->getMessage() : 'An error occurred during registration',
                500
            );
        }
    }

    public function Login(LoginRequest $request)
    {

        $data = $request->validated();
        try {
            if (!$token = JWTAuth::attempt($data)) {
                return $this->errorResponse('Invaild credentials', null, 401);
            }
        } catch (JWTException $e) {
            return $this->errorResponse('token creation faild', $e->getMessage(), 500);
        }

        $user = User::where('email', $data['email'])->first();
        $status = $this->getUserProfileStatus($user);

        // if ($status !== 'Active') {
        //     return $this->errorResponse('Account is not active', null, 401);
        // }

        return $this->successResponse(
            'Login successful',
            [
                'user' => new UserResource($user->load($user->user_type)),
                'token' => $token
            ]
        );
    }
    public function Logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->successResponse('Logout successful');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to logout, please try again', $e->getMessage(), 500);
        }
    }

    private function getUserProfileStatus(User $user)
    {
        switch ($user->user_type) {
            case 'merchant':
                return $user->merchant?->status;
            case 'driver':
                return $user->driver?->status;
            case 'customer':
                return $user->customer?->status;
            case 'employee':
                return $user->employee?->status;
            default:
                return null;
        }
    }
    private function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $data['status'] = $this->getStatus($data['user_type']);

        return User::create($data);
    }

    private function getStatus($user_type)
    {
        return match ($user_type) {
            'merchant' => 'inactive',
            'driver' => 'inactive',
            'customer' => 'active',
            'employee' => 'active',
            default => 'active'
        };
    }

    private function createProfile(RegisterRequest $request, User $user)
    {
        return match ($request->user_type) {
            'driver' => $this->createDriverProfile($user, $request),
            'merchant' => $this->createMerchantProfile($user, $request),
            'customer' => $this->createCustomerProfile($user, $request),
            'employee' => $this->createEmployeeProfile($user, $request),

            default => null,
        };
    }


    private function createDriverProfile(User $user, RegisterRequest $request)
    {
        return Driver::create([
            'user_id' => $user->id,
            'vehicle_number' => $request->vehicle_number,
            'delivery_company_id' => $request->delivery_company_id,
        ]);
    }

    private function createCustomerProfile(User $user, RegisterRequest $request)
    {
        return Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'location' => $request->loaction,
            'user_id' => $user->id,
            'address' => $request->address,
            'location' => $request->location

        ]);
    }

    private function createEmployeeProfile(User $user, RegisterRequest $request)
    {
        return Employee::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'location' => $request->loaction,
            'user_id' => $user->id,
            'address' => $request->address,
            'hire_date' => $request->location,
            'warehoue_id' => $request->warehouse_id,
            'delivery_company_id' => $request->delivery_company_id

        ]);
    }
    private function createMerchantProfile(User $user, RegisterRequest $request)
    {
        $licensePath = null;
        if ($request->hasFile('business_license')) {
            $licensePath = $request->file('business_license')->store('images', 'public');
        }

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'business_license' => $licensePath,
            'warehouse_id' => $request->warehouse_id
        ]);

        return $merchant;
    }
}
