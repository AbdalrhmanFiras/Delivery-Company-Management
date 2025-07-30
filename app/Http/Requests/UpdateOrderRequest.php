<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            return [
                'customer_name' => 'sometimes|string',
                'customer_phone' => 'sometimes|string|min:11',
                'customer_address' => 'sometimes|string',
                'total_price' => 'sometimes|numeric|min:0',
                'warehouse_id' => 'sometimes|exists:warehouses,id',
                'merchant_id' => 'required|exists:merchants,id'
            ];
        }
        if ($user->hasRole('admin_support')) {
            return [
                'customer_name' => 'sometimes|string',
                'customer_phone' => 'sometimes|string|min:11',
                'customer_address' => 'sometimes|string',
                'total_price' => 'sometimes|numeric|min:0',
                'warehouse_id' => 'sometimes|exists:warehouses,id',
                'merchant_id' => 'required|exists:merchants,id'
            ];
        }
        return [
            'merchant_id' => 'sometimes|uuid|exists:merchants,id',
            'customer_name' => 'sometimes',
            'customer_phone' => 'sometimes',
            'customer_address' => 'sometimes',
            'total_price' => 'sometimes|numeric|min:0',
            'warehouse_id' => 'sometimes|exists:warehouses,id'
        ];
    }

    public function getMerchantId(): string
    {
        $user = Auth::user();

        if ($user->hasRole('merchant')) {
            return $user->merchant->id;
        }

        if ($user->hasRole('super_admin')) {
            $merchantId = $this->input('merchant_id');
            if (!$merchantId) {
                abort(response()->json([
                    'message' => 'Merchant ID is required for Super Admin.'
                ], 422));
            }
            return $merchantId;
        }

        if ($user->hasRole('admin_order')) {
            $merchantId = $this->input('merchant_id');
            if (!$merchantId) {
                abort(response()->json([
                    'message' => 'Merchant ID is required for Admin_order.'
                ], 422));
            }
            return $merchantId;
        }

        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
