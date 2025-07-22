<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryCompanyOrderController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WareHouseController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\OrderlogController;
use App\Http\Controllers\WarehouseOrderController;
use App\Models\OrderItem;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::post('/logout', [AuthController::class, 'logout']);

//?------------------------------------------------------------------------------------------------------------

Route::prefix('orders')->group(function () {
    Route::get('/notsent-order', [OrderController::class, 'getnotSentOrder']);
    Route::get('/all', [OrderController::class, 'getAllOrder']);
    Route::get('/sent-order', [OrderController::class, 'getSentOrder']);
});
//?------------------------------------------------------------------------------------------------------------
Route::post('/item', [OrderItemsController::class, 'store']);
Route::apiResources([
    'orders' => OrderController::class, //merchant
    'employees' => EmployeeController::class
]);
//?------------------------------------------------------------------------------------------------------------

Route::prefix('warehouse')->group(function () {
    Route::post('/', [WarehouseController::class, 'store']);
    Route::post('orders/{orderid}/receive', [WarehouseOrderController::class, 'receiveOrder']);
    Route::post('orders/{merchantid}/auto/receive', [WarehouseOrderController::class, 'receiveAllMerchantOrdersAuto']);
    Route::get('orders/{merchantid}/receive', [WarehouseOrderController::class, 'receiveAllMerchantOrders']);
    Route::get('orders/{merchantid}/get/receive', [WarehouseOrderController::class, 'getAllMerchantOrderBeforeAccepet']);

    Route::get('orders/{orderId}', [WarehouseOrderController::class, 'getOrder']);
    Route::get('orders/', [WarehouseOrderController::class, 'getAllOrder']);
    Route::get('orders/merchant/{merchantId}', [WarehouseOrderController::class, 'getAllMerchantOrder']);
    Route::post('orders/{orderid}/assign', [WarehouseOrderController::class, 'assignOrder']);

    Route::prefix('delivery_company')->group(function () {
        Route::post('/', [WarehouseController::class, 'addDeliveryCompany']);
        Route::get('/', [WarehouseController::class, 'getAllDeliveryCompany']);
        Route::get('/{delivery_company}', [WarehouseController::class, 'getDeliveryCompany']);
        Route::get('governorate/{governorate}', [WarehouseController::class, 'deliveryCompaniesByGovernorate']);
        Route::get('status/{status}', [WarehouseController::class, 'deliveryCompaniesBystatus']);
        Route::put('/{delivery_company}', [WarehouseController::class, 'updateDeliveryCompany']);
        Route::delete('/{delivery_company}', [WarehouseController::class, 'destroyDeliveryCompany']);
    });
});
//?------------------------------------------------------------------------------------------------------------
Route::middleware(['auth:api', 'employee.delivery'])->group(function () {
    Route::prefix('delivery-company')->group(function () {
        Route::get('orders/assign-order', [DeliveryCompanyOrderController::class, 'getOrderAssign']);
        Route::get('orders', [DeliveryCompanyOrderController::class, 'getAllOrder']);
        Route::get('orders/summary', [DeliveryCompanyOrderController::class, 'getSummary']);


        Route::post('orders/{orderid}/receive', [DeliveryCompanyOrderController::class, 'receiveOrder']);
        Route::post('orders/{orderId}/auto-assign-driver', [DeliveryCompanyOrderController::class, 'autoAssignDriver']);
        Route::post('orders/{orderId}/assign-driver', [DeliveryCompanyOrderController::class, 'assignOrderDriver']);
        Route::get('orders/{orderid}', [DeliveryCompanyOrderController::class, 'getOrder']);
    });
});
//?------------------------------------------------------------------------------------------------------------

Route::post('driver/login', [DriverController::class, 'login']);


//?------------------------------------------------------------------------------------------------------------

Route::post('customer/login', [CustomerController::class, 'login']);
Route::post('customer/verify', [CustomerController::class, 'verifyOtp']);
Route::get('customer/order/track', [CustomerController::class, 'trackOrder']);
Route::get('customer/orders', [CustomerController::class, 'getOrders']);
Route::get('customer/compelete-orders', [CustomerController::class, 'getCompeleteOrders']);
Route::get('customer/current-orders', [CustomerController::class, 'getCurrentOrders']);
Route::put('customer/order/cancel', [CustomerController::class, 'cancelOrder']);

//?------------------------------------------------------------------------------------------------------------

Route::prefix('merchant')->group(function () {
    Route::post('/send-order/{orderid}', [MerchantController::class, 'sendToWarehouse']);
    Route::post('/send-all', [MerchantController::class, 'sentAllToWarehouse']);
    Route::delete('delete/{orderid}', [MerchantController::class, 'delete']);
    Route::get('logs/{merchant}', [OrderlogController::class, 'logs']);
});
