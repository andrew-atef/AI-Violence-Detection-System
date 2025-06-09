<?php

use App\Http\Controllers\FlaskSettingController;
use App\Http\Controllers\ViolenceNotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\UserController;
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

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Guest route
Route::get('/guest', function (Request $request) {
    return response()->json(['message' => 'unauthorized to access']);
})->name('guest');

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User profile routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'getUser']);
    });

    // Role-based routes
    Route::middleware('role:user')->group(function () {
        Route::get('/dashboard', function (Request $request) {
            return response()->json(['message' => 'Welcome to the dashboard']);
        });
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', function (Request $request) {
            return response()->json(['message' => 'you are admin']);
        });

        // User management routes for admin
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });

        // Camera management routes for admin
        Route::prefix('cameras')->group(function () {
            Route::get('/', [CameraController::class, 'index']);
            Route::post('/', [CameraController::class, 'store']);
            Route::get('/{id}', [CameraController::class, 'show']);
            Route::put('/{id}', [CameraController::class, 'update']);
            Route::delete('/{id}', [CameraController::class, 'destroy']);
            Route::post('/{id}/assign', [CameraController::class, 'assignToUser']);
        });
    });

    // Violence detection routes
    Route::middleware('role:user|admin')->group(function () {
        // Flask integration
        Route::post('/send-video', [FlaskSettingController::class, 'sendVideoToFlask']);
        // Route::post('/analyze-video-gemini', [FlaskSettingController::class, 'analyzeVideoWithGemini']);

        // Violence notifications
        Route::prefix('violence-notifications')->group(function () {
            Route::get('/', [ViolenceNotificationController::class, 'index']);
            Route::get('/{id}', [ViolenceNotificationController::class, 'show']);
        });
    });
});

// Public Flask URL route
Route::get('/flask-url', [FlaskSettingController::class, 'getFlaskUrl']);
