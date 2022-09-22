<?php

use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
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

Route::post('user/register', [UserController::class, 'register']);
Route::post('verify/email', [UserController::class, 'verifyEmail']);
Route::post('login', [UserController::class, 'login']);
Route::post('wallet/create', [WalletController::class, 'create']);
Route::post('transfer', [TransactionsController::class, 'transfer']);
Route::post('transaction/history', [TransactionsController::class, 'myHistory']);
