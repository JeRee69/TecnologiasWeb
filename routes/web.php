<?php

use App\Http\Controllers\Auth\AutenticacionController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\EspacioComunController;
use App\Http\Controllers\Admin\DepartamentoController;
use App\Http\Controllers\Admin\AvisoController as AdminAvisoController;
use App\Http\Controllers\Admin\IncidenciaController as AdminIncidenciaController;
use App\Http\Controllers\Conserje\EncomiendaController as ConserjeEncomiendaController;
use App\Http\Controllers\Conserje\VisitaController as ConserjeVisitaController;
use App\Http\Controllers\Conserje\VehiculoAutorizadoController as ConserjeVehiculoController;
use App\Http\Controllers\Conserje\IncidenciaController as ConserjeIncidenciaController;
use App\Http\Controllers\Residente\EncomiendaController as ResidenteEncomiendaController;
use App\Http\Controllers\Residente\MuralController as ResidenteMuralController;
use App\Http\Controllers\Residente\IncidenciaController as ResidenteIncidenciaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\Residente\DisponibilidadController;
use App\Http\Controllers\Residente\ReservaController;
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

// Módulo Administrador (HU-02, HU-31, HU-32, HU-36, HU-37)
Route::middleware(['auth', 'rol:Administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('/reservas', [\App\Http\Controllers\Admin\ReservaController::class, 'index'])->name('reservas.index');
    Route::patch('/reservas/{reserva}/estado', [\App\Http\Controllers\Admin\ReservaController::class, 'actualizarEstado'])->whereNumber('reserva')->name('reservas.estado');
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

    // Mural Informativo: Avisos Oficiales (HU-31, HU-32)
    Route::get('/avisos', [AdminAvisoController::class, 'index'])->name('avisos.index');
    Route::post('/avisos', [AdminAvisoController::class, 'store'])->name('avisos.store');
    Route::get('/avisos/{aviso}/editar', [AdminAvisoController::class, 'edit'])->name('avisos.edit');
    Route::put('/avisos/{aviso}', [AdminAvisoController::class, 'update'])->name('avisos.update');
    Route::patch('/avisos/{aviso}/estado', [AdminAvisoController::class, 'cambiarEstado'])->name('avisos.estado');

    // Gestión de Incidencias y Reclamos (HU-36, HU-37)
    Route::get('/incidencias', [AdminIncidenciaController::class, 'index'])->name('incidencias.index');
    Route::get('/incidencias/{incidencia}', [AdminIncidenciaController::class, 'show'])->name('incidencias.show');
    Route::patch('/incidencias/{incidencia}/estado', [AdminIncidenciaController::class, 'actualizarEstado'])->name('incidencias.estado');
});

// Módulo Conserje (HU-02, HU-25, HU-27, HU-29, HU-30, HU-36, HU-37)
Route::middleware(['auth', 'rol:Conserje'])->prefix('conserje')->name('conserje.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'conserje'])->name('dashboard');
    
    // Encomiendas (HU-25, HU-27, HU-28)
    Route::get('/encomiendas', [ConserjeEncomiendaController::class, 'index'])->name('encomiendas.index');
    Route::post('/encomiendas', [ConserjeEncomiendaController::class, 'store'])->name('encomiendas.store');
    Route::patch('/encomiendas/{encomienda}/retiro', [ConserjeEncomiendaController::class, 'registrarRetiro'])->name('encomiendas.retiro');

    // Control de Visitas (HU-29)
    Route::get('/visitas', [ConserjeVisitaController::class, 'index'])->name('visitas.index');
    Route::post('/visitas', [ConserjeVisitaController::class, 'store'])->name('visitas.store');
    Route::patch('/visitas/{visita}/salida', [ConserjeVisitaController::class, 'registrarSalida'])->name('visitas.salida');

    // Registro y Verificación Vehicular (HU-30)
    Route::get('/vehiculos', [ConserjeVehiculoController::class, 'index'])->name('vehiculos.index');
    Route::post('/vehiculos', [ConserjeVehiculoController::class, 'store'])->name('vehiculos.store');
    Route::get('/vehiculos/verificar', [ConserjeVehiculoController::class, 'verificar'])->name('vehiculos.verificar');
    Route::patch('/vehiculos/{vehiculo}/estado', [ConserjeVehiculoController::class, 'cambiarEstado'])->name('vehiculos.estado');

    // Gestión de Incidencias en Conserjería (HU-36, HU-37)
    Route::get('/incidencias', [ConserjeIncidenciaController::class, 'index'])->name('incidencias.index');
    Route::get('/incidencias/{incidencia}', [ConserjeIncidenciaController::class, 'show'])->name('incidencias.show');
    Route::patch('/incidencias/{incidencia}/estado', [ConserjeIncidenciaController::class, 'actualizarEstado'])->name('incidencias.estado');
});

// Módulo Residente (HU-02, HU-26, HU-33, HU-34, HU-35)
Route::middleware(['auth', 'rol:Residente'])->prefix('residente')->name('residente.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'residente'])->name('dashboard');
    Route::get('/encomiendas', [ResidenteEncomiendaController::class, 'index'])->name('encomiendas.index');
    Route::get('/espacios-comunes/disponibilidad', [DisponibilidadController::class, 'index'])->name('espacios-comunes.disponibilidad');
    Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    Route::patch('/reservas/{reserva}/cancelacion', [ReservaController::class, 'cancelar'])->whereNumber('reserva')->name('reservas.cancelar');

    // Mural Informativo (HU-33)
    Route::get('/mural', [ResidenteMuralController::class, 'index'])->name('mural.index');

    // Reporte y Seguimiento de Incidencias (HU-34, HU-35)
    Route::get('/incidencias', [ResidenteIncidenciaController::class, 'index'])->name('incidencias.index');
    Route::get('/incidencias/crear', [ResidenteIncidenciaController::class, 'create'])->name('incidencias.create');
    Route::post('/incidencias', [ResidenteIncidenciaController::class, 'store'])->name('incidencias.store');
    Route::get('/incidencias/{incidencia}', [ResidenteIncidenciaController::class, 'show'])->name('incidencias.show');
});
