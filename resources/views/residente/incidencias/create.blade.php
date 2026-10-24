<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reportar Incidencia - EdiConnect</title>
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
        <a href="{{ route('residente.dashboard') }}" class="font-bold text-xl tracking-tight">EdiConnect</a>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Portal Residente</span>
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

  <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6">
      <a href="{{ route('residente.incidencias.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a mis incidencias
      </a>
      <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined text-rose-600 text-3xl">report_problem</span>
        Reportar Incidencia o Reclamo
      </h1>
      <p class="text-sm text-gray-600 mt-1">Registra fallas, desperfectos en áreas comunes o situaciones anómalas en el edificio.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
      <form action="{{ route('residente.incidencias.store') }}" method="POST" class="space-y-5"
            data-swal-confirm="¿Enviar reporte de incidencia?"
            data-swal-text="Tu requerimiento quedará en estado 'Pendiente' y será revisado por la administración y conserjería."
            data-swal-button="Enviar Reporte">
        @csrf

        {{-- Selección de Departamento Asociado --}}
        <div>
          <label for="departamento_id" class="block text-xs font-semibold text-gray-700 mb-1">
            Departamento Origen del Reporte
          </label>
          @if ($departamentos->count() > 1)
            <select id="departamento_id" name="departamento_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] outline-none">
              @foreach ($departamentos as $depto)
                <option value="{{ $depto->id }}" {{ old('departamento_id') == $depto->id ? 'selected' : '' }}>
                  Departamento {{ $depto->numero }} (Piso {{ $depto->piso }})
                </option>
              @endforeach
            </select>
          @elseif ($departamentos->count() === 1)
            <input type="hidden" name="departamento_id" value="{{ $departamentos->first()->id }}"/>
            <div class="w-full bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg text-sm text-gray-700 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-gray-500">meeting_room</span>
              <span>Departamento {{ $departamentos->first()->numero }} (Piso {{ $departamentos->first()->piso }})</span>
            </div>
          @else
            <div class="w-full bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg text-xs text-amber-800">
              No tienes un departamento específico asignado actualmente. Tu incidencia se registrará a tu nombre personal.
            </div>
          @endif
        </div>

        {{-- Categoría del Problema --}}
        <div>
          <label for="categoria" class="block text-xs font-semibold text-gray-700 mb-1">
            Categoría del Problema <span class="text-red-500">*</span>
          </label>
          <select id="categoria" name="categoria" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] outline-none @error('categoria') border-red-500 @enderror">
            <option value="">-- Selecciona una categoría --</option>
            @foreach ($categorias as $cat)
              <option value="{{ $cat }}" {{ old('categoria') === $cat ? 'selected' : '' }}>
                {{ $cat }}
              </option>
            @endforeach
          </select>
          @error('categoria')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        {{-- Asunto / Título --}}
        <div>
          <label for="titulo" class="block text-xs font-semibold text-gray-700 mb-1">
            Título / Resumen breve <span class="text-red-500">*</span>
          </label>
          <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" required maxlength="150"
                 placeholder="Ej: Fuga de agua en lavandería / Ascensor 2 fuera de servicio"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('titulo') border-red-500 @enderror"/>
          @error('titulo')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        {{-- Descripción detallada --}}
        <div>
          <label for="descripcion" class="block text-xs font-semibold text-gray-700 mb-1">
            Descripción Detallada <span class="text-red-500">*</span>
          </label>
          <textarea id="descripcion" name="descripcion" rows="6" required maxlength="5000"
                    placeholder="Describe cuándo comenzó el problema, la ubicación exacta y cualquier detalle relevante para que el personal pueda verificarlo y gestionarlo..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('descripcion') border-red-500 @enderror">{{ old('descripcion') }}</textarea>
          @error('descripcion')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="rounded-lg bg-blue-50/60 border border-blue-100 p-3 text-xs text-blue-800 flex items-start gap-2">
          <span class="material-symbols-outlined text-blue-700 text-[18px] shrink-0 mt-0.5">info</span>
          <div>
            Al enviar, se generará un número de seguimiento único. Podrás consultar la respuesta oficial del equipo de administración y el estado del requerimiento desde tu panel.
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <a href="{{ route('residente.incidencias.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            Cancelar
          </a>
          <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-medium text-sm py-2 px-5 rounded-lg flex items-center gap-2 shadow-sm transition-colors">
            <span class="material-symbols-outlined text-[18px]">send</span>
            <span>Registrar Incidencia</span>
          </button>
        </div>
      </form>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
  @include('components.alertas')
</body>
</html>
