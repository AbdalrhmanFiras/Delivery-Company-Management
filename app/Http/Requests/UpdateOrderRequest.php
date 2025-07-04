<?php

namespace App\Http\Requests;

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
        return [
            'merchant_id' => 'sometimes|uuid|exists:merchants,id',
            'customer_name' => 'sometimes',
            'customer_phone' => 'sometimes',
            'customer_address' => 'sometimes',
            'total_price' => 'sometimes|numeric|min:0'
        ];
    }
}
