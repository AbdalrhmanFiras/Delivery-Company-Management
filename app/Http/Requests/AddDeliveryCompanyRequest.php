<?php

namespace App\Http\Requests;

use App\Enums\Governorate;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AddDeliveryCompanyRequest extends FormRequest
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
            'company_name' => 'required|string',
            'contact_info' => 'required|string',
            'governorate' => [
                'sometimes',
                'nullable',
                Rule::in(array_column(Governorate::cases(), 'value'))
            ],
            'warehouse_id' => 'nullable|exists:warehouses,id'
        ];
    }
}
