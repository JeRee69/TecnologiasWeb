@extends('layouts.app')
@section('title', 'Dashboard Administrador')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Administración de la comunidad</div><h1>Bienvenido, {{ $usuario->nombre }}</h1><p>Una vista general para gestionar las unidades, los cobros y los espacios de tu comunidad.</p></div><a href="{{ route('admin.gastos-comunes.index') }}" class="ui-button"><span class="material-symbols-outlined" aria-hidden="true">add</span>Generar gastos comunes</a></div>
<div class="stat-grid">
  @foreach ([['apartment','Departamentos registrados',$metricas['departamentos']],['group','Usuarios activos',$metricas['usuarios']],['event_available','Solicitudes pendientes',$metricas['reservas']],['payments','Cobros por pagar',$metricas['cobros']]] as [$icono, $etiqueta, $valor])
    <section class="stat-card"><span class="material-symbols-outlined" aria-hidden="true">{{ $icono }}</span><div class="metric-label">{{ $etiqueta }}</div><div class="metric-value">{{ $valor }}</div></section>
  @endforeach
</div>
<div class="ui-card-title"><h2>Gestión de la comunidad</h2><span class="text-xs text-gray-500">Módulos del administrador</span></div>
<div class="module-grid">
  @include('components.module-card', ['icono'=>'payments','titulo'=>'Gastos Comunes','descripcion'=>'Genera cobros mensuales, registra su desglose y valida los pagos recibidos.','destino'=>'admin.gastos-comunes.index','accion'=>'Generar gastos comunes'])
  @include('components.module-card', ['icono'=>'apartment','titulo'=>'Departamentos','descripcion'=>'Mantén el padrón de propiedades, residentes y contactos de emergencia.','destino'=>'admin.departamentos.index','accion'=>'Administrar departamentos'])
  @include('components.module-card', ['icono'=>'group','titulo'=>'Usuarios y Roles','descripcion'=>'Registra cuentas y gestiona los accesos de cada integrante de la comunidad.','destino'=>'admin.usuarios.index','accion'=>'Administrar usuarios'])
  @include('components.module-card', ['icono'=>'meeting_room','titulo'=>'Espacios Comunes','descripcion'=>'Configura los espacios, sus horarios, aforo y reglas de uso.','destino'=>'admin.espacios-comunes.index','accion'=>'Administrar espacios'])
  @include('components.module-card', ['icono'=>'event_available','titulo'=>'Solicitudes y Reservas','descripcion'=>'Gestiona solicitudes y reservas y consulta las decisiones y cancelaciones.','destino'=>'admin.reservas.index','accion'=>'Ver reservas y cancelaciones'])
  @include('components.module-card', ['icono'=>'account_circle','titulo'=>'Mi perfil','descripcion'=>'Actualiza tus datos de contacto y tu contraseña personal.','destino'=>'perfil.editar','accion'=>'Editar mi perfil'])
</div>
<a href="{{ route('admin.reservas.index', ['estado' => 'pendiente']) }}" class="ui-link inline-block mt-6">Gestionar solicitudes y reservas →</a>
@endsection
