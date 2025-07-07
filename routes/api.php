<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeliveryCompanyOrderController;
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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


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
    'orders' => OrderController::class,
    'employees' => EmployeeController::class
]);

//?------------------------------------------------------------------------------------------------------------

Route::prefix('warehouse')->group(function () {
    Route::post('/', [WarehouseController::class, 'store']);
    Route::post('orders/{orderid}/receive', [WarehouseOrderController::class, 'receiveOrder']);
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
        Route::post('orders/{orderid}/receive', [DeliveryCompanyOrderController::class, 'receiveOrder']);
        Route::get('orders/{orderid}', [DeliveryCompanyOrderController::class, 'getOrder']);
        Route::get('orders/', [DeliveryCompanyOrderController::class, 'getAllOrder']);
    });
});



Route::prefix('merchant')->group(function () {
    Route::post('/send-order/{orderid}', [MerchantController::class, 'sendToWarehouse']);
    Route::post('/send-all', [MerchantController::class, 'sentAllToWarehouse']);
    Route::delete('delete/{orderid}', [MerchantController::class, 'delete']);
    Route::get('logs/{merchant}', [OrderlogController::class, 'logs']);
});
