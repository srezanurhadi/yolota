<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HidroponikController;

Route::get('/detect', [HidroponikController::class, 'index']);
Route::post('/detect', [HidroponikController::class, 'detect']);
Route::get('/', function () {
    return view('welcome');
});
