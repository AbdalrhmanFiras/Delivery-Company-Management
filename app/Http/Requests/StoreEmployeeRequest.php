<?php

namespace App\Http\Requests;

use App\Enums\WarehouseRole;
use App\Enums\DeliveryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
            'hire_date' => 'required|date',
            'role' => [
                'sometimes',
                'nullable',
                Rule::in(array_column(WarehouseRole::cases(), 'value')),
            ],
            'status' => [
                'sometimes',
                'nullable',
                Rule::in(array_column(DeliveryStatus::cases(), 'value')),
            ],
        ];
    }
}
