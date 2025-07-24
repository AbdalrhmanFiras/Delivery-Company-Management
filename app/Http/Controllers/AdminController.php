<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Complaint;
use App\Enums\Governorate;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Enums\ComplaintStatus;
use App\Models\ComplaintReply;
use App\Models\DeliveryCompany;
use Illuminate\Validation\Rule;
use App\Traits\LogsOrderChanges;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\ComplaintsResource;
use App\Http\Requests\AdminWarehouseRequest;
use App\Http\Requests\ReplieComplaintRequest;
use App\Http\Requests\ComplaintsFilterRequest;
use App\Http\Requests\GovernorateFilterRequest;
use App\Http\Requests\VaildateDeliveryCompanyRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpParser\Node\Stmt\TryCatch;

class AdminController extends BaseController
{
    // for admin that can controller the order 
    use LogsOrderChanges;
    public function getAllOrder()
    {

        $orders = Order::orderStatus(1)->whereNotNull('warehouse_id')->cursorPaginate(20);
        if (empty($orders->items())) {
            Log::info('No orders found at warehouse status.');
            return $this->successResponse('there is no order at warehouse status yet.');
        }
        Log::info('Fetched orders at warehouse status.', ['count' => count($orders->items())]);
        return OrderResource::collection($orders);
    }


    public function getAllOrderbyGovernorate(GovernorateFilterRequest $request)
    {
        $data = $request->validated();
        $orders = Order::where('governorate', $data['governorate'])->orderStatus(1)->whereNotNull('warehouse_id')->cursorPaginate(20);
        if (empty($orders->items())) {
            Log::info('No orders found at this governorate.');
            return $this->successResponse('there is no order at this Governorate yet.');
        }
        return OrderResource::collection($orders);
    }


    public function getAllMerchantOrder($merchantId)
    {
        $orders = Order::merchantId($merchantId)->orderStatus(1)->whereNotNull('warehouse_id')->cursorPaginate(20);
        if (count($orders->items()) === 0) {
            Log::info('No orders found for that merchant.');
            return $this->successResponse('there is no order at warehouse status yet.');
        }
        return OrderResource::collection($orders);
    }


    public function getAllMerchantOrderfromWarehouse(AdminWarehouseRequest $request)
    {
        $data = $request->validated();
        $orders = Order::merchantId($data['merchant_id'])->orderStatus(1)->warehouseId($data['warehouse_id'])->cursorPaginate(20);
        if (empty($orders->items())) {
            return $this->successResponse('there is no order at warehouse status yet.');
        }
        return OrderResource::collection($orders);
    }


    public function AssignOrderToDeliveryCompany(VaildateDeliveryCompanyRequest $request, $orderId)
    {
        try {
            $data = $request->validated();
            $deliveryCompany = DeliveryCompany::where('id', $data['delivery_company_id'])
                ->value('governorate');
            $order = Order::id($orderId)->orderStatus(1)->whereNotNull('warehouse_id')->firstOrFail();

            if ($order->delivery_company_id == $data['delivery_company_id']) {
                Log::warning("Attempt to assign order already assigned", ['order_id' => $orderId, 'delivery_company_id' => $data['delivery_company_id']]);
                return $this->errorResponse('This order already assigned to delivery company.', null, 400);
            }

            if ($deliveryCompany == $order->governorate) {
                $originalData = $order->toArray();

                $order->delivery_company_id = $data['delivery_company_id'];
                $order->status = OrderStatus::AssignedDeliveryCompany->value;
                $order->save();

                $this->logOrderChange(
                    $order,
                    'order_assigned_to_delivery_company',
                    $originalData,
                    $order->toArray(),
                    // Auth::user()->name
                );

                $admin = Auth::user();

                Log::info('Order assigned to delivery company', [
                    'order_id' => $order->id,
                    // 'admin_id' => $admin->id,
                    // 'admin_name' => $admin->name,
                    'delivery_company_id' => $data['delivery_company_id'],
                ]);

                return $this->successResponse('Order assigned to delivery company successfully.');
            }

            Log::warning('Delivery company governorate mismatch', [
                'order_id' => $orderId,
                'delivery_company_governorate' => $deliveryCompany,
                'order_governorate' => $order->governorate,
            ]);

            return $this->errorResponse('Delivery company governorate does not match order governorate.', null, 400);
        } catch (ModelNotFoundException) {
            Log::error('Order not found or invalid state', ['order_id' => $orderId]);
            return $this->errorResponse('Order not found or not in a valid state.', 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error in AssignOrderToDeliveryCompany', ['exception' => $e]);
            return $this->errorResponse('Unexpected error occurred.', ['error' => $e->getMessage()], 500);
        }
    }


    public function getAllAssignOrder()
    {
        $orders = Order::orderStatus(2)->whereNotNull('warehouse_id')->cursorPaginate(20);
        if (empty($orders->items())) {
            Log::info('No Assign orders found.');
            return $this->successResponse('there is no Assign order yet.', null, 404);
        }
        return OrderResource::collection($orders);
    }


    public function getAllMerchantAssignOrder($merchantId)
    {
        $orders = Order::merchantId($merchantId)->orderStatus(2)->whereNotNull('warehouse_id')->cursorPaginate(20);
        if (count($orders->items()) === 0) {
            Log::info('No orders found for that merchant.');
            return $this->successResponse('there is no Assign order yet.');
        }
        return OrderResource::collection($orders);
    }

    //?-----------------------------------------------------------------------------------------------------------------------

    // for support admin


    public function getLateOrders()
    {
        $lateOrders = Order::with('merchant')->whereNotNull('merchant_id')->where(function ($query) {
            $query->where(function ($q) {
                $q->where('status', OrderStatus::AtWarehouse->value)
                    ->where('updated_at', '<', now()->subHours(2));
            })->orWhere(function ($q) {
                $q->where('status', OrderStatus::AssignedDeliveryCompany->value)
                    ->where('updated_at', '<', now()->subHours(3));
            })->orWhere(function ($q) {
                $q->where('status', OrderStatus::AssignedDriver->value)
                    ->where('updated_at', '<', now()->subHours(4));
            });
        })->latest()->cursorPaginate(20);

        return OrderResource::collection($lateOrders);
    }


    public function getComplaints()
    {
        Log::info('get All customer Complaints.');
        $complaints = Complaint::latest()->paginate(20);
        return response()->json([
            'complaints' => ComplaintsResource::collection($complaints),
            'meta' => [
                'total' => $complaints->total(),
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'next_page_url' => $complaints->nextPageUrl(),
                'prev_page_url' => $complaints->previousPageUrl(),
            ],
        ]);
    }

    public function replyToComplaint(ReplieComplaintRequest $request, $complaintId)
    {
        try {
            $data = $request->validated();
            $complaint = Complaint::id($complaintId)->complainStatus(ComplaintStatus::Open->value)->firstOrFail();
            $reply = ComplaintReply::create([
                'complaint_id' => $complaint->id,
                'replier_id' => Auth::user()->admin->id,
                'replier_type' => get_class(Auth::user()->admin),
                'message' => $request->validated('message')
            ]);

            $complaint->update(['status' => ComplaintStatus::InProgress->value]);


            Log::info('Complaint Reply', [
                'admin_id' => Auth::user()->admin->id,
                'complaint_id' => $complaint->id,
            ]);
            return $this->successResponse('reply Successfully', null, 202);
        } catch (ModelNotFoundException) {
            Log::warning('Complaint not found', [
                'complaint_id' => $complaintId,
                'admin_id' => Auth::user()->admin->id
            ]);

            return $this->errorResponse('Complaint not found', null, 404);
        }
    }


    public function markComplaintClosed($complaintId)
    {
        try {
            $complaint = Complaint::id($complaintId)->complainStatus(ComplaintStatus::InProgress->value)->firstOrFail();
            $complaint->update(['status' => ComplaintStatus::Closed->value]);
            Log::info('Complaint Status Changed', [
                'admin_id' => Auth::user()->admin->id,
                'complaint_id' => $complaint->id,
                'to_status' => 'Closed',
            ]);
            return $this->successResponse('updated Successfully', null, 202);
        } catch (ModelNotFoundException) {
            Log::warning('Complaint not found when closing', [
                'admin_id' => Auth::user()->admin->id
            ]);
            return $this->errorResponse('Complaint not found', null, 404);
        }
    }


    public function getComplaintsFilters(ComplaintsFilterRequest $request)
    {
        $data = $request->validated();
        $complaintsQuery = Complaint::with('order')->latest();
        $complaintsQuery->whereHas('order', function ($query) use ($data) {
            if (isset($data['merchant_id'])) {
                $query->where('merchant_id', $data['merchant_id']);
            }
            if (isset($data['warehouse_id'])) {
                $query->where('warehouse_id', $data['warehouse_id']);
            }
        });

        $complaints = $complaintsQuery->paginate(20);

        return response()->json([
            'complaints_count' => $complaints->total(),
            'current_page' => $complaints->currentPage(),
            'complaints' => ComplaintsResource::collection($complaints),
            'next_page_url' => $complaints->nextPageUrl(),
            'prev_page_url' => $complaints->previousPageUrl(),
            'last_page' => $complaints->lastPage(),
            'per_page' => $complaints->perPage(),
        ]);
    }


    public function getAllFailedOrder()
    {
        $orders = Order::orderStatus(9)->whereNotNull('warehouse_id')->whereNotNull('delivery_company_id')->whereNotNull('driver_id')->cursorPaginate(20);
        if (empty($orders->items())) {
            Log::info('No orders are failed.');
            return $this->successResponse('there is no failed orders.');
        }
        return OrderResource::collection($orders);
    }


    public function update(UpdateOrderRequest $request, $orderId)
    { //! merchant , Supportadmin
        $merchantId = Auth::user()->merchant->id;
        $data = $request->validated();
        try {
            $order = Order::id($orderId)->merchantId($merchantId)->orderStatus(1)->firstOrFail();
            $originalData = $order->toArray();
            $updated = $order->update($data);
            if (!$updated) {
                return $this->errorResponse('Failed to update order.', null, 422);
            }
            Log::info("Order #{$order->id} updated by merchant {$order->merchant_id}");
            $this->logOrderChange($order, 'order_update', $originalData, $order->toArray());
            return $this->successResponse(
                'Order Updated Successfully',
                [
                    'order' => new OrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            Log::error("Order update failed for order #{$orderId} by merchant {$merchantId}: " . $e->getMessage());
            return $this->errorResponse(
                'Unexpected error.',
                [
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function AssignFailedOrderToDeliveryCompany(VaildateDeliveryCompanyRequest $request, $orderId)
    {
        try {
            $data = $request->validated();
            $deliveryCompany = DeliveryCompany::where('id', $data['delivery_company_id'])
                ->value('governorate');
            $order = Order::id($orderId)->orderStatus(9)->whereNotNull('warehouse_id')->firstOrFail();
            $originalData = $order->toArray();
            if ($deliveryCompany == $order->governorate) {
                Log::warning('Delivery company governorate mismatch');
                $order->delivery_company_id = $data['delivery_company_id'];
                $order->status = OrderStatus::AssignedDeliveryCompany->value;
                $order->save();

                $this->logOrderChange(
                    $order,
                    'failed_order_assign_to_delivery_company',
                    $originalData,
                    $order->toArray(),
                    Auth::user()->name
                );
                $admin = Auth::user();

                Log::info('Order assigned to another delivery company', [
                    'order_id' => $order->id,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'delivery_company_id' => $data['delivery_company_id'],
                ]);
                return $this->successResponse('Order assigned to delivery company second time successfully.');
            }
        } catch (ModelNotFoundException) {
            Log::error('Order not found or invalid state', ['order_id' => $orderId]);
            return $this->errorResponse('Order not found or not in a valid state.', 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error in AssignFailedOrderToDeliveryCompany', ['exception' => $e]);
            return $this->errorResponse('Unexpected error occurred.', ['error' => $e->getMessage()], 500);
        }
    }

    //?-----------------------------------------------------------------------------------------------------------------------
    // tracker admin 
    public function getAllDeliveredOrders()
    {
        $orders = Order::orderStatus(5)->whereNotNull('delivery_company_id')->whereNotNull('driver_id')->latest->cursorPaginate(20);
        if (empty($orders->items())) {
            return $this->successResponse('No delivered orders yet.', null, 404);
        }
        return OrderResource::collection($orders);
    }


    public function getLogs()
    {
        $Logs = OrderLog::paginate(20);
        return $this->successResponse("Logs Count " . $Logs->total(), [$Logs]);
    }


    public function getOrderLogs($orderId)
    {
        if (!Order::id($orderId)->exists()) {
            return $this->errorResponse('This order does not exist.', null, 404);
        }

        $orderLogs = OrderLog::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return $this->successResponse(
            "Order logs retrieved successfully. Total: " . $orderLogs->total(),
            $orderLogs
        );
    }

    public function getMerchantLogs($merchantId, $orderId)
    {
        $order = Order::id($orderId)->merchantId($merchantId)->first();
        if (!$order) {
            return $this->errorResponse('Order not found for this merchant.', null, 404);
        }
        $orderLogs = OrderLog::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return $this->successResponse(
            "Order logs retrieved successfully. Total: " . $orderLogs->total(),
            $orderLogs
        );
    }
}
