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
            'user_type' => 'required|in:customer,driver,merchant',

        ];

        switch ($user) {
            case 'merchant':
                $baseRules = array_merge($baseRules, [
                    'city' => 'required|string|max:100',
                    'country' => 'required|string|max:100',
                    'business_type' => 'nullable|string|max:100',
                    'phone' => 'required|string|max:20|unique:merchants,phone',
                ]);
                break;
            case 'driver':
                $baseRules = array_merge($baseRules, []);
                break;
            case 'customer':
                $baseRules = array_merge($baseRules, []);
                break;
        }
        return $baseRules;
    }
}
