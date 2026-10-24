<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mis Incidencias y Reclamos - EdiConnect</title>
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

  <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6">
      <a href="{{ route('residente.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al panel
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Mis Incidencias y Reclamos</h1>
          <p class="text-sm text-gray-600 mt-1">Seguimiento en tiempo real de tus solicitudes reportadas a la administración.</p>
        </div>
        <a href="{{ route('residente.incidencias.create') }}" class="inline-flex items-center gap-2 bg-[#1e3a8a] hover:bg-blue-900 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-sm transition-colors shrink-0">
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          <span>Reportar Nueva Incidencia</span>
        </a>
      </div>
    </div>

    {{-- Feedback de sesión si SweetAlert2 no está disponible --}}
    @if (session('estado') || session('exito'))
      <div data-swal-feedback role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
        <span>{{ session('estado') ?? session('exito') }}</span>
      </div>
    @endif

    {{-- Métricas Rápidas --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Reportadas</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totales['total'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-amber-100 bg-amber-50/30 shadow-sm">
        <p class="text-xs font-medium text-amber-700 uppercase tracking-wider">Pendientes de Revisión</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ $totales['pendientes'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-blue-100 bg-blue-50/30 shadow-sm">
        <p class="text-xs font-medium text-blue-700 uppercase tracking-wider">En Proceso / Atención</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">{{ $totales['en_proceso'] }}</p>
      </div>
      <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/30 shadow-sm">
        <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">Resueltas</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $totales['resueltas'] }}</p>
      </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-wrap items-center gap-3">
      <form method="GET" action="{{ route('residente.incidencias.index') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
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
        @if (!empty($filtroEstado) || !empty($filtroCategoria))
          <a href="{{ route('residente.incidencias.index') }}" class="text-xs text-blue-700 hover:underline">Limpiar</a>
        @endif
      </form>
    </div>

    {{-- Listado de Incidencias --}}
    @if ($incidencias->isEmpty())
      <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-3xl">assignment_turned_in</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No tienes incidencias registradas</h3>
        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Cuando reportes un problema en áreas comunes o servicios, podrás seguir aquí su estado y resolución.</p>
        <div class="mt-5">
          <a href="{{ route('residente.incidencias.create') }}" class="inline-flex items-center gap-1.5 bg-[#1e3a8a] hover:bg-blue-900 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-[16px]">add</span>
            <span>Reportar una incidencia ahora</span>
          </a>
        </div>
      </div>
    @else
      <div class="space-y-4">
        @foreach ($incidencias as $incidencia)
          @php
            $estadoInfo = [
                'Pendiente' => ['badge' => 'bg-amber-100 text-amber-800 border-amber-200', 'icon' => 'hourglass_empty'],
                'En Proceso' => ['badge' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'engineering'],
                'Resuelta' => ['badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => 'check_circle'],
                'Rechazada' => ['badge' => 'bg-rose-100 text-rose-800 border-rose-200', 'icon' => 'cancel'],
            ][$incidencia->estado] ?? ['badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'help'];
          @endphp

          <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-gray-300 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
              <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                  <span class="font-mono text-xs font-bold bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">
                    Folio #{{ $incidencia->id }}
                  </span>
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                    {{ $incidencia->categoria }}
                  </span>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $estadoInfo['badge'] }}">
                    <span class="material-symbols-outlined text-[14px]">{{ $estadoInfo['icon'] }}</span>
                    <span>{{ $incidencia->estado }}</span>
                  </span>
                  @if ($incidencia->departamento)
                    <span class="text-xs text-gray-500">Depto {{ $incidencia->departamento->numero }}</span>
                  @endif
                </div>
                <h3 class="text-base font-bold text-gray-900">{{ $incidencia->titulo }}</h3>
              </div>

              <div class="text-xs text-gray-500 sm:text-right shrink-0">
                <span class="flex items-center gap-1 sm:justify-end">
                  <span class="material-symbols-outlined text-[15px]">schedule</span>
                  {{ $incidencia->created_at->format('d/m/Y H:i') }}
                </span>
                <a href="{{ route('residente.incidencias.show', $incidencia) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-[#1e3a8a] hover:underline mt-2">
                  <span>Ver seguimiento completo</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>

            <p class="text-sm text-gray-600 line-clamp-2 bg-gray-50/70 p-3 rounded-lg border border-gray-100">
              {{ $incidencia->descripcion }}
            </p>

            @if (!empty($incidencia->respuesta_administracion))
              <div class="mt-3 p-3 rounded-lg bg-blue-50/60 border border-blue-100 text-xs text-blue-900 flex items-start gap-2">
                <span class="material-symbols-outlined text-blue-700 text-[18px] shrink-0 mt-0.5">forum</span>
                <div>
                  <span class="font-semibold text-blue-950">Respuesta oficial de Administración:</span>
                  <p class="mt-0.5 text-blue-900">{{ Str::limit($incidencia->respuesta_administracion, 160) }}</p>
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </div>

      <div class="mt-6">
        {{ $incidencias->links() }}
      </div>
    @endif
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
  @include('components.alertas')
</body>
</html>
