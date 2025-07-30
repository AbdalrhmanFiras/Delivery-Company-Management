<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ShowAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        if ($user->hasRole('super_admin')) {
            return ['merchant_id' => 'required|exists:merchants,id'];
        }
        $user = Auth::user();
        if ($user->hasRole('admin_order')) {
            return ['merchant_id' => 'required|exists:merchants,id'];
        }

        return [];
    }



    public function getMerchantId(): string
    {
        $user = Auth::user();

        if ($user->hasRole('merchant')) {
            return $user->merchant->id;
        }
        if ($user->hasRole('super_admin') || $user->hasRole('admin_order')) {
            $merchantId = $this->input('merchant_id');

            if (!$merchantId) {
                abort(response()->json([
                    'message' => 'Merchant ID is required.'
                ], 422));
            }

            return $merchantId;
        }
        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
