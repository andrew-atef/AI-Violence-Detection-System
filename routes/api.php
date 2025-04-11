<?php

use App\Http\Controllers\FlaskSettingController;
use App\Http\Controllers\ViolenceNotificationController;
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
Route::get('/gest', function (Request $request) {
    return response()->json(['message' => 'unauthorized to access']);
})->name('gest');

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return response()->json(['message' => 'Welcome to the dashboard']);
    })->middleware('role:user');

    Route::get('/admin', function (Request $request) {
        return response()->json(['message' => 'you are admin']);
    })->middleware(['role:admin']);
});


Route::get('/flask-url', [FlaskSettingController::class, 'getFlaskUrl']);

Route::post('/send-video', [FlaskSettingController::class, 'sendVideoToFlask'])
    ->middleware(['auth:api', 'role:user|admin']);

Route::post('/analyzeVideoWithGemini', [FlaskSettingController::class, 'analyzeVideoWithGemini'])
    ->middleware(['auth:api', 'role:user|admin']);

Route::get('/violence-notifications', [ViolenceNotificationController::class, 'index'])
    ->middleware(['auth:api', 'role:user|admin']);
