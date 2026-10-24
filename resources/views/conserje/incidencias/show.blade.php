<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Atención Incidencia #{{ $incidencia->id }} - EdiConnect</title>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 min-h-screen flex flex-col">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-3xl">apartment</span>
        <a href="{{ route('conserje.dashboard') }}" class="font-bold text-xl tracking-tight">EdiConnect</a>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Panel de Conserjería</span>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
          <div class="font-semibold text-sm">{{ auth()->user()->nombre }}</div>
          <div class="text-xs text-blue-200">{{ auth()->user()->rol?->nombre }}</div>
        </div>
        <a href="{{ route('perfil.editar') }}" class="text-xs font-semibold hover:underline whitespace-nowrap">Mi perfil</a>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white text-xs px-3 py-2 rounded-lg flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-[16px]">logout</span>
            <span>Salir</span>
          </button>
        </form>
      </div>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6">
      <a href="{{ route('conserje.incidencias.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a la bitácora de incidencias
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <span>Atención Incidencia #{{ $incidencia->id }}</span>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded bg-slate-100 text-slate-700">{{ $incidencia->categoria }}</span>
          </h1>
          <p class="text-sm text-gray-500 mt-1">Reportada el {{ $incidencia->created_at->format('d/m/Y H:i') }} ({{ $incidencia->created_at->diffForHumans() }})</p>
        </div>
        @php
          $estadoClases = [
              'Pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
              'En Proceso' => 'bg-blue-100 text-blue-800 border-blue-200',
              'Resuelta' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
              'Rechazada' => 'bg-rose-100 text-rose-800 border-rose-200',
          ][$incidencia->estado] ?? 'bg-gray-100 text-gray-700 border-gray-200';
        @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold border {{ $estadoClases }}">
          <span class="w-2 h-2 rounded-full {{ $incidencia->estado === 'Resuelta' ? 'bg-emerald-600' : ($incidencia->estado === 'Pendiente' ? 'bg-amber-600' : ($incidencia->estado === 'En Proceso' ? 'bg-blue-600' : 'bg-rose-600')) }}"></span>
          <span>Estado: {{ $incidencia->estado }}</span>
        </span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      {{-- Columna Izquierda: Información de la Incidencia y Solicitante --}}
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-3">{{ $incidencia->titulo }}</h2>
          <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-gray-50/70 p-4 rounded-lg border border-gray-100">
            {!! nl2br(e($incidencia->descripcion)) !!}
          </div>
        </div>

        {{-- Respuesta actual y trazabilidad --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#1e3a8a] text-[20px]">history_edu</span>
            <span>Historial de Atención / Respuesta Registrada</span>
          </h3>

          @if (!empty($incidencia->respuesta_administracion))
            <div class="bg-blue-50/70 border border-blue-100 rounded-lg p-4 text-sm text-blue-950 space-y-2">
              <p class="leading-relaxed whitespace-pre-line">{{ $incidencia->respuesta_administracion }}</p>
              <div class="pt-2 border-t border-blue-200/60 text-xs text-blue-800 flex items-center justify-between">
                <span>Atendido por: {{ $incidencia->personalResolucion?->nombre ?? 'Personal Autorizado' }}</span>
                @if ($incidencia->fecha_resolucion)
                  <span>Fecha: {{ $incidencia->fecha_resolucion->format('d/m/Y H:i') }}</span>
                @endif
              </div>
            </div>
          @else
            <p class="text-xs text-gray-500 italic">No se ha registrado una respuesta aún para esta incidencia.</p>
          @endif
        </div>
      </div>

      {{-- Columna Derecha: Panel de Actualización de Estado (HU-37) y Solicitante --}}
      <div class="lg:col-span-1 space-y-6">
        {{-- Datos del Residente --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Datos del Residente</h3>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-xs text-gray-500 block">Nombre:</span>
              <span class="font-medium text-gray-900">{{ $incidencia->residente?->nombre ?? 'N/D' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Correo Electrónico:</span>
              <span class="text-xs font-medium text-gray-900">{{ $incidencia->residente?->email ?? 'N/D' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Teléfono:</span>
              <span class="text-xs font-medium text-gray-900">{{ $incidencia->residente?->telefono ?? 'Sin registrar' }}</span>
            </div>
            <div class="pt-2 border-t border-gray-100">
              <span class="text-xs text-gray-500 block">Unidad Habitacional:</span>
              <span class="font-semibold text-[#1e3a8a]">
                {{ $incidencia->departamento ? 'Departamento ' . $incidencia->departamento->numero . ' (Piso ' . $incidencia->departamento->piso . ')' : 'Sin departamento asociado' }}
              </span>
            </div>
          </div>
        </div>

        {{-- Formulario de Transición de Estado (HU-37) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px] text-gray-700">edit_note</span>
            <span>Actualizar Estado (HU-37)</span>
          </h3>

          <form action="{{ route('conserje.incidencias.estado', $incidencia) }}" method="POST" class="space-y-4"
                data-swal-confirm="¿Actualizar estado de la incidencia?"
                data-swal-text="El nuevo estado y las observaciones registradas serán visibles inmediatamente para el residente solicitante."
                data-swal-button="Actualizar Estado">
            @csrf
            @method('PATCH')

            <div>
              <label for="estado" class="block text-xs font-semibold text-gray-700 mb-1">Nuevo Estado <span class="text-red-500">*</span></label>
              <select id="estado" name="estado" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] outline-none">
                <option value="Pendiente" {{ old('estado', $incidencia->estado) === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="En Proceso" {{ old('estado', $incidencia->estado) === 'En Proceso' ? 'selected' : '' }}>En Proceso (En Revisión/Trabajo)</option>
                <option value="Resuelta" {{ old('estado', $incidencia->estado) === 'Resuelta' ? 'selected' : '' }}>Resuelta (Solución Aplicada)</option>
                <option value="Rechazada" {{ old('estado', $incidencia->estado) === 'Rechazada' ? 'selected' : '' }}>Rechazada (No Procede)</option>
              </select>
              @error('estado')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="respuesta_administracion" class="block text-xs font-semibold text-gray-700 mb-1">
                Respuesta Oficial / Observación Operativa
              </label>
              <textarea id="respuesta_administracion" name="respuesta_administracion" rows="4" maxlength="5000"
                        placeholder="Ingresa la acción correctiva ejecutada en conserjería o motivo del estado..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-[#1e3a8a] outline-none @error('respuesta_administracion') border-red-500 @enderror">{{ old('respuesta_administracion', $incidencia->respuesta_administracion) }}</textarea>
              <p class="text-[11px] text-gray-500 mt-0.5">Obligatorio si se marca como Resuelta o Rechazada.</p>
              @error('respuesta_administracion')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <button type="submit" class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-medium text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 shadow-sm transition-colors">
              <span class="material-symbols-outlined text-[16px]">save</span>
              <span>Guardar Cambios</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
  @include('components.alertas')
</body>
</html>
