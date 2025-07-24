<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Resources\ComplaintsResource;

class ComplaintController extends BaseController
{

    public function store(ComplaintStoreRequest $request)
    {
        $data = $request->validated();
        $complaint = Complaint::create($data);

        $complaint->load(['order' => function ($query) {
            $query->select('id', 'merchant_id', 'warehouse_id', 'delivery_company_id');
        }]);
        return $this->successResponse(
            'Complaint submitted successfully.',
            [
                'type' => $data['type'],
                'message' => $data['message']
            ]
        );
    }
}
