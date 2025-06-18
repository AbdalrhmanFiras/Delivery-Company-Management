<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'name' => 'required|string|max:225',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:7|confirmed',
            'phone' => 'required|string|min:12',
            'addres' => 'required|string|max:225',
            'city' => 'required|string|max:225',
            'user_type' => 'required|in:merchant , driver, admin, super admin, warehouse_employee, customer, employee',
        ];
    }
}
