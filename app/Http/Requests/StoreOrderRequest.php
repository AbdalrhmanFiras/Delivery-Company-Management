<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'merchant_id' => 'required|uuid|exists:merchants,id',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string|min:11',
            'customer_address' => 'required|string',
            'total_price' => 'required|numeric|min:0',
            'warehouse_id' => 'required|exists:warehouses,id'

        ];
    }
}
