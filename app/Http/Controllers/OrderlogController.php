<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderLogResource;
use App\Models\OrderLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class OrderlogController extends BaseController
{
    public function logs(Request $request, $merchantId)
    {
        try {
            $query = OrderLog::where('merchant_id', $merchantId)->firstOrFail();
            $logs = $query->orderByDesc('created_at')->paginate(20);
            return OrderLogResource::collection($logs);
        } catch (ModelNotFoundException) {
            return $this->errorResponse('There is no merchant like this', null, 404);
        }
    }
}
