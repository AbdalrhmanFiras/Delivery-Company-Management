<?php

use App\Models\OrderLog;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OrderlogController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\WareHouseController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\WarehouseOrderController;
use App\Http\Controllers\DeliveryCompanyController;
use App\Http\Controllers\DeliveryCompanyOrderController;
use GuzzleHttp\Middleware;

Route::post('/register', [AuthController::class, 'Register']); //
Route::post('/login', [AuthController::class, 'Login']); //
Route::post('/logout', [AuthController::class, 'logout']); //
//?------------------------------------------------------------------------------------------------------------
Route::post('/item', [OrderItemsController::class, 'store']);
Route::prefix('merchant')->middleware(['auth:api', 'role:merchant|super_admin'])->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/all', [OrderController::class, 'getAllOrder'])->middleware('permission:get-orders');
        Route::get('/cancel/{orderId}', [OrderController::class, 'getCancelOrder'])->middleware('permission:get-cancelled-order');
        Route::get('/summary/all', [OrderController::class, 'getSummaryAll'])->middleware('permission:get-summary');
        Route::get('/delivered/all', [OrderController::class, 'getAllDelivered'])->middleware('permission:get-delivered');
        Route::get('/cancel/all', [OrderController::class, 'getAllCancelOrder'])->middleware('permission:get-cancelled');
        Route::get('/latest', [OrderController::class, 'getlatestOrders'])->middleware('permission:get-latest');
    });
    Route::prefix('warehouse')->group(function () {
        Route::get('cancel/{warehouseId}', [OrderController::class, 'getCancelledWarehouse'])->middleware('permission:get-warehouse-cancelled');
        Route::get('delivered/{warehouseId}', [OrderController::class, 'getDeliveredWarehouse'])->middleware('permission:get-warehouse-delivered');
        Route::get('summary/{warehouseId}', [OrderController::class, 'getSummary'])->middleware('permission:get-warehouse-summary');
        Route::post('store', [MerchantController::class, 'store'])->middleware('permission:create-warehouse');
        Route::put('{warehouseId}', [MerchantController::class, 'update'])->middleware('permission:update-warehouse');
        Route::delete('{warehouseId}', [MerchantController::class, 'destroy'])->middleware('permission:delete-warehouse');
    });
}); //! DONE    
//?------------------------------------------------------------------------------------------------------------
Route::apiResources([
    'orders' => OrderController::class, //
    'employees' => EmployeeController::class //
]);
//?------------------------------------------------------------------------------------------------------------
Route::middleware(['auth:api', 'role:employee|super_admin_dc'])->group(function () {
    Route::prefix('delivery-company')->group(function () {
        Route::prefix('orders')->group(function () {
            Route::get('/assign-order', [DeliveryCompanyOrderController::class, 'getOrderAssign'])->middleware('permission:get-assign-order');
            Route::get('all', [DeliveryCompanyOrderController::class, 'getAllOrder'])->middleware('permission:get-orders');
            Route::get('summary', [DeliveryCompanyOrderController::class, 'getSummary'])->middleware('permission:get-summary');
            Route::post('receive/{orderid}', [DeliveryCompanyOrderController::class, 'receiveOrder'])->middleware('permission:receive-order');
            Route::post('auto-assign-driver/{orderId}', [DeliveryCompanyOrderController::class, 'autoAssignDriver'])->middleware('permission:auto-assign-driver');
            Route::post('assign-driver/{orderId}', [DeliveryCompanyOrderController::class, 'assignOrderDriver'])->middleware('permission:assign-driver');
            Route::get('{orderid}', [DeliveryCompanyOrderController::class, 'getOrder'])->middleware('permission:get-order');
            Route::post('stuck', [DeliveryCompanyController::class, 'getStuckOrders'])->middleware('permission:get-stuck');
        });
        Route::prefix('drivers')->group(function () {

            Route::get('all', [DeliveryCompanyController::class, 'getDrivers'])->middleware('permission:get-drivers');
            Route::get('{driverId}', [DeliveryCompanyController::class, 'getDriver'])->middleware('permission:get-driver');
            Route::get('available', [DeliveryCompanyController::class, 'getAvailableDriver'])->middleware('permission:get-available');
            Route::get('best', [DeliveryCompanyController::class, 'getBestDrivers'])->middleware('permission:get-best');
            Route::get('avg', [DeliveryCompanyController::class, 'getAvgDrivers'])->middleware('permission:get-avg');
            Route::get('order/{driverId}', [DeliveryCompanyController::class, 'getDriverOrders'])->middleware('permission:get-driver-orders');
            Route::put('update/driver/{driverId}', [DeliveryCompanyController::class, 'UpdateDriver'])->middleware('permission:update-driver');
            Route::get('summary/{driverId}', [DeliveryCompanyController::class, 'getDriverSummery'])->middleware('permission:get-driver-summary');
            Route::post('toggle/{driverId}', [DeliveryCompanyController::class, 'toggleAvailability'])->middleware('permission:get-driver-toggle');
            Route::delete('destroy/driver/{driverId}', [DeliveryCompanyController::class, 'destroyDriver'])->middleware('permission:delete-driver');
        });
    });
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('driver')->middleware('auth:api', 'role:driver|super_admin')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('receive/{orderId}', [DriverController::class, 'receiveOrder'])->middleware('permission:receive-order');
        Route::get('{orderId}', [DriverController::class, 'getOrders'])->middleware('permission:get-order');
        Route::post('cancel/{orderId}', [DriverController::class, 'markCancel'])->middleware('permission:cancel-order');
        Route::post('failed/{orderId}', [DriverController::class, 'markFailed'])->middleware('permission:failed-order');
        Route::post('out-delivery/{orderId}', [DriverController::class, 'assignOutDelivery'])->middleware('permission:out-delivery-order');
        Route::get('{tracknumber}', [DriverController::class, 'searchByTrackNumber'])->middleware('permission:tracknumber-order');
        Route::get('delivered', [DriverController::class, 'getDeliverd'])->middleware('permission:get-delivered-order');
        Route::get('for-delivery', [DriverController::class, 'getOutForDelivery'])->middleware('permission:get-for-delivery-order');
        Route::get('cancel', [DriverController::class, 'getCancel'])->middleware('permission:get-cancel-order');
        Route::post('assign-delivered/{orderId}', [DriverController::class, 'assignDelivery'])->middleware('permission:assign-delivered');
    });
    Route::post('/login', [DriverController::class, 'login']);
    Route::post('not-available', [DriverController::class, 'notAvailable'])->middleware('permission:not-available');
    Route::get('summary', [DriverController::class, 'getOrderSummary'])->middleware('permission:summary');
    Route::get('rating', [DriverController::class, 'getRating'])->middleware('permission:rating');
    Route::get('count-rating', [DriverController::class, 'countRating'])->middleware('permission:summary-rating');
});
//?------------------------------------------------------------------------------------------------------------

// guard here
Route::post('customer/login', [CustomerController::class, 'loginWithOutOrder']);
Route::post('customer/login/order', [CustomerController::class, 'login']);
Route::post('customer/verify', [CustomerController::class, 'verifyOtp']);

Route::prefix('customer')->middleware(['auth:customer', 'role:customer'])->group(function () {
    Route::get('orders', [CustomerController::class, 'getOrders'])->middleware('permission:get-orders');
    Route::get('order/track', [CustomerController::class, 'trackOrder'])->middleware('permission:track');
    Route::get('compelete-orders', [CustomerController::class, 'getCompeleteOrders'])->middleware('permission:get-compelete');
    Route::put('order/cancel/{orderId}', [CustomerController::class, 'cancelOrder'])->middleware('permission:cancel');
    Route::get('reply/{complaintId}', [CustomerController::class, 'getResponse'])->middleware('permission:get-reply');
    Route::apiResource('complaints', ComplaintController::class);
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('admin')->middleware(['auth:api|super_admin'])->group(function () {
    Route::prefix('orders')->group(function () {
        //?  Admin_order
        Route::get('/{orderId}', [AdminController::class, 'getOrder'])->middleware('permission:get-order');

        Route::post('/assign-company/{orderId}', [AdminController::class, 'AssignOrderToDeliveryCompany'])->middleware('permission:assign-order');

        Route::get('/governorate', [AdminController::class, 'getAllOrderbyGovernorate'])->middleware('permission:get-governorate-orders');

        Route::get('/merchant', [AdminController::class, 'getAllMerchantOrder'])->middleware('permission:get-merchant-orders');

        Route::get('/assign', [AdminController::class, 'getAllAssignOrder'])->middleware('permission:get-assign-orders');

        Route::get('/merchant/assign', [AdminController::class, 'getAllMerchantAssignOrder'])->middleware('permission:get-merchant-assign-orders');

        Route::get('/merchant/warehouse', [AdminController::class, 'getAllOrdersWarehouse'])->middleware('permission:get-warehouse-orders');

        Route::get('/all', [AdminController::class, 'getAllOrder'])->middleware('permission:get_orders');
        //? Admin_support

        Route::post('/failed/assign/{orderId}', [AdminController::class, 'AssignFailedOrderToDeliveryCompany'])->middleware('permission:assign-failed-orders-agian');

        Route::get('/all/cancel', [AdminController::class, 'getAllCancelOrder'])->middleware('permission:get-cancelled-orders');

        Route::get('/complaints', [AdminController::class, 'getComplaints'])->middleware('permission:get-complaints');

        Route::post('/complaints/mark-closed/{complaint}', [AdminController::class, 'markComplaintClosed'])->middleware('permission:mark-complaint-closed');

        Route::get('/complaints/filter', [AdminController::class, 'getComplaintsFilters'])->middleware('permission:get-compalaint-filter');

        Route::post('/complaints/reply/{complaintId}', [AdminController::class, 'replyToComplaint'])->middleware('permission:reply-complaint');

        Route::put('/{order}', [AdminController::class, 'update'])->middleware('permission:update-orders');

        Route::get('/late', [AdminController::class, 'getLateOrders'])->middleware('permission:get-late-orders');

        Route::get('/failed', [AdminController::class, 'getAllFailedOrder'])->middleware('permission:get-falied-orders');

        //? Admin_check
        Route::get('/logs', [AdminController::class, 'getLogs'])->middleware('permission:get-logs-orders');

        Route::get('/merchant/{merchantId}/order/{orderId}/logs', [AdminController::class, 'getOrderLogs'])->middleware('permission:get-merchant-logs');

        Route::get('/delivered', [AdminController::class, 'getAllDeliveredOrders'])->middleware('permission:get-delivered-orders');

        Route::get('/logs/{orderId}', [AdminController::class, 'getOrderLogs'])->middleware('permission:get-logs-order');
    });
    //? Admin_manager
    Route::prefix('delivery-company')->group(function () {
        Route::post('/add', [AdminController::class, 'addDeliveryCompany'])->middleware('permission:add-delivery-company');

        Route::get('/all', [AdminController::class, 'getAllDeliveryCompany'])->middleware('permission:get-delivery-companies');

        Route::get('/summary/{deliveryCompany}', [AdminController::class, 'getDeliveryComapnySummary'])->middleware('permission:get-summary-delivery-companies');

        Route::get('/{deliveryCompany}', [AdminController::class, 'getDeliveryCompany'])->middleware('permission:get-delivery-company');

        Route::get('governorate/{governorate}', [AdminController::class, 'deliveryCompaniesByGovernorate'])->middleware('permission:get-governorate-delivery-companies');

        Route::get('status/{status}', [AdminController::class, 'deliveryCompaniesBystatus'])->middleware('permission:get-status-delivery-companies');

        Route::put('/{deliveryCompany}', [AdminController::class, 'updateDeliveryCompany'])->middleware('permission:update-delivery-company');

        Route::delete('/{deliveryCompany}', [AdminController::class, 'destroyDeliveryCompany'])->middleware('permission:delete-delivery-company');
    });
});
