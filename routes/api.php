<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return response()->json(['message' => 'Welcome to the dashboard']);
    })->middleware('role:user');

    Route::get('/admin', function (Request $request) {
        return response()->json(['message' => 'Admin access only']);
    })->middleware('role:admin');
});
