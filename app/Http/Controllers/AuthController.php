<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Merchant;


//! review all code
class AuthController extends Controller
{
    public function RegisterMerchant(Request $request)
    {

        // $request->validate([
        //     'name' => 'required|string|max:225',
        //     'email' => 'required|string|email',
        //     'phone' => 'required|string',
        //     'address' => 'required|string',
        //     'city' => 'required|string',
        //     'country' => 'required|string',
        //     'status' => 'required|in:Active,Inactive',
        //     'password' => 'required|string|min:7'
        // ]);

        // $user = User::create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'password' => bcrypt($request->password),
        //     'role' => 'merchant',
        // ]);

        // $user->merchants()->create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'phone' => $request->phone,
        //     'city' => $request->city,
        //     'address' => $request->address,
        //     'country' => $request->country,
        //     'status' => $request->status,

        // ]);
        // return response()->json($user);

    }


    public function Register(RegisterRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();

        try {
            $user = $this->createUser($data);
            $profile = $this->createProfile($request, $user);

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                'Registration successful',
                [
                    'user' => new UserResource($user->load($data['user_type'])),
                    'token' => $token,

                ],
                201
            );

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Registration failed: ' . $e->getMessage());

            return $this->errorResponse(
                'Registration failed',
                config('app.debug') ? $e->getMessage() : 'An error occurred during registration',
                500
            );
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
        return null;
    }
    private function createMerchantProfile(User $user, RegisterRequest $request)
    {
        $licensePath = null;
        if ($request->hasFile('business_license')) {
            $licensePath = $request->file('business_license')->store('license', 'public');
        }

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'business_license' => $licensePath,
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


