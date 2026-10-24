<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Dashboard Residente - EdiConnect</title>
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
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Portal Residente</span>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
          <div class="font-semibold text-sm">{{ $usuario->nombre }}</div>
          <div class="text-xs text-blue-200">{{ $usuario->rol?->nombre }}</div>
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

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Hola, {{ $usuario->nombre }}</h1>
        <p class="text-sm text-gray-500">Bienvenido al portal de tu comunidad residencial</p>
      </div>
      <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-medium">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        Residente activo
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Mis Gastos Comunes</h2>
        <p class="text-sm text-gray-600 mb-4">Revisa tu saldo vigente, histórico de cobros y descarga el desglose en PDF.</p>
        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md">Estado Financiero</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">upload_file</span>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Cargar Comprobante</h2>
        <p class="text-sm text-gray-600 mb-4">Sube tu comprobante de transferencia bancaria para validación del administrador.</p>
        <span class="text-xs font-semibold text-[#1e3a8a] bg-blue-50 px-2.5 py-1 rounded-md">Validación de Pagos</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">calendar_month</span>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Reserva de Espacios</h2>
        <p class="text-sm text-gray-600 mb-4">Consulta por fecha los horarios disponibles y ocupados de los espacios comunes.</p>
        <a href="{{ route('residente.espacios-comunes.disponibilidad') }}" class="text-sm font-semibold text-purple-800 hover:underline">Consultar disponibilidad →</a>
        <a href="{{ route('residente.reservas.index') }}" class="block mt-3 text-sm font-semibold text-purple-800 hover:underline">Mis reservas →</a>
        <span class="inline-block mt-3 text-xs font-semibold text-purple-800 bg-purple-50 px-2.5 py-1 rounded-md">Áreas Comunes</span>
      </div>

      <a href="{{ route('residente.encomiendas.index') }}" class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-amber-300 transition-all block">
        <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-800 group-hover:bg-amber-600 group-hover:text-white flex items-center justify-center mb-4 transition-colors">
          <span class="material-symbols-outlined text-2xl">package_2</span>
        </div>
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 group-hover:text-amber-800 transition-colors mb-1">Mis Encomiendas</h2>
          <span class="material-symbols-outlined text-gray-400 group-hover:text-amber-800 text-[20px] transition-colors">arrow_forward</span>
        </div>
        <p class="text-sm text-gray-600 mb-4">Visualiza encomiendas recibidas en conserjería pendientes de retiro.</p>
        <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md">Ver Paquetes en Custodia</span>
      </a>

      {{-- Mural Informativo (HU-33) --}}
      <a href="{{ route('residente.mural.index') }}" class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all block">
        <div class="w-12 h-12 rounded-lg bg-indigo-100 text-indigo-800 group-hover:bg-indigo-700 group-hover:text-white flex items-center justify-center mb-4 transition-colors">
          <span class="material-symbols-outlined text-2xl">newspaper</span>
        </div>
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-800 transition-colors mb-1">Mural Informativo</h2>
          <span class="material-symbols-outlined text-gray-400 group-hover:text-indigo-800 text-[20px] transition-colors">arrow_forward</span>
        </div>
        <p class="text-sm text-gray-600 mb-4">Comunicados oficiales, citaciones a asamblea y mantenimientos programados.</p>
        <span class="text-xs font-semibold text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-md">Ver Tablón de Avisos</span>
      </a>

      {{-- Incidencias y Reclamos (HU-34, HU-35) --}}
      <a href="{{ route('residente.incidencias.index') }}" class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-rose-300 transition-all block">
        <div class="w-12 h-12 rounded-lg bg-rose-100 text-rose-800 group-hover:bg-rose-700 group-hover:text-white flex items-center justify-center mb-4 transition-colors">
          <span class="material-symbols-outlined text-2xl">report_problem</span>
        </div>
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 group-hover:text-rose-800 transition-colors mb-1">Mis Incidencias</h2>
          <span class="material-symbols-outlined text-gray-400 group-hover:text-rose-800 text-[20px] transition-colors">arrow_forward</span>
        </div>
        <p class="text-sm text-gray-600 mb-4">Registra solicitudes sobre espacios comunes y haz seguimiento en tiempo real.</p>
        <span class="text-xs font-semibold text-rose-800 bg-rose-50 px-2.5 py-1 rounded-md">Reportar y Consultar</span>
      </a>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
  @include('components.alertas')
</body>
</html>
