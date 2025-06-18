<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Models\Merchant;
use App\Models\User;
use CreateUsersTable;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function RegisterMerchant(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:225',
            'email' => 'required|string|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'status' => 'required|in:Active,Inactive',
            'password' => 'required|string|min:7'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'merchant',
        ]);

        $user->merchants()->create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'address' => $request->address,
            'country' => $request->country,
            'status' => $request->status,

        ]);
        return response()->json($user);

    }



    private function CreateUser(CreateUserRequest $request)
    {

        $data = $request->validated();
        $data['password'] = hash::make($data['password']);
        $data['status'] = $this->GetStatus($data['user_type']);

        return User::create($data);
    }

    private function GetStatus($user_type)
    {
        return match ($user_type) {
            'merchant' => 'inactive',
            'driver' => 'inactive',
            'customer' => 'active',
            default => 'active'
        };
    }

    private function CreateProfile(Request $request, User $user)
    { {
            return match ($request->user_type) {
                'driver' => $this->CreateDriverProfile($user, $request),
                'merchant' => $this->CreateMerchantProfile($user, $request),
                'customer' => $this->CreateCustomerProfile($user, $request),
                default => null,
            };
        }
    }


    private function CreateMerchantProfile(User $user, Request $request)
    {

        return Merchant::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'business_type' => $request->business_type,
            'business_license' => $request->business_license,
        ]);
    }


}


