<?php

use App\Http\Controllers\FlaskSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/set-url', [FlaskSettingController::class, 'showForm'])->name('set.url');
Route::post('/set-url', [FlaskSettingController::class, 'storeUrl'])->name('store.public.url');
