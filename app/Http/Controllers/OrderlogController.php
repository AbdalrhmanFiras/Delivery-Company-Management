<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderLogResource;
use App\Models\OrderLog;
use Illuminate\Http\Request;


class OrderlogController extends Controller
{
    public function logs(Request $request, $merchantId)
    {
        $query = OrderLog::where('merchant_id', $merchantId);

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);
        return OrderLogResource::collection($logs);
    }
}
