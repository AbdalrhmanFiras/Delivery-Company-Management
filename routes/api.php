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


Route::post('/register', [AuthController::class, 'Register']); //
Route::post('/login', [AuthController::class, 'Login']); //
Route::post('/logout', [AuthController::class, 'logout']); //
//?------------------------------------------------------------------------------------------------------------
Route::post('/item', [OrderItemsController::class, 'store']);
Route::prefix('merchant')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/all', [OrderController::class, 'getAllOrder']); //
        Route::get('/all/summary', [OrderController::class, 'getSummaryAll']); //
        Route::get('/delivered/all', [OrderController::class, 'getAllDelivered']); //
        Route::get('/cancel/all', [OrderController::class, 'getAllCancelOrder']); //
        Route::get('/latest', [OrderController::class, 'getlatestOrders']); //
    });
    Route::prefix('warehouse')->group(function () {
        Route::get('cancel/{warehouseId}', [OrderController::class, 'getCancelledWarehouse']); //
        Route::get('delivered/{warehouseId}', [OrderController::class, 'getDeliveredWarehouse']); //
        Route::get('summary/{warehouseId}', [OrderController::class, 'getSummary']); //
        Route::post('store', [MerchantController::class, 'store']); //
        Route::put('{warehouseId}', [MerchantController::class, 'update']); //
        Route::delete('{warehouseId}', [MerchantController::class, 'destroy']); //
    });
});
//?------------------------------------------------------------------------------------------------------------
Route::apiResources([
    'orders' => OrderController::class, //
    'employees' => EmployeeController::class //
]);
//?------------------------------------------------------------------------------------------------------------
Route::middleware(['auth:api'])->group(function () {
    Route::prefix('delivery-company')->group(function () {
        Route::prefix('orders')->group(function () {
            Route::get('/assign-order', [DeliveryCompanyOrderController::class, 'getOrderAssign']); //
            Route::get('all', [DeliveryCompanyOrderController::class, 'getAllOrder']); //
            Route::get('summary', [DeliveryCompanyOrderController::class, 'getSummary']); //
            Route::post('receive/{orderid}', [DeliveryCompanyOrderController::class, 'receiveOrder']); //
            Route::post('auto-assign-driver/{orderId}', [DeliveryCompanyOrderController::class, 'autoAssignDriver']); //
            Route::post('assign-driver/{orderId}', [DeliveryCompanyOrderController::class, 'assignOrderDriver']); //
            Route::get('{orderid}', [DeliveryCompanyOrderController::class, 'getOrder']); //
            Route::post('stuck', [DeliveryCompanyController::class, 'getStuckOrders']); //
        });
        Route::prefix('drivers')->group(function () {

            Route::get('all', [DeliveryCompanyController::class, 'getDrivers']); //
            Route::get('available', [DeliveryCompanyController::class, 'getAvailableDriver']); //
            Route::get('best', [DeliveryCompanyController::class, 'getBestDrivers']); //
            Route::get('avg', [DeliveryCompanyController::class, 'getAvgDrivers']); //
            Route::get('order/{driverId}', [DeliveryCompanyController::class, 'getDriverOrders']); //
            Route::put('update/driver/{driverId}', [DeliveryCompanyController::class, 'UpdateDriver']); //
            Route::get('summary/{driverId}', [DeliveryCompanyController::class, 'getDriverSummery']); //
            Route::post('toggle/{driverId}', [DeliveryCompanyController::class, 'toggleAvailability']); //
            Route::delete('destroy/driver/{driverId}', [DeliveryCompanyController::class, 'destroyDriver']); //
        });
        Route::get('employee/all', [DeliveryCompanyController::class, 'getEmployees']); //
        Route::get('employee/{employeeId}', [DeliveryCompanyController::class, 'getEmployee']); //
    });
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('driver')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('receive/{orderId}', [DriverController::class, 'receiveOrder']); //
        Route::get('{orderId}', [DriverController::class, 'getOrders']); //
        Route::post('cancel/{orderId}', [DriverController::class, 'markCancel']); //
        Route::post('failed/{orderId}', [DriverController::class, 'markFailed']); //
        Route::post('out-delivery/{orderId}', [DriverController::class, 'assignOutDelivery']); //
        Route::get('{tracknumber}', [DriverController::class, 'searchByTrackNumber']); //
        Route::get('delivered', [DriverController::class, 'getDeliverd']); //
        Route::get('for-delivery', [DriverController::class, 'getOutForDelivery']); //
        Route::get('cancel', [DriverController::class, 'getCancel']); //
        Route::post('assign-delivered/{orderId}', [DriverController::class, 'assignDelivery']); //
    });
    Route::post('/login', [DriverController::class, 'login']); //
    Route::post('not-available', [DriverController::class, 'notAvailable']); //
    Route::get('summary', [DriverController::class, 'getOrderSummary']); //
    Route::get('rating', [DriverController::class, 'getRating']); //
    Route::get('count-rating', [DriverController::class, 'countRating']); //
});
//?------------------------------------------------------------------------------------------------------------

// guard here
Route::post('customer/login', [CustomerController::class, 'loginWithOutOrder']); //
Route::post('customer/login/order', [CustomerController::class, 'login']); //
Route::post('customer/verify', [CustomerController::class, 'verifyOtp']); //

Route::prefix('customer')->middleware(['auth:customer', 'role:customer'])->group(function () { //
    Route::get('orders', [CustomerController::class, 'getOrders']); //
    Route::get('order/track', [CustomerController::class, 'trackOrder']); //
    Route::get('compelete-orders', [CustomerController::class, 'getCompeleteOrders']); //
    Route::put('order/cancel/{orderId}', [CustomerController::class, 'cancelOrder']); //
    Route::get('reply/{complaintId}', [CustomerController::class, 'getResponse']); //
    Route::apiResource('complaints', ComplaintController::class); //
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('admin')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('/assign-company/{orderId}', [AdminController::class, 'AssignOrderToDeliveryCompany']); //
        Route::get('/governorate', [AdminController::class, 'getAllOrderbyGovernorate']); //
        Route::get('/merchant', [AdminController::class, 'getAllMerchantOrder']); //
        Route::get('/assign', [AdminController::class, 'getAllAssignOrder']); //
        Route::get('/merchant/assign', [AdminController::class, 'getAllMerchantAssignOrder']); //
        Route::get('/merchant/warehouse', [AdminController::class, 'getAllOrdersWarehouse']); //
        //?
        Route::post('/failed/assign/{orderId}', [AdminController::class, 'AssignFailedOrderToDeliveryCompany']); //
        Route::get('/all/cancel', [AdminController::class, 'getAllCancelOrder']); //
        Route::get('/complaints', [AdminController::class, 'getComplaints']); //
        Route::post('/complaints/mark-closed/{complaint}', [AdminController::class, 'markComplaintClosed']); //
        Route::get('/complaints/filter', [AdminController::class, 'getComplaintsFilters']); //
        Route::post('/complaints/reply/{complaintId}', [AdminController::class, 'replyToComplaint']); //
        Route::put('/{order}', [AdminController::class, 'update']); //
        Route::get('/late', [AdminController::class, 'getLateOrders']); //
        Route::get('/all', [AdminController::class, 'getAllOrder']); //
        Route::get('/failed', [AdminController::class, 'getAllFailedOrder']); //
        //?
        Route::get('/logs', [AdminController::class, 'getLogs']); //
        Route::get('/logs/{orderLogs}', [AdminController::class, 'getOrderLogs']); //
        Route::get('/merchant/{merchantId}/order/{orderId}/logs', [AdminController::class, 'getOrderLogs']); //
        Route::get('/delivered', [AdminController::class, 'getAllDeliveredOrders']); //
        Route::get('/logs', [AdminController::class, 'getLogs']); //
        Route::get('/logs/{orderId}', [AdminController::class, 'getOrderLogs']); //
    });
    Route::prefix('delivery-company')->group(function () {
        Route::post('/add', [AdminController::class, 'addDeliveryCompany']); //
        Route::get('/all', [AdminController::class, 'getAllDeliveryCompany']); //
        Route::get('/summary/{deliveryCompany}', [AdminController::class, 'getDeliveryComapnySummary']); //
        Route::get('/{deliveryCompany}', [AdminController::class, 'getDeliveryCompany']); //
        Route::get('governorate/{governorate}', [AdminController::class, 'deliveryCompaniesByGovernorate']); //
        Route::get('status/{status}', [AdminController::class, 'deliveryCompaniesBystatus']); //
        Route::put('/{deliveryCompany}', [AdminController::class, 'updateDeliveryCompany']); //
        Route::delete('/{deliveryCompany}', [AdminController::class, 'destroyDeliveryCompany']); //
    });
});
