<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post("login",[App\Http\Controllers\Auth\AuthController::class,'checkAuth']);

// Authorized Admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('adminprofile', [App\Http\Controllers\AdminController::class, 'index'])->name('adminprofile');
    Route::post('approveloan', [App\Http\Controllers\AdminController::class, 'approvedLoan'])->name('approveloan');
    Route::post("admin_logout",[App\Http\Controllers\Auth\AuthController::class,'admin_logout']);
});

// Authorized Customer
Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::post('customerprofile', [App\Http\Controllers\CustomerController::class, 'index'])->name('customerprofile');
    Route::post('reqloan', [App\Http\Controllers\CustomerController::class, 'requestloan'])->name('reqloan');
    Route::post('repaymentloan', [App\Http\Controllers\CustomerController::class, 'RepaymentAmount'])->name('repaymentloan');
    Route::post("logout",[App\Http\Controllers\Auth\AuthController::class,'logout']);
});
