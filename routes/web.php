<?php

use App\Http\Controllers\Auth\AutenticacionController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AutenticacionController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AutenticacionController::class, 'iniciarSesion'])->name('login.post');

    // Recuperación de contraseña (HU-38)
    Route::get('/recuperar-contrasena', [RecuperarContrasenaController::class, 'mostrarFormularioSolicitud'])->name('password.request');
    Route::post('/recuperar-contrasena', [RecuperarContrasenaController::class, 'enviarTokenRecuperacion'])->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [RecuperarContrasenaController::class, 'mostrarFormularioRestablecer'])->name('password.reset');
    Route::post('/restablecer-contrasena', [RecuperarContrasenaController::class, 'restablecerContrasena'])->name('password.update');
});

Route::post('/logout', [AutenticacionController::class, 'cerrarSesion'])->name('logout')->middleware('auth');

// HU-06: datos de contacto propios, disponibles para cualquier cuenta activa.
Route::middleware(['auth', 'rol'])->group(function () {
    Route::get('/perfil', [PerfilController::class, 'editar'])->name('perfil.editar');
    Route::patch('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::patch('/perfil/contrasena', [PerfilController::class, 'actualizarContrasena'])
        ->middleware(\Illuminate\Routing\Middleware\ThrottleRequests::class.':10,1')
        ->name('perfil.contrasena');
});

// Módulo Administrador (HU-02)
Route::middleware(['auth', 'rol:Administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'actualizarEstado'])->name('usuarios.estado');
});

// Módulo Conserje (HU-02)
Route::middleware(['auth', 'rol:Conserje'])->prefix('conserje')->name('conserje.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'conserje'])->name('dashboard');
});

// Módulo Residente (HU-02)
Route::middleware(['auth', 'rol:Residente'])->prefix('residente')->name('residente.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'residente'])->name('dashboard');
});
