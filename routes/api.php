<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WareHouseController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\OrderlogController;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::post('/logout', [AuthController::class, 'logout']);


Route::apiResource('orders', OrderController::class);

Route::post('/item', [OrderItemsController::class, 'store']);
Route::post('/warehouse', [WarehouseController::class, 'store']);

Route::prefix('merchant')->group(function () {
    Route::post('/{orderid}/send-order', [MerchantController::class, 'sendToWarehouse']);
    Route::post('//send-all', [MerchantController::class, 'sentAllToWarehouse']);
    Route::get('/sent-order', [MerchantController::class, 'getSentOrder']);
    Route::get('/notsent-order', [MerchantController::class, 'getnotSentOrder']);
    Route::get('/all', [MerchantController::class, 'getAllOrder']);
    Route::delete('/{orderid}/delete', [MerchantController::class, 'deleteNotSent']);
    Route::get('/{merchant}/logs', [OrderlogController::class, 'logs']);
});
