@extends('layouts.app')
@section('title', 'Dashboard Residente')
@section('content')
<div class="page-heading">
  <div>
    <div class="eyebrow">Portal Residente @if ($departamentos->isNotEmpty()) · {{ $departamentos->count() }} unidad(es) asociada(s) @endif</div>
    <h1>Hola, {{ $usuario->nombre }}</h1>
    <p>Bienvenido al panel de tu comunidad. Revisa tus gastos, encomiendas y próximas reservas.</p>
  </div>
  <a href="{{ route('residente.espacios-comunes.disponibilidad') }}" class="ui-button"><span class="material-symbols-outlined" aria-hidden="true">add</span>Reservar espacio</a>
</div>
<div class="dashboard-grid">
  <div class="dashboard-stack">
    <section class="ui-card">
      <div class="ui-card-title">
        <h2><span class="material-symbols-outlined" aria-hidden="true">payments</span>Mis Gastos Comunes</h2>
        @if ($cobroDestacado)
          @include('components.estado-gasto', ['estadoGasto' => $cobroDestacado->estadoVigente()])
        @endif
      </div>
      @if ($cobroDestacado)
        <div class="flex flex-wrap justify-between gap-5 pb-5 border-b border-gray-100">
          <div><div class="metric-label">Monto del cobro · {{ $cobroDestacado->periodo->format('m/Y') }}</div><div class="metric-value text-4xl">${{ number_format($cobroDestacado->monto, 0, ',', '.') }} <span class="text-sm font-normal text-gray-500">CLP</span></div><p class="text-xs text-gray-500 mt-2">Depto. {{ $cobroDestacado->departamento->numero }}</p></div>
          <div class="text-sm"><div class="metric-label">Vencimiento</div><strong>{{ $cobroDestacado->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha definida' }}</strong>
          @if ($ultimoPago)
            <p class="text-xs text-gray-500 mt-3">Último período pagado: {{ $ultimoPago->periodo->format('m/Y') }}</p>
          @endif
          </div>
        </div>
        <div class="soft-panel mt-5"><p class="font-semibold text-xs mb-2">Información de tu cobro</p><p class="text-xs text-gray-500 leading-relaxed">Consulta el detalle de los conceptos y descarga tu respaldo en PDF. El estado del pago se actualiza cuando la administración lo valida.</p></div>
      @else
        <div class="empty-state"><span class="material-symbols-outlined block mb-3">receipt_long</span>{{ $departamentos->isEmpty() ? 'Aún no tienes un departamento asociado. Contacta a la administración.' : 'Aún no se han generado gastos comunes para tus departamentos.' }}</div>
      @endif
      <div class="flex flex-wrap gap-3 mt-5"><a href="{{ route('residente.gastos-comunes.index') }}" class="ui-button">Consultar mis gastos comunes</a>
      @if ($cobroDestacado)
        <a href="{{ route('residente.gastos-comunes.pdf', $cobroDestacado) }}" class="ui-button secondary"><span class="material-symbols-outlined" aria-hidden="true">download</span>Descargar PDF</a>
      @endif
      </div>
    </section>
    <section class="ui-card">
      <div class="ui-card-title"><h2><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>Encomiendas y correspondencia</h2><span class="text-xs bg-blue-50 text-blue-900 px-2 py-1 rounded-full">{{ $totalEncomiendas }} en custodia</span></div>
      <div class="grid sm:grid-cols-2 gap-3">
        @forelse ($encomiendas as $encomienda)
          <div class="soft-list-item bg-blue-50/30"><strong>{{ $encomienda->empresa_delivery }}</strong><p>Depto. {{ $encomienda->departamento->numero }} · {{ $encomienda->estado }}</p><p>{{ $encomienda->fecha_ingreso->format('d/m/Y H:i') }}</p><p class="font-mono">{{ $encomienda->codigo_seguimiento ?? 'Sin código de seguimiento' }}</p></div>
        @empty
          <p class="empty-state sm:col-span-2">No tienes encomiendas pendientes de retiro.</p>
        @endforelse
      </div>
      <a href="{{ route('residente.encomiendas.index') }}" class="ui-link inline-block mt-4">Mis Encomiendas →</a>
    </section>
  </div>
  <div class="dashboard-stack">
    @include('components.resumen-gastos')
    <section class="ui-card">
      <div class="ui-card-title"><h2><span class="material-symbols-outlined" aria-hidden="true">event_available</span>Próxima reserva</h2><a class="ui-link" href="{{ route('residente.reservas.index') }}">Mis reservas</a></div>
      @if ($proximaReserva)
        <div class="soft-list-item bg-blue-50/30"><strong>{{ $proximaReserva->espacioComun->nombre }}</strong><p>{{ \App\Models\Reserva::ESTADOS[$proximaReserva->estado] }}</p><p>{{ $proximaReserva->fecha->format('d/m/Y') }} · {{ substr($proximaReserva->hora_inicio, 0, 5) }} a {{ substr($proximaReserva->hora_fin, 0, 5) }}</p></div>
      @else
        <p class="empty-state">No tienes reservas próximas. Encuentra un espacio para tu siguiente encuentro.</p>
      @endif
      <a href="{{ route('residente.espacios-comunes.disponibilidad') }}" class="ui-button secondary w-full mt-4">Consultar disponibilidad →</a>
    </section>
    <section class="ui-card"><div class="ui-card-title"><h2><span class="material-symbols-outlined" aria-hidden="true">apartment</span>Tus departamentos</h2></div>
      @forelse ($departamentos as $departamento)
        <p class="text-xs py-2 border-b border-gray-100">Depto. <strong>{{ $departamento->numero }}</strong> · Piso {{ $departamento->piso }}</p>
      @empty
        <p class="text-xs text-gray-500">Sin unidades asociadas.</p>
      @endforelse
    </section>
  </div>
</div>
@endsection
