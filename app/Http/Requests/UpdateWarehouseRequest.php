<?php

namespace App\Http\Requests;

use App\Enums\Governorate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
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
                'name' => 'sometimes|string',
                'address' => 'sometimes|string',
                'governorate' => [
                    'sometimes',
                    'nullable',
                    Rule::in(array_column(Governorate::cases(), 'value'))
                ],
                'merchant_id' => 'required|exists:merchants,id'

            ];
        }

        return [
            'name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'governorate' => [
                'sometimes',
                'nullable',
                Rule::in(array_column(Governorate::cases(), 'value'))
            ],
            'merchant_id' => 'nullable|exists:merchants,id'

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

        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
