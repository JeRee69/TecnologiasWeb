@extends('layouts.app')
@section('title', 'Dashboard Conserje')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Conserjería y accesos</div><h1>Bienvenido, {{ $usuario->nombre }}</h1><p>Gestiona la recepción de encomiendas y el control de accesos de la comunidad.</p></div><a href="{{ route('conserje.encomiendas.index') }}" class="ui-button"><span class="material-symbols-outlined" aria-hidden="true">add</span>Registrar encomienda</a></div>
<div class="stat-grid">
  @foreach ([['inventory_2','Encomiendas en custodia',$metricas['encomiendas']],['shield_person','Visitas en recinto',$metricas['visitas']],['directions_car','Vehículos autorizados',$metricas['vehiculos']],['apartment','Departamentos activos',$metricas['departamentos']]] as [$icono, $etiqueta, $valor])
    <section class="stat-card"><span class="material-symbols-outlined" aria-hidden="true">{{ $icono }}</span><div class="metric-label">{{ $etiqueta }}</div><div class="metric-value">{{ $valor }}</div></section>
  @endforeach
</div>
<div class="module-grid mb-6">
  @include('components.module-card', ['icono'=>'inventory_2','titulo'=>'Recepción de Encomiendas','descripcion'=>'Registra paquetes y correspondencia y confirma su retiro por el residente.','destino'=>'conserje.encomiendas.index','accion'=>'Gestionar encomiendas'])
  @include('components.module-card', ['icono'=>'shield_person','titulo'=>'Control de Visitas','descripcion'=>'Registra ingresos, consulta visitas en recinto y confirma sus salidas.','destino'=>'conserje.visitas.index','accion'=>'Gestionar visitas'])
  @include('components.module-card', ['icono'=>'directions_car','titulo'=>'Registro Vehicular','descripcion'=>'Consulta patentes y administra los vehículos autorizados de la comunidad.','destino'=>'conserje.vehiculos.index','accion'=>'Gestionar vehículos'])
</div>
<section class="ui-card"><div class="ui-card-title"><h2><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>Últimas encomiendas en custodia</h2><a href="{{ route('conserje.encomiendas.index') }}" class="ui-link">Ver todas →</a></div>
  <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @forelse ($encomiendasRecientes as $encomienda)
      <div class="soft-list-item"><strong>{{ $encomienda->empresa_delivery }}</strong><p>Depto. {{ $encomienda->departamento->numero }} · {{ $encomienda->estado }}</p><p>{{ $encomienda->fecha_ingreso->format('d/m/Y H:i') }}</p></div>
    @empty
      <p class="empty-state sm:col-span-2 xl:col-span-4">No hay encomiendas pendientes de retiro.</p>
    @endforelse
  </div>
</section>
@endsection
