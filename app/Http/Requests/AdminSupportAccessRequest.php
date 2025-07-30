<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class AdminSupportAccessRequest extends FormRequest
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

        if ($user->hasRole('admin_support')) {
            return ['merchant_id' => 'required|exists:merchants,id'];
        }

        return [];
    }


    public function getMerchantId(): string
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            $merchantId = $this->input('merchant_id');
            if (!$merchantId) {
                abort(response()->json([
                    'message' => 'Merchant ID is required for Super Admin.'
                ], 422));
            }
            return $merchantId;
        }

        if ($user->hasRole('merchant')) {
            return $user->merchant->id;
        }

        if ($user->hasRole('admin_support')) {
            $merchantId = $this->input('merchant_id');
            if (!$merchantId) {
                abort(response()->json([
                    'message' => 'Merchant ID is required for Admin Support.'
                ], 422));
            }
            return $merchantId;
        }
        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
