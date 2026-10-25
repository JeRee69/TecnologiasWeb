@extends('layouts.app')
@section('title', 'Mural de Avisos')
@section('content')
<div class="legacy-page">
<div class="mb-6">
      <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al panel
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Mural Informativo y Comunicados</h1>
          <p class="text-sm text-gray-600 mt-1">Publicación, edición y control de vigencia de comunicados oficiales para la comunidad.</p>
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
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Avisos</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totales['total'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/30 shadow-sm">
        <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">Publicados (Vigentes)</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $totales['publicados'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-amber-100 bg-amber-50/30 shadow-sm">
        <p class="text-xs font-medium text-amber-700 uppercase tracking-wider">Avisos Urgentes</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ $totales['urgentes'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Avisos Retirados</p>
        <p class="text-2xl font-bold text-gray-600 mt-1">{{ $totales['retirados'] }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      {{-- Formulario de Publicación (HU-31) --}}
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sticky top-6">
          <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
            <span class="material-symbols-outlined text-indigo-700">campaign</span>
            <h2 class="text-lg font-semibold text-gray-900">Publicar Nuevo Aviso</h2>
          </div>

          <form action="{{ route('admin.avisos.store') }}" method="POST" class="space-y-4" data-swal-confirm="¿Confirmar publicación del aviso?" data-swal-text="El comunicado será visible de inmediato en el mural informativo de todos los residentes." data-swal-button="Publicar Aviso">
            @csrf

            <div>
              <label for="titulo" class="block text-xs font-semibold text-gray-700 mb-1">Título del Aviso <span class="text-red-500">*</span></label>
              <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" required maxlength="150" placeholder="Ej: Mantenimiento de Ascensores Torre A"
                     class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('titulo') border-red-500 @enderror"/>
              @error('titulo')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="prioridad" class="block text-xs font-semibold text-gray-700 mb-1">Prioridad <span class="text-red-500">*</span></label>
              <select id="prioridad" name="prioridad" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none">
                <option value="Baja" {{ old('prioridad') === 'Baja' ? 'selected' : '' }}>Baja (Informativo rutinario)</option>
                <option value="Media" {{ old('prioridad', 'Media') === 'Media' ? 'selected' : '' }}>Media (Aviso general)</option>
                <option value="Alta" {{ old('prioridad') === 'Alta' ? 'selected' : '' }}>Alta (Importante / Citaciones)</option>
                <option value="Urgente" {{ old('prioridad') === 'Urgente' ? 'selected' : '' }}>Urgente (Cortes de servicio / Emergencias)</option>
              </select>
              @error('prioridad')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="contenido-aviso" class="block text-xs font-semibold text-gray-700 mb-1">Contenido del Comunicado <span class="text-red-500">*</span></label>
              <textarea id="contenido-aviso" name="contenido" rows="5" required maxlength="5000" placeholder="Detalla las fechas, horarios, instrucciones y recomendaciones para los vecinos..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('contenido') border-red-500 @enderror">{{ old('contenido') }}</textarea>
              @error('contenido')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <button type="submit" class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-medium text-sm py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 shadow-sm transition-colors">
              <span class="material-symbols-outlined text-[18px]">send</span>
              <span>Publicar en el Mural</span>
            </button>
          </form>
        </div>
      </div>

      {{-- Listado y Gestión de Avisos (HU-31, HU-32) --}}
      <div class="lg:col-span-2 space-y-4">
        {{-- Filtros y Búsqueda --}}
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
          <form method="GET" action="{{ route('admin.avisos.index') }}" class="flex flex-wrap items-center gap-2 w-full">
            <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por título o texto..."
                   class="flex-1 min-w-[180px] rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-[#1e3a8a] outline-none"/>

            <select name="estado" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#1e3a8a] outline-none">
              <option value="">Todos los estados</option>
              <option value="Publicado" {{ $filtroEstado === 'Publicado' ? 'selected' : '' }}>Publicados</option>
              <option value="Retirado" {{ $filtroEstado === 'Retirado' ? 'selected' : '' }}>Retirados</option>
            </select>

            <select name="prioridad" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#1e3a8a] outline-none">
              <option value="">Todas las prioridades</option>
              <option value="Urgente" {{ $filtroPrioridad === 'Urgente' ? 'selected' : '' }}>Urgente</option>
              <option value="Alta" {{ $filtroPrioridad === 'Alta' ? 'selected' : '' }}>Alta</option>
              <option value="Media" {{ $filtroPrioridad === 'Media' ? 'selected' : '' }}>Media</option>
              <option value="Baja" {{ $filtroPrioridad === 'Baja' ? 'selected' : '' }}>Baja</option>
            </select>

            <button type="submit" class="ui-button">
              Filtrar
            </button>
            @if (!empty($busqueda) || !empty($filtroEstado) || !empty($filtroPrioridad))
              <a href="{{ route('admin.avisos.index') }}" class="text-xs text-blue-700 hover:underline">Limpiar</a>
            @endif
          </form>
        </div>

        {{-- Lista de Avisos --}}
        @if ($avisos->isEmpty())
          <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
            <span class="material-symbols-outlined text-gray-300 text-5xl mb-3">campaign</span>
            <h3 class="text-base font-semibold text-gray-800">No se encontraron avisos</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">No hay comunicados registrados con los criterios seleccionados.</p>
          </div>
        @else
          @foreach ($avisos as $aviso)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-gray-300 transition-colors {{ $aviso->estado === 'Retirado' ? 'opacity-70 bg-gray-50/50' : '' }}">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    @php
                      $prioridadClases = [
                          'Urgente' => 'bg-red-100 text-red-800 border-red-200',
                          'Alta' => 'bg-amber-100 text-amber-800 border-amber-200',
                          'Media' => 'bg-blue-100 text-blue-800 border-blue-200',
                          'Baja' => 'bg-gray-100 text-gray-700 border-gray-200',
                      ][$aviso->prioridad] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $prioridadClases }}">
                      {{ $aviso->prioridad }}
                    </span>

                    @if ($aviso->estado === 'Publicado')
                      <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Publicado
                      </span>
                    @else
                      <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Retirado
                      </span>
                    @endif

                    <span class="text-xs text-gray-400">#{{ $aviso->id }}</span>
                  </div>

                  <h3 class="text-lg font-bold text-gray-900">{{ $aviso->titulo }}</h3>
                  <div class="text-xs text-gray-500 flex items-center gap-2">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">person</span> {{ $aviso->autor?->nombre ?? 'Administración' }}</span>
                    <span>•</span>
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> {{ $aviso->fecha_publicacion->isoFormat('D MMM YYYY, HH:mm') ?? $aviso->created_at->format('d/m/Y H:i') }}</span>
                  </div>
                </div>

                {{-- Acciones (HU-32: Edición y Retiro) --}}
                <div class="flex items-center gap-2 shrink-0">
                  <a href="{{ route('admin.avisos.edit', $aviso) }}" class="inline-flex items-center gap-1 text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-[15px]">edit</span>
                    <span>Editar</span>
                  </a>

                  <form action="{{ route('admin.avisos.estado', $aviso) }}" method="POST"
                        data-swal-confirm="{{ $aviso->estado === 'Publicado' ? '¿Deseas retirar este aviso?' : '¿Deseas reactivar este aviso?' }}"
                        data-swal-text="{{ $aviso->estado === 'Publicado' ? 'El comunicado dejará de mostrarse inmediatamente en el mural para todos los residentes.' : 'El comunicado volverá a ser visible para todos los residentes en el mural.' }}"
                        data-swal-danger="{{ $aviso->estado === 'Publicado' ? 'true' : 'false' }}"
                        data-swal-button="{{ $aviso->estado === 'Publicado' ? 'Sí, retirar aviso' : 'Reactivar y publicar' }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors {{ $aviso->estado === 'Publicado' ? 'bg-red-50 hover:bg-red-100 text-red-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}">
                      <span class="material-symbols-outlined text-[15px]">{{ $aviso->estado === 'Publicado' ? 'visibility_off' : 'visibility' }}</span>
                      <span>{{ $aviso->estado === 'Publicado' ? 'Retirar' : 'Publicar' }}</span>
                    </button>
                  </form>
                </div>
              </div>

              <div class="text-sm text-gray-700 whitespace-pre-line bg-gray-50/70 p-3.5 rounded-lg border border-gray-100">
                {{ $aviso->contenido }}
              </div>
            </div>
          @endforeach

          <div class="mt-4">
            {{ $avisos->links() }}
          </div>
        @endif
      </div>
    </div>
</div>
@endsection
