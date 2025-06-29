<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::post('/logout', [AuthController::class, 'logout']);


Route::post('/order' , [OrderController::class,  'store']);
Route::put('/order/{id}' , [OrderController::class,  'update']);
Route::delete('/order/{id}' , [OrderController::class,  'destroy']);
Route::get('/order/{id}' , [OrderController::class,  'show']);
Route::get('/order/' , [OrderController::class,  'index']);



