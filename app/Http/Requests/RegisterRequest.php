<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        $user = $this->input('user_type');

        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'user_type' => 'required|in:customer,driver,merchant,employee,super_admin,admin_order,admin_support,admin_check,admin_manager,super_admin_dc'

        ];

        switch ($user) {
            case 'merchant':
                $baseRules = array_merge($baseRules, [
                    'city' => 'required|string|max:100',
                    'country' => 'required|string|max:100',
                    'business_type' => 'nullable|string|max:100',
                    'business_license' => 'required_with:business_type|file|mimes:jpg,jpeg,png,pdf|max:2048',
                    'business_name' => 'required|string|max:100',
                ]);
                break;
            case 'driver':
                $baseRules = array_merge($baseRules, [
                    'vehicle_number' => 'required|string|max:6|unique:drivers,vehicle_number',
                    'delivery_company_id' => 'required|exists:delivery_companies,id'
                ]);
                break;
            case 'employee':
                $baseRules = array_merge($baseRules, [
                    'hire_date' => 'required|date',
                    'warehouse_id' => 'required_without:delivery_company_id|exists:warehouses,id',
                    'delivery_company_id' => 'required_without:warehouse_id|exists:delivery_companies,id'
                ]);
                break;
        }
        return $baseRules;
    }
}
