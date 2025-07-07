<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureEmployeeIsDeliveryCompanyEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (
            !$user ||
            $user->user_type !== 'employee' ||
            !$user->employee ||
            !$user->employee->delivery_company_id
        ) {
            return response()->json(['error' => 'Unauthorized: Only delivery company employees allowed.'], 403);
        }

        return $next($request);
    }
}
