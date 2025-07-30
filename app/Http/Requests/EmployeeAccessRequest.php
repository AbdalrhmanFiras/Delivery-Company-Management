<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeAccessRequest extends FormRequest
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
        if ($user->hasRole('super_admin_dc')) {
            return [
                'employee_id' => 'required|exists:employees,id',
            ];
        }
        return [];
    }

    public function getEmployeeId(): string
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin_dc')) {
            $EmployeeId = $this->input('employee_id');
            if (!$EmployeeId) {
                abort(response()->json([
                    'message' => 'Employee ID is required for Super Admin DC.'
                ], 422));
            }
            return $EmployeeId;
        }

        if ($user->hasRole('employee')) {
            return $user->employee->id;
        }

        abort(response()->json([
            'message' => 'Unauthorized access.'
        ], 403));
    }
}
