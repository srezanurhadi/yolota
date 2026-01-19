<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HidroponikController;

Route::get('/', [HidroponikController::class, 'index']);
Route::post('/', [HidroponikController::class, 'detect']);
// Route untuk Chatbot via Python
Route::post('/api/chat-python', [HidroponikController::class, 'chatPython']);

