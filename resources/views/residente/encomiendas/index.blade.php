@extends('layouts.app')
@section('title', 'Mis Encomiendas Pendientes')
@section('content')
<div class="legacy-page">
<div class="mb-6">
      <a href="{{ route('residente.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al portal
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Mis Encomiendas Pendientes</h1>
          <p class="text-sm text-gray-600 mt-1">Paquetes recibidos en conserjería disponibles para retiro asociados a tu departamento.</p>
        </div>
        @if ($departamentos->isNotEmpty())
          <div class="flex flex-wrap gap-2 items-center">
            <span class="text-xs text-gray-500 font-medium">Tus unidades:</span>
            @foreach ($departamentos as $depto)
              <span class="inline-flex items-center gap-1 bg-blue-50 text-[#1e3a8a] border border-blue-200 px-2.5 py-1 rounded-md text-xs font-semibold">
                <span class="material-symbols-outlined text-[14px]">meeting_room</span>
                Depto. {{ $depto->numero }}
              </span>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    @if ($departamentos->isEmpty())
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-900 flex items-start gap-4">
        <span class="material-symbols-outlined text-amber-600 text-3xl shrink-0">info</span>
        <div>
          <h2 class="text-base font-bold mb-1">Sin departamento asociado</h2>
          <p class="text-sm text-amber-800">
            Aún no tienes una unidad habitacional activa asignada a tu cuenta de residente. Contacta a la administración para vincular tu departamento y poder visualizar tus encomiendas.
          </p>
        </div>
      </div>
    @elseif ($encomiendas->isEmpty())
      <div class="bg-white border border-gray-200 rounded-xl p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-3xl">mark_email_read</span>
        </div>
        <h2 class="text-lg font-bold text-gray-900 mb-1">No tienes encomiendas pendientes</h2>
        <p class="text-sm text-gray-500 max-w-md mx-auto">
          En este momento no hay paquetes pendientes de retiro para tu departamento en conserjería. Cuando un repartidor entregue un paquete a tu nombre, aparecerá en esta sección.
        </p>
      </div>
    @else
      <div class="mb-5 bg-blue-50 border border-blue-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm text-blue-950">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[#1e3a8a] text-2xl">package_2</span>
          <span>Tienes <strong>{{ $encomiendas->count() }}</strong> {{ $encomiendas->count() === 1 ? 'encomienda pendiente' : 'encomiendas pendientes' }} de retiro en conserjería.</span>
        </div>
        <span class="text-xs text-blue-800 bg-white px-3 py-1 rounded-full border border-blue-200 font-semibold w-max">
          Retiro con RUT o número de unidad
        </span>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <caption class="sr-only">Listado de encomiendas pendientes asociadas al residente</caption>
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
              <tr>
                <th scope="col" class="p-4 font-semibold">Departamento</th>
                <th scope="col" class="p-4 font-semibold">Empresa de Delivery</th>
                <th scope="col" class="p-4 font-semibold">Fecha de Recepción</th>
                <th scope="col" class="p-4 font-semibold">Estado</th>
                <th scope="col" class="hidden sm:table-cell p-4 font-semibold">Receptor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach ($encomiendas as $encomienda)
                <tr class="hover:bg-gray-50/60 transition-colors">
                  <th scope="row" class="p-4 font-normal whitespace-nowrap">
                    <span class="font-bold text-gray-900 text-base">Depto. {{ $encomienda->departamento?->numero ?? 'N/A' }}</span>
                    @if ($encomienda->codigo_seguimiento)
                      <div class="text-xs text-gray-500 font-mono mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">qr_code_2</span>
                        {{ $encomienda->codigo_seguimiento }}
                      </div>
                    @endif
                  </th>
                  <td class="p-4">
                    <div class="font-semibold text-gray-900 flex items-center gap-1.5">
                      <span class="material-symbols-outlined text-[18px] text-[#1e3a8a]">local_shipping</span>
                      {{ $encomienda->empresa_delivery }}
                    </div>
                    @if ($encomienda->descripcion)
                      <p class="text-xs text-gray-500 mt-1 max-w-sm">{{ $encomienda->descripcion }}</p>
                    @endif
                  </td>
                  <td class="p-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">{{ $encomienda->fecha_ingreso?->format('d/m/Y') }}</div>
                    <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                      <span class="material-symbols-outlined text-[13px]">schedule</span>
                      {{ $encomienda->fecha_ingreso?->format('H:i') }} hrs
                    </div>
                  </td>
                  <td class="p-4 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                      {{ $encomienda->estado }}
                    </span>
                  </td>
                  <td class="hidden sm:table-cell p-4 whitespace-nowrap text-xs text-gray-500">
                    {{ $encomienda->conserje?->nombre ?? 'Conserjería de turno' }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
</div>
@endsection
