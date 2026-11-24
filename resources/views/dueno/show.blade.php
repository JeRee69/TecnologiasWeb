@extends('layouts.app')
@section('title', 'Departamento '.e($departamento->numero))
@section('content')
<div class="legacy-page">
  <div class="mb-6">
    <a href="{{ route('dueno.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Volver a mis propiedades
    </a>
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 rounded-xl bg-blue-100 text-[#1e3a8a] flex items-center justify-center font-bold text-xl shadow-sm">{{ $departamento->numero }}</div>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Departamento {{ $departamento->numero }}</h1>
        <p class="text-xs text-gray-500">Piso {{ $departamento->piso }} &bull; {{ $departamento->estaActivo() ? 'Activo' : 'Inactivo' }} &bull; Vista de solo lectura</p>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <section aria-labelledby="residentes-heading" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
        <h2 id="residentes-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-700">group</span>Residentes
        </h2>
        <span class="text-xs bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-full">{{ $departamento->residentes->count() }}</span>
      </div>
      <div class="space-y-3">
        @forelse ($departamento->residentes as $residente)
          <div class="p-3 rounded-lg border border-gray-200">
            <div class="flex items-center gap-2">
              <span class="font-semibold text-sm text-gray-900">{{ $residente->nombre }}</span>
              <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $residente->pivot->es_titular ? 'bg-amber-100 text-amber-900' : 'bg-gray-100 text-gray-600' }}">
                {{ $residente->pivot->es_titular ? 'Titular' : 'Habitante' }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              {{ $residente->email }}@if ($residente->telefono) &bull; {{ $residente->telefono }}@endif
            </div>
          </div>
        @empty
          <p class="text-xs text-gray-500 text-center py-6">No hay residentes asociados a esta unidad.</p>
        @endforelse
      </div>
    </section>

    <section aria-labelledby="incidencias-heading" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
        <h2 id="incidencias-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-rose-700">report_problem</span>Incidencias reportadas
        </h2>
        <span class="text-xs bg-rose-50 text-rose-700 font-semibold px-2.5 py-1 rounded-full">{{ $incidencias->count() }}</span>
      </div>
      <div class="space-y-3">
        @forelse ($incidencias as $incidencia)
          @php
            $colorIncidencia = match ($incidencia->estado) {
              'Resuelta' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
              'En Proceso' => 'bg-blue-50 text-blue-800 border-blue-200',
              'Rechazada' => 'bg-gray-100 text-gray-700 border-gray-200',
              default => 'bg-amber-50 text-amber-800 border-amber-200',
            };
          @endphp
          <div class="p-3 rounded-lg border border-gray-200">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="font-semibold text-sm text-gray-900">{{ $incidencia->titulo }}</div>
                <div class="text-[11px] text-gray-500 mt-0.5">{{ $incidencia->categoria }} &bull; {{ $incidencia->created_at?->format('d/m/Y') }} &bull; {{ $incidencia->residente?->nombre }}</div>
              </div>
              <span class="shrink-0 text-[11px] font-semibold border rounded-full px-2 py-0.5 {{ $colorIncidencia }}">{{ $incidencia->estado }}</span>
            </div>
            @if ($incidencia->respuesta_administracion)
              <p class="text-xs text-gray-600 mt-2 bg-gray-50 rounded p-2"><strong>Respuesta:</strong> {{ $incidencia->respuesta_administracion }}</p>
            @endif
          </div>
        @empty
          <p class="text-xs text-gray-500 text-center py-6">No hay incidencias reportadas desde esta unidad.</p>
        @endforelse
      </div>
    </section>
  </div>

  <section aria-labelledby="gastos-heading" class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100">
      <h2 id="gastos-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-700">payments</span>Gastos comunes
      </h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 border-b text-gray-600">
          <tr>
            <th scope="col" class="px-5 py-3">Período</th>
            <th scope="col" class="px-5 py-3">Vencimiento</th>
            <th scope="col" class="px-5 py-3 text-right">Monto</th>
            <th scope="col" class="px-5 py-3">Estado</th>
            <th scope="col" class="px-5 py-3">Último comprobante</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse ($gastos as $gasto)
            <tr data-gasto="{{ $gasto->id }}">
              <td class="px-5 py-3 font-semibold">{{ $gasto->periodo?->format('m/Y') }}</td>
              <td class="px-5 py-3 text-gray-600">{{ $gasto->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
              <td class="px-5 py-3 text-right whitespace-nowrap">{{ $gasto->montoFormateado() }}</td>
              <td class="px-5 py-3">@include('components.estado-gasto', ['estadoGasto' => $gasto->estadoVigente()])</td>
              <td class="px-5 py-3 text-xs text-gray-600">{{ $gasto->ultimoComprobante?->estadoLegible() ?? 'Sin comprobante' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-8 text-center text-gray-500 text-xs">No hay gastos comunes registrados para esta unidad.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section aria-labelledby="comprobantes-heading" class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100">
      <h2 id="comprobantes-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-700">fact_check</span>Comprobantes de pago enviados
      </h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 border-b text-gray-600">
          <tr>
            <th scope="col" class="px-5 py-3">Enviado</th>
            <th scope="col" class="px-5 py-3">Gasto</th>
            <th scope="col" class="px-5 py-3">Residente</th>
            <th scope="col" class="px-5 py-3 text-right">Monto</th>
            <th scope="col" class="px-5 py-3">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse ($comprobantes as $comprobante)
            @php
              $colorComprobante = match ($comprobante->estado) {
                'aprobado' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'rechazado' => 'bg-red-50 text-red-800 border-red-200',
                default => 'bg-blue-50 text-blue-800 border-blue-200',
              };
            @endphp
            <tr>
              <td class="px-5 py-3 text-gray-600">{{ $comprobante->created_at?->format('d/m/Y H:i') }}</td>
              <td class="px-5 py-3">{{ $comprobante->gastoComun?->periodo?->format('m/Y') ?? '—' }}</td>
              <td class="px-5 py-3">{{ $comprobante->residente?->nombre ?? '—' }}</td>
              <td class="px-5 py-3 text-right whitespace-nowrap">{{ $comprobante->montoFormateado() }}</td>
              <td class="px-5 py-3">
                <span class="text-xs font-semibold border rounded-full px-2.5 py-0.5 {{ $colorComprobante }}">{{ $comprobante->estadoLegible() }}</span>
                @if ($comprobante->estado === 'rechazado' && $comprobante->motivo_rechazo)
                  <div class="text-[11px] text-red-700 mt-1">{{ $comprobante->motivo_rechazo }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-8 text-center text-gray-500 text-xs">No se han enviado comprobantes de pago para esta unidad.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
