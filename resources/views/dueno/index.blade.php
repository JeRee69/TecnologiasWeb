@extends('layouts.app')
@section('title', 'Mis Propiedades')
@section('content')
  <div class="page-heading">
    <div>
      <div class="eyebrow">Portal del dueño</div>
      <h1>Bienvenido, {{ $usuario->nombre }}</h1>
      <p>Consulta el estado de los departamentos de tu propiedad. Esta vista es de solo lectura.</p>
    </div>
  </div>

  <div class="stat-grid">
    @foreach ([['real_estate_agent', 'Departamentos en propiedad', $metricas['propiedades']], ['group', 'Residentes en tus unidades', $metricas['residentes']], ['payments', 'Cobros por pagar', $metricas['por_pagar']], ['report_problem', 'Incidencias abiertas', $metricas['incidencias']]] as [$icono, $etiqueta, $valor])
      <section class="stat-card"><span class="material-symbols-outlined" aria-hidden="true">{{ $icono }}</span>
        <div class="metric-label">{{ $etiqueta }}</div>
        <div class="metric-value">{{ $valor }}</div>
      </section>
    @endforeach
  </div>

  <div class="ui-card-title">
    <h2>Mis departamentos</h2><span class="text-xs text-gray-500">Selecciona una unidad para ver su detalle</span>
  </div>

  @if ($propiedades->isEmpty())
    <div class="ui-card text-center text-sm text-gray-500 py-10">
      <span class="material-symbols-outlined text-4xl text-gray-300 block mb-2">home_work</span>
      Aún no tienes departamentos asignados. Contacta a la administración para vincular tus propiedades.
    </div>
  @else
    <div class="module-grid">
      @foreach ($propiedades as $propiedad)
        <a href="{{ route('dueno.departamentos.show', $propiedad) }}" class="ui-card block hover:border-blue-300 transition-colors" data-propiedad="{{ $propiedad->numero }}">
          <div class="flex items-center justify-between gap-3 mb-3">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl bg-blue-100 text-[#1e3a8a] flex items-center justify-center font-bold">{{ $propiedad->numero }}</div>
              <div>
                <div class="font-bold text-gray-900">Departamento {{ $propiedad->numero }}</div>
                <div class="text-xs text-gray-500">Piso {{ $propiedad->piso }}</div>
              </div>
            </div>
            @if ($propiedad->estaActivo())
              <span class="text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full px-2 py-0.5">Activo</span>
            @else
              <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200 rounded-full px-2 py-0.5">Inactivo</span>
            @endif
          </div>
          <dl class="grid grid-cols-2 gap-2 text-xs">
            <div><dt class="text-gray-500">Residentes</dt><dd class="font-semibold text-gray-900">{{ $propiedad->residentes_count }}</dd></div>
            <div><dt class="text-gray-500">Cobros por pagar</dt><dd class="font-semibold {{ $propiedad->gastos_por_pagar_count ? 'text-amber-700' : 'text-gray-900' }}">{{ $propiedad->gastos_por_pagar_count }}</dd></div>
            <div><dt class="text-gray-500">Deuda pendiente</dt><dd class="font-semibold text-gray-900">${{ number_format((int) $propiedad->deuda_total, 0, ',', '.') }}</dd></div>
            <div><dt class="text-gray-500">Incidencias abiertas</dt><dd class="font-semibold text-gray-900">{{ $propiedad->incidencias_abiertas_count }}</dd></div>
          </dl>
          @if ($propiedad->comprobantes_en_revision_count)
            <p class="mt-3 text-[11px] text-blue-800 bg-blue-50 border border-blue-100 rounded px-2 py-1">{{ $propiedad->comprobantes_en_revision_count }} comprobante(s) en revisión</p>
          @endif
          <span class="ui-link inline-block mt-3 text-xs">Ver detalle →</span>
        </a>
      @endforeach
    </div>
  @endif
@endsection
