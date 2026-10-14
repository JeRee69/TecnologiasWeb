<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Dashboard Administrador - EdiConnect</title>
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
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Panel de Administración</span>
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
        <h1 class="text-2xl font-bold text-gray-900">Bienvenido, {{ $usuario->nombre }}</h1>
        <p class="text-sm text-gray-500">Módulos autorizados para el rol Administrador</p>
      </div>
      <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-medium">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        Cuenta activa y verificada
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">group</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Usuarios y Roles</h3>
        <p class="text-sm text-gray-600 mb-4">Consulta los usuarios y activa o desactiva su acceso a la comunidad.</p>
        <a href="{{ route('admin.usuarios.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline">
          Administrar usuarios <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
        </a>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">meeting_room</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Departamentos</h3>
        <p class="text-sm text-gray-600 mb-4">Gestión del padrón de unidades residenciales y vinculación con residentes.</p>
        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md">Padrón de Unidades</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">receipt_long</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Gastos Comunes</h3>
        <p class="text-sm text-gray-600 mb-4">Emisión mensual de cobros, desglose financiero y aprobación de comprobantes.</p>
        <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md">Gestión Financiera</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">event_available</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Espacios Comunes</h3>
        <p class="text-sm text-gray-600 mb-4">Registra los espacios compartidos de la comunidad con su descripción y estado.</p>
        <a href="{{ route('admin.espacios-comunes.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline">
          Administrar espacios <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
        </a>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">campaign</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Mural Comunitario</h3>
        <p class="text-sm text-gray-600 mb-4">Publicación oficial de comunicados, mantenimientos y avisos urgentes.</p>
        <span class="text-xs font-semibold text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-md">Publicaciones</span>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-2xl">assignment_late</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Incidencias y Reclamos</h3>
        <p class="text-sm text-gray-600 mb-4">Revisión y actualización del estado de las solicitudes reportadas por residentes.</p>
        <span class="text-xs font-semibold text-rose-800 bg-rose-50 px-2.5 py-1 rounded-md">Atención Vecinal</span>
      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>
</body>
</html>
