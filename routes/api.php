<?php

use App\Http\Controllers\Api\ComprobanteApiController;
use App\Http\Controllers\Api\EncomiendaApiController;
use App\Http\Controllers\Auth\AutenticacionController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AutenticacionController::class, 'apiLogin']);
        Route::post('/forgot-password', [RecuperarContrasenaController::class, 'apiForgotPassword']);
    });

    Route::post('/encomiendas', [EncomiendaApiController::class, 'store']);
    Route::get('/gastos-comunes', [ComprobanteApiController::class, 'listarGastos']);
    Route::post('/comprobantes', [ComprobanteApiController::class, 'store']);
});
