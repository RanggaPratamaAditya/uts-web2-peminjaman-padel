<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LapanganController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/lapangan', [
        LapanganController::class,
        'index'
    ]);

    Route::get('/peminjaman', [
        PeminjamanController::class,
        'index'
    ]);

    Route::post('/peminjaman', [
        PeminjamanController::class,
        'store'
    ]);

    Route::get('/peminjaman/{peminjaman}', [
        PeminjamanController::class,
        'show'
    ]);

    Route::put('/peminjaman/{peminjaman}', [
        PeminjamanController::class,
        'update'
    ]);

    Route::middleware('admin')->group(function () {

        Route::put('/peminjaman/{peminjaman}/status', [
            PeminjamanController::class,
            'updateStatus'
        ]);

    });
});