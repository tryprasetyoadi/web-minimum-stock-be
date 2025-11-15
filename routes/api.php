<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\JenisController;
use App\Http\Controllers\MerkController;
use App\Http\Controllers\ShipmentController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Contoh route terproteksi (opsional):
// Route::get('/user', fn (\Illuminate\Http\Request $request) => $request->user())
//     ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::match(['put','patch'], '/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/{user}/restore', [UserController::class, 'restore']);

    // Role endpoints
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::match(['put','patch'], '/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    // Master Jenis endpoints
    Route::get('/jenis', [JenisController::class, 'index']);
    Route::get('/jenis/{jenis}', [JenisController::class, 'show']);
    Route::post('/jenis', [JenisController::class, 'store']);
    Route::match(['put','patch'], '/jenis/{jenis}', [JenisController::class, 'update']);
    Route::delete('/jenis/{jenis}', [JenisController::class, 'destroy']);

    // Master Merk endpoints
    Route::get('/merks', [MerkController::class, 'index']);
    Route::get('/merks/{merk}', [MerkController::class, 'show']);
    Route::post('/merks', [MerkController::class, 'store']);
    Route::match(['put','patch'], '/merks/{merk}', [MerkController::class, 'update']);
    Route::delete('/merks/{merk}', [MerkController::class, 'destroy']);

    // Shipment endpoints
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::match(['put','patch'], '/shipments/{shipment}', [ShipmentController::class, 'update']);
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy']);

    // Chat endpoints
    Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index']);
    Route::post('/conversations', [\App\Http\Controllers\ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\ConversationController::class, 'send']);
});