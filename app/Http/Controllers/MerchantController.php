<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Warehouse;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Traits\LogsOrderChanges;
use App\Models\WarehouseReceipts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use App\Jobs\SendAllNotSentOrdersJob;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\WarehouseResource;
use App\Http\Resources\StoreOrderResource;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\AssignWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MerchantController extends BaseController
{


    public function getAllOrder()
    { //! merchant,
        $merchantId = Auth::user()->merchant->id;
        return OrderResource::collection(
            Order::merchantId($merchantId)->paginate(20)
        );
    }


    public function store(StoreWarehouseRequest $request)
    { //! merchant , super admin
        $merchantId = Auth::user()->merchant->id;
        $data = $request->validated();
        $data['merchant_id'] = $merchantId;
        $warehouse = Warehouse::create($data);
        Log::info('warehouse Add successfuly by' . $merchantId);
        return $this->successResponse('Wahreouse Add successfully.', [new WarehouseResource($warehouse)], 202);
    }


    public function update(UpdateWarehouseRequest $request, $warehouseId)
    { //! merchant , super admin
        try {
            $merchantId = Auth::user()->merchant->id;
            $data = $request->validated();
            $warehouse = Warehouse::where('warehouse_id', $warehouseId)->merchantId($merchantId)->firstOrFail();
            $updated = $warehouse->update($data);
            if (!$updated) {
                Log::warning("Warehouse update failed or no changes made. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId}");
                return $this->errorResponse('No changes detected or update failed.', null, 422);
            }
            Log::info("Warehouse updated successfully. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId}", ['updated_fields' => $data]);

            return response()->json(new WarehouseResource($warehouse));
        } catch (ModelNotFoundException) {
            Log::warning("Warehouse not found. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId}");
            return $this->errorResponse('Warehouse not found.', null, 404);
        }
    }


    public function destroy($warehouseId)
    { //! merchant, , super admin
        try {
            $merchantId = Auth::user()->merchant->id;
            $warehouse = Warehouse::where('id', $warehouseId)
                ->merchantId($merchantId)
                ->firstOrFail();

            $warehouse->delete();
            Log::info("Warehouse deleted successfully. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId}");

            return $this->successResponse('Warehouse deleted successfully.');
        } catch (ModelNotFoundException) {
            Log::warning("Attempt to delete non-existent warehouse. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId}");
            return $this->errorResponse('Warehouse not found.', null, 404);
        } catch (\Exception $e) {
            Log::error("Error deleting warehouse. Warehouse ID: {$warehouseId}, Merchant ID: {$merchantId},Error: {$e->getMessage()}");
            return $this->errorResponse('Failed to delete warehouse.', ['error' => $e->getMessage()], 500);
        }
    }
}
