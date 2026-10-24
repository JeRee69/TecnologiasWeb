<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Incidencia #{{ $incidencia->id }} - EdiConnect</title>
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

  <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6">
      <a href="{{ route('residente.incidencias.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al listado de incidencias
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Seguimiento de Incidencia #{{ $incidencia->id }}</h1>
          <p class="text-sm text-gray-500 mt-1">Registrada el {{ $incidencia->created_at->isoFormat('D [de] MMMM [de] YYYY, HH:mm') ?? $incidencia->created_at->format('d/m/Y H:i') }}</p>
        </div>
        @php
          $estadoBadge = [
              'Pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
              'En Proceso' => 'bg-blue-100 text-blue-800 border-blue-200',
              'Resuelta' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
              'Rechazada' => 'bg-rose-100 text-rose-800 border-rose-200',
          ][$incidencia->estado] ?? 'bg-gray-100 text-gray-700 border-gray-200';
        @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold border {{ $estadoBadge }}">
          <span class="w-2 h-2 rounded-full {{ $incidencia->estado === 'Resuelta' ? 'bg-emerald-600' : ($incidencia->estado === 'Pendiente' ? 'bg-amber-600' : ($incidencia->estado === 'En Proceso' ? 'bg-blue-600' : 'bg-rose-600')) }}"></span>
          <span>Estado actual: {{ $incidencia->estado }}</span>
        </span>
      </div>
    </div>

    <div class="space-y-6">
      {{-- Tarjeta de Datos de la Incidencia --}}
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 mb-4 border-b border-gray-100 text-xs text-gray-600">
          <div>
            <span class="font-semibold text-gray-500 block">Categoría:</span>
            <span class="text-sm font-medium text-gray-900">{{ $incidencia->categoria }}</span>
          </div>
          <div>
            <span class="font-semibold text-gray-500 block">Departamento Origen:</span>
            <span class="text-sm font-medium text-gray-900">{{ $incidencia->departamento ? 'Depto ' . $incidencia->departamento->numero : 'Sin departamento asignado' }}</span>
          </div>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-2">{{ $incidencia->titulo }}</h2>
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-gray-50/70 p-4 rounded-lg border border-gray-100">
          {!! nl2br(e($incidencia->descripcion)) !!}
        </div>
      </div>

      {{-- Tarjeta de Respuesta de la Administración / Conserjería --}}
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-3">
          <span class="material-symbols-outlined text-[#1e3a8a]">support_agent</span>
          <h2 class="text-base font-bold text-gray-900">Respuesta Oficial del Personal Autorizado</h2>
        </div>

        @if (!empty($incidencia->respuesta_administracion))
          <div class="bg-blue-50/70 border border-blue-100 rounded-lg p-4 text-sm text-blue-950 space-y-2">
            <p class="leading-relaxed whitespace-pre-line">{{ $incidencia->respuesta_administracion }}</p>
            <div class="pt-2 border-t border-blue-200/60 text-xs text-blue-800 flex items-center justify-between">
              <span>Atendido por: {{ $incidencia->personalResolucion?->nombre ?? 'Personal de Comunidad' }} ({{ $incidencia->personalResolucion?->rol?->nombre ?? 'Administración' }})</span>
              @if ($incidencia->fecha_resolucion)
                <span>Fecha: {{ $incidencia->fecha_resolucion->isoFormat('D MMM YYYY, HH:mm') ?? $incidencia->fecha_resolucion->format('d/m/Y H:i') }}</span>
              @endif
            </div>
          </div>
        @else
          <div class="bg-gray-50 border border-dashed border-gray-200 rounded-lg p-6 text-center text-xs text-gray-500">
            <span class="material-symbols-outlined text-gray-400 text-3xl mb-1">hourglass_top</span>
            <p class="font-medium text-gray-700">Tu requerimiento aún no cuenta con una respuesta formal registrada.</p>
            <p class="mt-0.5">El equipo de administración y conserjería ha sido notificado y responderá a la brevedad.</p>
          </div>
        @endif
      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
  @include('components.alertas')
</body>
</html>
