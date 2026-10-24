<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Editar Aviso - EdiConnect</title>
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
        <a href="{{ route('admin.dashboard') }}" class="font-bold text-xl tracking-tight">EdiConnect</a>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Panel de Administración</span>
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
      <a href="{{ route('admin.avisos.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a avisos
      </a>
      <h1 class="text-2xl font-bold text-gray-900">Editar Comunicado #{{ $aviso->id }}</h1>
      <p class="text-sm text-gray-600 mt-1">Actualiza los datos y la prioridad del aviso comunitario.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
      <form action="{{ route('admin.avisos.update', $aviso) }}" method="POST" class="space-y-5"
            data-swal-confirm="¿Guardar cambios en el aviso?"
            data-swal-text="Las modificaciones se reflejarán inmediatamente en el mural comunitario."
            data-swal-button="Guardar Cambios">
        @csrf
        @method('PUT')

        <div>
          <label for="titulo" class="block text-xs font-semibold text-gray-700 mb-1">Título del Aviso <span class="text-red-500">*</span></label>
          <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $aviso->titulo) }}" required maxlength="150"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('titulo') border-red-500 @enderror"/>
          @error('titulo')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="prioridad" class="block text-xs font-semibold text-gray-700 mb-1">Nivel de Prioridad <span class="text-red-500">*</span></label>
            <select id="prioridad" name="prioridad" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] outline-none">
              <option value="Baja" {{ old('prioridad', $aviso->prioridad) === 'Baja' ? 'selected' : '' }}>Baja (Informativo rutinario)</option>
              <option value="Media" {{ old('prioridad', $aviso->prioridad) === 'Media' ? 'selected' : '' }}>Media (Aviso general)</option>
              <option value="Alta" {{ old('prioridad', $aviso->prioridad) === 'Alta' ? 'selected' : '' }}>Alta (Importante / Citaciones)</option>
              <option value="Urgente" {{ old('prioridad', $aviso->prioridad) === 'Urgente' ? 'selected' : '' }}>Urgente (Cortes / Emergencias)</option>
            </select>
          </div>

          <div>
            <label for="estado" class="block text-xs font-semibold text-gray-700 mb-1">Estado de Publicación <span class="text-red-500">*</span></label>
            <select id="estado" name="estado" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] outline-none">
              <option value="Publicado" {{ old('estado', $aviso->estado) === 'Publicado' ? 'selected' : '' }}>Publicado (Visible a residentes)</option>
              <option value="Retirado" {{ old('estado', $aviso->estado) === 'Retirado' ? 'selected' : '' }}>Retirado (Oculto del mural)</option>
            </select>
          </div>
        </div>

        <div>
          <label for="contenido" class="block text-xs font-semibold text-gray-700 mb-1">Contenido del Comunicado <span class="text-red-500">*</span></label>
          <textarea id="contenido" name="contenido" rows="8" required maxlength="5000"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1e3a8a] focus:ring-1 focus:ring-[#1e3a8a] outline-none @error('contenido') border-red-500 @enderror">{{ old('contenido', $aviso->contenido) }}</textarea>
          @error('contenido')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <a href="{{ route('admin.avisos.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            Cancelar
          </a>
          <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-medium text-sm py-2 px-5 rounded-lg flex items-center gap-2 shadow-sm transition-colors">
            <span class="material-symbols-outlined text-[18px]">save</span>
            <span>Actualizar Aviso</span>
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
