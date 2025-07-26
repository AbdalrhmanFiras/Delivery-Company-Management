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


Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::post('/logout', [AuthController::class, 'logout']);
//?------------------------------------------------------------------------------------------------------------
Route::post('/item', [OrderItemsController::class, 'store']);
Route::prefix('merchant')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/all', [OrderController::class, 'getAllOrder']);
        Route::get('/all/summary', [OrderController::class, 'getSummaryAll']);
        Route::get('/delivered/all', [OrderController::class, 'getAllDelivered']);
        Route::get('warehouse/summary', [OrderController::class, 'getSummary']);
        Route::get('warehouse/delivered', [OrderController::class, 'getDeliveredWarehouse']);
        Route::get('warehouse/cancel', [OrderController::class, 'getCancelddWarehouse']);
        Route::get('warehouse/latest', [OrderController::class, 'getlatestWarehouse']);
        Route::get('/cancel/all', [OrderController::class, 'getAllCancelOrder']);
        Route::get('/latest', [OrderController::class, 'getlatestOrders']);
    });
    Route::post('/warehouse/{warehouseId}', [MerchantController::class, 'update']);
    Route::post('/warehouse/store', [MerchantController::class, 'store']);
    Route::post('/warehouse/{warehouseId}', [MerchantController::class, 'destroy']);
});
//?------------------------------------------------------------------------------------------------------------
Route::apiResources([
    'orders' => OrderController::class, //merchant
    'employees' => EmployeeController::class
]);
//?------------------------------------------------------------------------------------------------------------
Route::middleware(['auth:api', 'employee.delivery'])->group(function () {
    Route::prefix('delivery-company')->group(function () {
        Route::prefix('order')->group(function () {
            Route::get('/assign-order', [DeliveryCompanyOrderController::class, 'getOrderAssign']);
            Route::get('all', [DeliveryCompanyOrderController::class, 'getAllOrder']);
            Route::get('summary', [DeliveryCompanyOrderController::class, 'getSummary']);
            Route::post('{orderid}/receive', [DeliveryCompanyOrderController::class, 'receiveOrder']);
            Route::post('{orderId}/auto-assign-driver', [DeliveryCompanyOrderController::class, 'autoAssignDriver']);
            Route::post('{orderId}/assign-driver', [DeliveryCompanyOrderController::class, 'assignOrderDriver']);
            Route::get('{orderid}', [DeliveryCompanyOrderController::class, 'getOrder']);
            Route::post('stuck', [DeliveryCompanyController::class, 'getStuckOrders']);
        });
        Route::prefix('driver')->group(function () {

            Route::get('all', [DeliveryCompanyController::class, 'getDrivers']);
            Route::get('available', [DeliveryCompanyController::class, 'getAvailableDriver']);
            Route::get('best', [DeliveryCompanyController::class, 'getBestDrivers']);
            Route::get('avg', [DeliveryCompanyController::class, 'getAvgDrivers']);
            Route::post('update/driver', [DeliveryCompanyController::class, 'UpdateDriver']);
            Route::post('{driverId}/order', [DeliveryCompanyController::class, 'getDriverOrders']);
            Route::post('{driverId}/summary', [DeliveryCompanyController::class, 'getDriverSummery']);
            Route::post('{driverId}/toggle', [DeliveryCompanyController::class, 'toggleAvailability']);
            Route::post('update/driver', [DeliveryCompanyController::class, 'UpdateDriver']);
            Route::get('destroy/driver', [DeliveryCompanyController::class, 'destroyDriver']);
        });
        Route::get('employee/all', [DeliveryCompanyController::class, 'getEmployees']);
        Route::get('employee/{employeeId}', [DeliveryCompanyController::class, 'getEmployee']);
        Route::get('employee/{employeename}', [DeliveryCompanyController::class, 'getEmployeebyName']);
    });
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('driver')->group(function () {
    Route::prefix('order')->group(function () {
        Route::post('receive/{orderId}', [DriverController::class, 'receiveOrder']);
        Route::get('{orderId}', [DriverController::class, 'getOrders']);
        Route::post('cancel/{orderId}', [DriverController::class, 'markCancel']);
        Route::post('failed/{orderId}', [DriverController::class, 'markFailed']);
        Route::post('out-delivery/{orderId}', [DriverController::class, 'assignOutDelivery']);
        Route::post('{tracknumber}', [DriverController::class, 'searchByTrackNumber']);
        Route::get('delivered', [DriverController::class, 'getDeliverd']);
        Route::get('for-delivery', [DriverController::class, 'getOutForDelivery']);
        Route::get('cancel', [DriverController::class, 'getCancel']);
    });
    Route::post('/login', [DriverController::class, 'login']);
    Route::post('not-available', [DriverController::class, 'notAvailable']);
    Route::post('assign-delivered/{orderId}', [DriverController::class, 'assignDelivery']);
    Route::get('summery', [DriverController::class, 'getOrderSummery']);
    Route::get('rating', [DriverController::class, 'getRating']);
    Route::get('count-rating', [DriverController::class, 'countRating']);
});
//?------------------------------------------------------------------------------------------------------------
Route::post('customer/login', [CustomerController::class, 'loginWithOutOrder']);
Route::post('customer/login/order', [CustomerController::class, 'login']);
Route::post('customer/verify', [CustomerController::class, 'verifyOtp']);
Route::prefix('customer')->middleware('auth:customer')->group(function () {
    Route::get('orders', [CustomerController::class, 'getOrders']);
    Route::get('order/track', [CustomerController::class, 'trackOrder']);
    Route::get('compelete-orders', [CustomerController::class, 'getCompeleteOrders']);
    Route::put('order/cancel', [CustomerController::class, 'cancelOrder']);
    Route::apiResource('complaints', ComplaintController::class);
});
//?------------------------------------------------------------------------------------------------------------
Route::prefix('admin')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('/assign-company/{orderId}', [AdminController::class, 'AssignOrderToDeliveryCompany']);
        Route::post('/failed/assign/{orderId}', [AdminController::class, 'AssignFailedOrderToDeliveryCompany']);
        Route::get('/all-cancel', [AdminController::class, 'getAllCancelOrder']);
        Route::get('/complaints', [AdminController::class, 'getComplaints']);
        Route::get('/complaints/mark-closed/{complaint}', [AdminController::class, 'markComplaintClosed']);
        Route::get('/complaints/filter', [AdminController::class, 'getComplaintsFilters']);
        Route::get('/complaints/reply/{complaintId}', [AdminController::class, 'replyToComplaint']);
        Route::post('/{order}', [AdminController::class, 'update']);
        Route::get('/late', [AdminController::class, 'getLateOrders']);
        Route::get('/all', [AdminController::class, 'getAllOrder']);
        Route::get('/delivered', [AdminController::class, 'getAllDeliveredOrders']);
        Route::get('/logs', [AdminController::class, 'getLogs']);
        Route::get('/logs/{orderLogs}', [AdminController::class, 'getOrderLogs']);
        Route::get('/merchant/{merchantId}/order/{orderId}/logs', [AdminController::class, 'getOrderLogs']);
        Route::get('/failed', [AdminController::class, 'getAllFailedOrder']);
        Route::get('/governorate', [AdminController::class, 'getAllOrderbyGovernorate']);
        Route::get('/logs', [AdminController::class, 'getLogs']);
        Route::get('/logs/{orderId}', [AdminController::class, 'getOrderLogs']);
        Route::get('/assign', [AdminController::class, 'getAllAssignOrder']); //? merchant
        Route::get('/merchant/assign', [AdminController::class, 'getAllMerchantAssignOrder']);
        Route::get('/merchant', [AdminController::class, 'getAllMerchantOrder']);
        Route::get('/merchant/warehouse', [AdminController::class, 'getAllOrdersWarehouse']);

        Route::prefix('delivery-company')->group(function () {
            Route::post('/add', [AdminController::class, 'addDeliveryCompany']);
            Route::get('/all', [AdminController::class, 'getAllDeliveryCompany']);
            Route::get('/{delivery_company}', [AdminController::class, 'getDeliveryCompany']);
            Route::get('governorate/{governorate}', [AdminController::class, 'deliveryCompaniesByGovernorate']);
            Route::get('status/{status}', [AdminController::class, 'deliveryCompaniesBystatus']);
            Route::put('/{delivery_company}', [AdminController::class, 'updateDeliveryCompany']);
            Route::delete('/{delivery_company}', [AdminController::class, 'destroyDeliveryCompany']);
        });
    });
});
