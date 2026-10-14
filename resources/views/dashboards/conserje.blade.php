<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Bitácora de Conserjería - EdiConnect</title>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 antialiased min-h-screen flex flex-col">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-3xl">apartment</span>
        <span class="font-bold text-xl tracking-tight">EdiConnect</span>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Módulo de Conserjería</span>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
          <div class="font-semibold text-sm">{{ $usuario->nombre }}</div>
          <div class="text-xs text-blue-200">{{ $usuario->rol?->nombre }}</div>
        </div>
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

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Bitácora Operativa, {{ $usuario->nombre }}</h1>
        <p class="text-sm text-gray-500">Módulos autorizados para el rol Conserje</p>
      </div>
      <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-medium">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        Turno activo
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">inventory_2</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Recepción de Encomiendas</h3>
        <p class="text-sm text-gray-600 mb-4">Registro de paquetes recibidos con departamento de destino y empresa de delivery.</p>
        <span class="text-xs font-semibold text-[#1e3a8a] bg-blue-50 px-2.5 py-1 rounded-md">Recepción y Custodia</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">badge</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Control de Visitas</h3>
        <p class="text-sm text-gray-600 mb-4">Registro inmediato de visitas con RUT, nombre y departamento de destino.</p>
        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md">Bitácora de Accesos</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">directions_car</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Registro Vehicular</h3>
        <p class="text-sm text-gray-600 mb-4">Verificación de patentes autorizadas para estacionamientos de visitas y residentes.</p>
        <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md">Control de Portón</span>
      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
</body>
</html>
