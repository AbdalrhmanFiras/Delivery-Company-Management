<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WareHouseController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\OrderlogController;
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




Route::prefix('orders')->group(function () {
    Route::get('/notsent-order', [OrderController::class, 'getnotSentOrder']);
    Route::get('/all', [OrderController::class, 'getAllOrder']);
    Route::get('/sent-order', [OrderController::class, 'getSentOrder']);
});

Route::apiResource('orders', OrderController::class);
Route::post('/item', [OrderItemsController::class, 'store']);

Route::prefix('warehouse')->group(function () {
    Route::post('/', [WarehouseController::class, 'store']);
    Route::post('/delivery_company', [WarehouseController::class, 'addDeliveryCompany']);
    Route::get('/delivery_company', [WarehouseController::class, 'getAllDeliveryCompany']);
    Route::get('/delivery_company/{delivery_company}', [WarehouseController::class, 'getDeliveryCompany']);
    Route::put('/delivery_company/{delivery_company}/', [WarehouseController::class, 'updateDeliveryCompany']);
    Route::delete('/delivery_company/{delivery_company}/', [WarehouseController::class, 'destroyDeliveryCompany']);
});


Route::apiResource('employees', EmployeeController::class);

Route::prefix('merchant')->group(function () {
    Route::post('/{orderid}/send-order', [MerchantController::class, 'sendToWarehouse']);
    Route::post('/send-all', [MerchantController::class, 'sentAllToWarehouse']);
    Route::delete('/{orderid}/delete', [MerchantController::class, 'deleteNotSent']);
    Route::get('/{merchant}/logs', [OrderlogController::class, 'logs']);
});
