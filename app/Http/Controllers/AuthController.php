<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Merchant;

//? done
//! review all code
class AuthController extends Controller
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

        if ($status !== 'Active') {
            return $this->errorResponse('Account is not active', null, 401);
        }

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
            default => 'active'
        };
    }

    private function createProfile(RegisterRequest $request, User $user)
    {
        return match ($request->user_type) {
            'driver' => $this->createDriverProfile($user, $request),
            'merchant' => $this->createMerchantProfile($user, $request),
            'customer' => $this->createCustomerProfile($user, $request),
            default => null,
        };
    }


    private function createDriverProfile(User $user, RegisterRequest $request)
    {
        // return Driver::create([
        //     'user_id' => $user->id,
        //     'license_number' => $request->license_number,
        //     'vehicle_type' => $request->vehicle_type,
        //     'vehicle_number' => $request->vehicle_number,
        //     'vehicle_color' => $request->vehicle_color,
        //     'vehicle_model' => $request->vehicle_model,
        //     'availability_status' => 'offline',
        // ]);
        return null;
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
