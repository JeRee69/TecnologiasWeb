@extends('layouts.app')
@section('title', 'Gestión de Incidencias')
@section('content')
<div class="mb-6">
      <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al panel
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-600 text-3xl">assignment_late</span>
            Atención de Incidencias y Reclamos
          </h1>
          <p class="text-sm text-gray-600 mt-1">Revisión, derivación y actualización de solicitudes reportadas por los residentes.</p>
        </div>
      </div>
    </div>

    {{-- Feedback de sesión si no carga SweetAlert2 --}}
    @if (session('estado') || session('exito'))
      <div data-swal-feedback role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
        <span>{{ session('estado') ?? session('exito') }}</span>
      </div>
    @endif

    {{-- Métricas Rápidas --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Registradas</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totales['total'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-amber-100 bg-amber-50/30 shadow-sm">
        <p class="text-xs font-medium text-amber-700 uppercase tracking-wider">Pendientes</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ $totales['pendientes'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-blue-100 bg-blue-50/30 shadow-sm">
        <p class="text-xs font-medium text-blue-700 uppercase tracking-wider">En Proceso</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">{{ $totales['en_proceso'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/30 shadow-sm">
        <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">Resueltas</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $totales['resueltas'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-rose-100 bg-rose-50/30 shadow-sm">
        <p class="text-xs font-medium text-rose-700 uppercase tracking-wider">Rechazadas</p>
        <p class="text-2xl font-bold text-rose-700 mt-1">{{ $totales['rechazadas'] }}</p>
      </div>
    </div>

    {{-- Barra de Búsqueda y Filtros --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
      <form method="GET" action="{{ route('admin.incidencias.index') }}" class="flex flex-wrap items-center gap-2 w-full">
        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por título, depto o residente..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-[#1e3a8a] outline-none"/>

        <select name="estado" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#1e3a8a] outline-none">
          <option value="">Todos los estados</option>
          <option value="Pendiente" {{ $filtroEstado === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
          <option value="En Proceso" {{ $filtroEstado === 'En Proceso' ? 'selected' : '' }}>En Proceso</option>
          <option value="Resuelta" {{ $filtroEstado === 'Resuelta' ? 'selected' : '' }}>Resuelta</option>
          <option value="Rechazada" {{ $filtroEstado === 'Rechazada' ? 'selected' : '' }}>Rechazada</option>
        </select>

        <select name="categoria" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#1e3a8a] outline-none">
          <option value="">Todas las categorías</option>
          @foreach (\App\Models\Incidencia::CATEGORIAS as $cat)
            <option value="{{ $cat }}" {{ $filtroCategoria === $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>

        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs px-3 py-1.5 rounded-lg font-medium transition-colors">
          Filtrar
        </button>
        @if (!empty($busqueda) || !empty($filtroEstado) || !empty($filtroCategoria))
          <a href="{{ route('admin.incidencias.index') }}" class="text-xs text-blue-700 hover:underline">Limpiar</a>
        @endif
      </form>
    </div>

    {{-- Tabla de Incidencias (HU-36) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      @if ($incidencias->isEmpty())
        <div class="p-12 text-center">
          <span class="material-symbols-outlined text-gray-300 text-5xl mb-3">inbox</span>
          <h3 class="text-base font-semibold text-gray-800">No hay incidencias registradas</h3>
          <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">No se encontraron tickets con los filtros indicados.</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="px-5 py-3">Folio</th>
                <th class="px-5 py-3">Residente / Depto</th>
                <th class="px-5 py-3">Categoría y Asunto</th>
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Estado</th>
                <th class="px-5 py-3 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
              @foreach ($incidencias as $incidencia)
                @php
                  $estadoClases = [
                      'Pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
                      'En Proceso' => 'bg-blue-100 text-blue-800 border-blue-200',
                      'Resuelta' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                      'Rechazada' => 'bg-rose-100 text-rose-800 border-rose-200',
                  ][$incidencia->estado] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <tr class="hover:bg-gray-50/60 transition-colors">
                  <td class="px-5 py-4 font-mono text-xs font-bold text-gray-800">
                    #{{ $incidencia->id }}
                  </td>
                  <td class="px-5 py-4">
                    <div class="font-medium text-gray-900">{{ $incidencia->residente?->nombre ?? 'Usuario eliminado' }}</div>
                    <div class="text-xs text-gray-500 flex items-center gap-1">
                      <span class="material-symbols-outlined text-[14px]">meeting_room</span>
                      {{ $incidencia->departamento ? 'Depto ' . $incidencia->departamento->numero : 'Sin departamento' }}
                    </div>
                  </td>
                  <td class="px-5 py-4 max-w-xs">
                    <span class="inline-block text-[11px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700 mb-1">
                      {{ $incidencia->categoria }}
                    </span>
                    <div class="font-semibold text-gray-900 truncate" title="{{ $incidencia->titulo }}">
                      {{ $incidencia->titulo }}
                    </div>
                    <div class="text-xs text-gray-500 line-clamp-1">
                      {{ $incidencia->descripcion }}
                    </div>
                  </td>
                  <td class="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                    {{ $incidencia->created_at->format('d/m/Y H:i') }}
                  </td>
                  <td class="px-5 py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $estadoClases }}">
                      {{ $incidencia->estado }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-right whitespace-nowrap">
                    <a href="{{ route('admin.incidencias.show', $incidencia) }}" class="inline-flex items-center gap-1 bg-[#1e3a8a] hover:bg-blue-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors shadow-sm">
                      <span class="material-symbols-outlined text-[15px]">manage_search</span>
                      <span>Gestionar</span>
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-gray-200">
          {{ $incidencias->links() }}
        </div>
      @endif
    </div>
@endsection
