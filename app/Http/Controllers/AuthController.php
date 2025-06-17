<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function RegisterMerchant(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:225',
            'email' => 'required|string|email|unqiue',
            'phone' => 'required|string',
            'addres' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'status' => 'required|in:Active ,Inactive',

        ]);

        $merchant = Merchant::create(
            $request->all()
        );
        return response()->json($merchant);

    }

}

