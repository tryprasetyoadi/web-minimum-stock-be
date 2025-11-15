<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Contoh route terproteksi (opsional):
// Route::get('/user', fn (\Illuminate\Http\Request $request) => $request->user())
//     ->middleware('auth:sanctum');