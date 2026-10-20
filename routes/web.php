<?php

use App\Http\Controllers\Auth\AutenticacionController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\EspacioComunController;
use App\Http\Controllers\Admin\DepartamentoController;
use App\Http\Controllers\Conserje\EncomiendaController as ConserjeEncomiendaController;
use App\Http\Controllers\Residente\EncomiendaController as ResidenteEncomiendaController;
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
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'actualizarEstado'])->name('usuarios.estado');
    Route::get('/espacios-comunes', [EspacioComunController::class, 'index'])->name('espacios-comunes.index');
    Route::post('/espacios-comunes', [EspacioComunController::class, 'store'])->name('espacios-comunes.store');
    Route::patch('/espacios-comunes/{espacio}/estado', [EspacioComunController::class, 'actualizarEstado'])->name('espacios-comunes.estado');
    Route::get('/espacios-comunes/{espacio}/configuracion', [EspacioComunController::class, 'configurar'])->name('espacios-comunes.configurar');
    Route::patch('/espacios-comunes/{espacio}/configuracion', [EspacioComunController::class, 'guardarConfiguracion'])->name('espacios-comunes.configuracion');

    // Gestión de Departamentos (HU-07, HU-08, HU-09, HU-10)
    Route::get('/departamentos', [DepartamentoController::class, 'index'])->name('departamentos.index');
    Route::post('/departamentos', [DepartamentoController::class, 'store'])->name('departamentos.store');
    Route::get('/departamentos/{departamento}', [DepartamentoController::class, 'show'])->name('departamentos.show');
    Route::patch('/departamentos/{departamento}/estado', [DepartamentoController::class, 'actualizarEstado'])->name('departamentos.estado');
    Route::post('/departamentos/{departamento}/residentes', [DepartamentoController::class, 'asociarResidente'])->name('departamentos.residentes.asociar');
    Route::delete('/departamentos/{departamento}/residentes/{usuario}', [DepartamentoController::class, 'desasociarResidente'])->name('departamentos.residentes.desasociar');
    Route::post('/departamentos/{departamento}/contactos', [DepartamentoController::class, 'storeContacto'])->name('departamentos.contactos.store');
    Route::delete('/departamentos/{departamento}/contactos/{contacto}', [DepartamentoController::class, 'destroyContacto'])->name('departamentos.contactos.destroy');
});

// Módulo Conserje (HU-02, HU-25, HU-27)
Route::middleware(['auth', 'rol:Conserje'])->prefix('conserje')->name('conserje.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'conserje'])->name('dashboard');
    Route::get('/encomiendas', [ConserjeEncomiendaController::class, 'index'])->name('encomiendas.index');
    Route::post('/encomiendas', [ConserjeEncomiendaController::class, 'store'])->name('encomiendas.store');
    Route::patch('/encomiendas/{encomienda}/retiro', [ConserjeEncomiendaController::class, 'registrarRetiro'])->name('encomiendas.retiro');
});

// Módulo Residente (HU-02, HU-26)
Route::middleware(['auth', 'rol:Residente'])->prefix('residente')->name('residente.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'residente'])->name('dashboard');
    Route::get('/encomiendas', [ResidenteEncomiendaController::class, 'index'])->name('encomiendas.index');
});
