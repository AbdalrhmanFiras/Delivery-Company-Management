<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait CheckUserAccessTrait
{
    // cancelled this trait im using requestform is better for my case 
    protected function checkUserType($request)
    {
        $user = Auth::user();
        if ($user->hasRole('merchant')) {
            return $user->merchant->id;
        }
        if ($user->hasRole('super_admin')) {
            $merchantId = $request->input('merchant_id');
            if (!$merchantId) {
                return abort(response()->json([
                    'message' => 'Merchant ID is required for Super Admin.'
                ], 422));
            }
            return $merchantId;
        }
        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
