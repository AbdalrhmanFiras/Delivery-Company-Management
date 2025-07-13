<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
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
            'driver_name' => 'sometimes|string',
            'age' => 'sometimes|string',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:11|unique:drivers,phone',
            'email' => 'sometimes|email|unique:users,email',
            'vehicle_number' => 'sometimes|string|max:6|unique:drivers,vehicle_number',
        ];
    }
}
