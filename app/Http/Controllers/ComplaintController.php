<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Complaint;
use App\Enums\OrderStatus;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use App\Models\ComplaintReply;
use Faker\Provider\ar_EG\Company;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ComplaintsResource;
use App\Http\Requests\ComplaintStoreRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Resources\IndexComplaintsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ComplaintController extends BaseController
{

    public function __construct()
    {
        $this->middleware(['auth:customer', 'role:customer']);
        $this->middleware('permission:get-customer-complaints')->only('show');
        $this->middleware('permission:get-customer-complaint')->only('index');
        $this->middleware('permission:create-complaint')->only('store');
        $this->middleware('permission:update-complaint')->only('update');
        $this->middleware('permission:delete-complaint')->only('destroy');
    }


    public function store(ComplaintStoreRequest $request)
    {
        $data = $request->validated();
        $order = Order::find($data['order_id']);
        if (!$order) {
            return $this->errorResponse('Order not found.', null, 404);
        }
        $customer = Auth::guard('customer')->user();
        $data['customer_id'] = $customer->id;
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


    public function Complaintreply($complaintId)
    {
        $customerId = Auth::user()->customer->id;

        $replies = ComplaintReply::whereHas('complaint', function ($query) use ($complaintId, $customerId) {
            $query->where('id', $complaintId)
                ->where('customer_id', $customerId)
                ->where('status', 'in_progress');
        })->get();

        return response()->json([
            'replies' => $replies
        ], 200);
    }


    public function update(UpdateComplaintRequest $request, $complaintId)
    {
        try {
            $data = $request->validated();
            $customerId = Auth::guard('customer')->user()->id;
            $complaint = Complaint::id($complaintId)->customerId($customerId)->firstOrFail();
            $complaint->update($data);
            return $this->successResponse('your Complaint updated successfully.');
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Complaint not found', null, 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error.', ['error' => $e->getMessage()]);
        }
    }

    public function show($complaintId)
    {
        $customerId = Auth::guard('customer')->user()->id;
        $complaint = Complaint::id($complaintId)->customerId($customerId)->firstOrFail();
        if (!$complaint) {
            return $this->errorResponse('Complaints not found', null, 404);
        }
        return response()->json([
            'type' => $complaint->type,
            'message' => $complaint->message
        ], 202);
    }


    public function index()
    {
        $customerId = Auth::guard('customer')->user()->id;
        $complaints = Complaint::customerId($customerId)->latest()->paginate(10);
        if (empty($complaints->items())) {
            return $this->errorResponse('there is no complaints', null, 404);
        }
        return $this->successResponse('Complaints :' . $complaints->total(), [IndexComplaintsResource::collection($complaints)], 202);
    }


    public function destroy($complaintId)
    {
        $customerId = Auth::guard('customer')->user()->id;
        $complaint = Complaint::id($complaintId)->customerId($customerId)->firstOrFail();
        if (!$complaint) {
            return $this->errorResponse('Complaints not found', null, 404);
        }
        $complaint->delete();
        return $this->successResponse('complaints deleted successfully.', null, 202);
    }
}
