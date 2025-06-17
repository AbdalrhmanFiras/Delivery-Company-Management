<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\User;
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




}

