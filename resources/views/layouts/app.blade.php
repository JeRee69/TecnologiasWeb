<!DOCTYPE html>
<html lang="es">
<head>
  @include('layouts.assets')
  <title>@yield('title', 'Mi comunidad') - EdiConnect</title>
  @stack('styles')
</head>
<body class="app-body">
@php
  $cuenta = auth()->user();
  $rol = $cuenta->rol?->nombre;
  $zona = match ($rol) { 'Administrador' => 'admin', 'Conserje' => 'conserje', default => 'residente' };
  $portal = match ($zona) { 'admin' => 'Administración', 'conserje' => 'Conserjería', default => 'Portal Residente' };
  $iniciales = collect(explode(' ', $cuenta->nombre))->filter()->take(2)->map(fn ($parte) => mb_substr($parte, 0, 1))->implode('');
  $menu = match ($zona) {
    'admin' => [
      ['admin.dashboard','space_dashboard','Dashboard Administrador','admin.dashboard'],
      ['admin.gastos-comunes.index','payments','Gastos Comunes','admin.gastos-comunes.*'],
      ['admin.departamentos.index','apartment','Padrón de Propiedades','admin.departamentos.*'],
      ['admin.usuarios.index','group','Usuarios y Roles','admin.usuarios.*'],
      ['admin.espacios-comunes.index','meeting_room','Espacios Comunes','admin.espacios-comunes.*'],
      ['admin.reservas.index','event_available','Solicitudes y Reservas','admin.reservas.*'],
    ],
    'conserje' => [
      ['conserje.dashboard','space_dashboard','Dashboard Conserje','conserje.dashboard'],
      ['conserje.encomiendas.index','inventory_2','Recepción de Encomiendas','conserje.encomiendas.*'],
      ['conserje.visitas.index','shield_person','Control de Visitas','conserje.visitas.*'],
      ['conserje.vehiculos.index','directions_car','Registro Vehicular','conserje.vehiculos.*'],
    ],
    default => [
      ['residente.dashboard','home','Dashboard Residente','residente.dashboard'],
      ['residente.gastos-comunes.index','payments','Gastos Comunes','residente.gastos-comunes.*'],
      ['residente.encomiendas.index','inventory_2','Mis Encomiendas','residente.encomiendas.*'],
      ['residente.espacios-comunes.disponibilidad','calendar_month','Reserva de Espacios','residente.espacios-comunes.*'],
      ['residente.reservas.index','event_available','Mis Reservas','residente.reservas.*'],
    ],
  };
@endphp
  <a href="#contenido" class="skip-link">Saltar al contenido</a>
  <div id="sidebar-backdrop" class="sidebar-backdrop" hidden></div>
  <aside id="app-sidebar" class="app-sidebar" aria-label="Navegación principal">
    <a class="app-brand" href="{{ route($zona.'.dashboard') }}">
      <span class="brand-icon material-symbols-outlined" aria-hidden="true">apartment</span>
      <span><strong>EdiConnect</strong><small>Gestión de tu comunidad</small></span>
    </a>
    <nav class="sidebar-nav">
      <p class="nav-caption">{{ $portal }}</p>
      @foreach ($menu as [$destino, $icono, $etiqueta, $patron])
        <a href="{{ route($destino) }}" @class(['sidebar-link', 'is-active' => request()->routeIs($patron)]) @if(request()->routeIs($patron)) aria-current="page" @endif>
          <span class="material-symbols-outlined" aria-hidden="true">{{ $icono }}</span><span>{{ $etiqueta }}</span>
        </a>
      @endforeach
      <p class="nav-caption mt-6">Mi cuenta</p>
      <a href="{{ route('perfil.editar') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('perfil.*')]) @if(request()->routeIs('perfil.*')) aria-current="page" @endif><span class="material-symbols-outlined" aria-hidden="true">account_circle</span><span>Mi Perfil</span></a>
    </nav>
    <div class="sidebar-account">
      <a href="{{ route('perfil.editar') }}" class="account-link"><span class="avatar">{{ mb_strtoupper($iniciales) }}</span><span class="account-name"><strong>{{ $cuenta->nombre }}</strong><small>{{ $rol }}</small></span><span class="material-symbols-outlined" aria-hidden="true">settings</span></a>
      <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="logout-button"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Cerrar Sesión</button></form>
    </div>
  </aside>
  <div class="app-workspace">
    <header class="app-topbar">
      <div class="topbar-title"><button id="sidebar-toggle" type="button" aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menú de navegación"><span class="material-symbols-outlined" aria-hidden="true">menu</span></button><span class="portal-label">{{ $portal }}</span><span class="page-label">@yield('title', 'Mi comunidad')</span></div>
      <a href="{{ route('perfil.editar') }}" class="topbar-account"><span class="avatar avatar-dark">{{ mb_strtoupper($iniciales) }}</span><span>{{ $cuenta->nombre }}</span></a>
    </header>
    <main id="contenido" class="app-content" tabindex="-1">@yield('content')</main>
    <footer class="app-footer">EdiConnect <span>Gestión de comunidades residenciales</span></footer>
  </div>
  @include('components.alertas')
  <script src="{{ asset('js/ediconnect.js') }}" defer></script>
  @stack('scripts')
</body>
</html>
