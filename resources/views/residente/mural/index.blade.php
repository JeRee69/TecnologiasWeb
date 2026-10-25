@extends('layouts.app')
@section('title', 'Mural Informativo')
@section('content')
<div class="mb-6">
      <a href="{{ route('residente.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al panel
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-700 text-3xl">newspaper</span>
            Mural Informativo y Comunicados
          </h1>
          <p class="text-sm text-gray-600 mt-1">Avisos oficiales, cortes programados y comunicados de la administración del edificio.</p>
        </div>
      </div>
    </div>

    {{-- Filtros Rápidos --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
      <form method="GET" action="{{ route('residente.mural.index') }}" class="flex flex-wrap items-center gap-2 w-full">
        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar en los comunicados..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-[#1e3a8a] outline-none"/>

        <select name="prioridad" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#1e3a8a] outline-none">
          <option value="">Todas las prioridades</option>
          <option value="Urgente" {{ $filtroPrioridad === 'Urgente' ? 'selected' : '' }}>Urgente</option>
          <option value="Alta" {{ $filtroPrioridad === 'Alta' ? 'selected' : '' }}>Alta</option>
          <option value="Media" {{ $filtroPrioridad === 'Media' ? 'selected' : '' }}>Media</option>
          <option value="Baja" {{ $filtroPrioridad === 'Baja' ? 'selected' : '' }}>Baja</option>
        </select>

        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs px-3 py-1.5 rounded-lg font-medium transition-colors">
          Filtrar
        </button>
        @if (!empty($busqueda) || !empty($filtroPrioridad))
          <a href="{{ route('residente.mural.index') }}" class="text-xs text-blue-700 hover:underline">Limpiar</a>
        @endif
      </form>
    </div>

    {{-- Contenedor de Avisos Vigentes (HU-33) --}}
    @if ($avisos->isEmpty())
      <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-3xl">mark_email_read</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No hay avisos vigentes en este momento</h3>
        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">La administración no ha emitido nuevos comunicados recientemente o no coinciden con tus filtros de búsqueda.</p>
      </div>
    @else
      <div class="space-y-5">
        @foreach ($avisos as $aviso)
          @php
            $configPrioridad = [
                'Urgente' => [
                    'badge' => 'bg-red-100 text-red-800 border-red-200',
                    'icon' => 'warning',
                    'cardBorder' => 'border-l-4 border-l-red-500',
                ],
                'Alta' => [
                    'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'icon' => 'notification_important',
                    'cardBorder' => 'border-l-4 border-l-amber-500',
                ],
                'Media' => [
                    'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
                    'icon' => 'info',
                    'cardBorder' => 'border-l-4 border-l-blue-600',
                ],
                'Baja' => [
                    'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                    'icon' => 'campaign',
                    'cardBorder' => 'border-l-4 border-l-gray-400',
                ],
            ][$aviso->prioridad] ?? [
                'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                'icon' => 'campaign',
                'cardBorder' => 'border-l-4 border-l-gray-400',
            ];
          @endphp

          <article class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow {{ $configPrioridad['cardBorder'] }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3 pb-3 border-b border-gray-100">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border {{ $configPrioridad['badge'] }}">
                  <span class="material-symbols-outlined text-[15px]">{{ $configPrioridad['icon'] }}</span>
                  <span>Prioridad: {{ $aviso->prioridad }}</span>
                </span>
                <span class="text-xs text-gray-400">#{{ $aviso->id }}</span>
              </div>

              <div class="text-xs text-gray-500 flex items-center gap-2">
                <span class="flex items-center gap-1 font-medium text-gray-700">
                  <span class="material-symbols-outlined text-[16px] text-gray-400">admin_panel_settings</span>
                  {{ $aviso->autor?->nombre ?? 'Administración EdiConnect' }}
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                  <span class="material-symbols-outlined text-[16px] text-gray-400">event</span>
                  {{ $aviso->fecha_publicacion->isoFormat('D [de] MMMM [de] YYYY, HH:mm') ?? $aviso->created_at->format('d/m/Y H:i') }}
                </span>
              </div>
            </div>

            <h2 class="text-xl font-bold text-gray-900 mb-3">{{ $aviso->titulo }}</h2>

            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-gray-50/50 p-4 rounded-lg border border-gray-100">
              {!! nl2br(e($aviso->contenido)) !!}
            </div>
          </article>
        @endforeach
      </div>

      <div class="mt-6">
        {{ $avisos->links() }}
      </div>
    @endif
@endsection
