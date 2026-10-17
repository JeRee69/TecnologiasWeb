<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Departamentos - EdiConnect</title>
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

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <div class="mb-6">
      <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al panel
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Padrón de Departamentos</h1>
          <p class="text-sm text-gray-600 mt-1">Registro de unidades habitacionales, control de estado y asignación de residentes.</p>
        </div>
      </div>
    </div>

    {{-- Notificaciones y feedback --}}
    @if (session('estado'))
      <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
        <span>{{ session('estado') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm shadow-sm">
        <div class="flex items-center gap-2 font-semibold mb-1">
          <span class="material-symbols-outlined text-red-600">error</span>
          <span>Por favor corrige los siguientes errores:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 ml-6 text-xs text-red-700">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Métricas de unidades --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">meeting_room</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $estadisticas['total'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Total de Unidades</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">check_circle</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-emerald-600">{{ $estadisticas['activos'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Departamentos Activos</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">block</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-600">{{ $estadisticas['inactivos'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Departamentos Inactivos</div>
        </div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8 items-start">
      {{-- Formulario Registro de Departamento (HU-07) --}}
      <section aria-labelledby="form-registro-heading" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm lg:sticky lg:top-6">
        <div class="flex items-center gap-2 mb-4">
          <span class="material-symbols-outlined text-[#1e3a8a]">add_home</span>
          <h2 id="form-registro-heading" class="text-lg font-bold text-gray-900">Registrar Departamento</h2>
        </div>
        <p class="text-xs text-gray-500 mb-5">Ingresa el número de unidad único y el piso respectivo para incorporarlo al padrón.</p>

        <form action="{{ route('admin.departamentos.store') }}" method="POST" class="space-y-4">
          @csrf
          <div>
            <label for="numero" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Número de Unidad <span class="text-red-500">*</span>
            </label>
            <input 
              id="numero" 
              name="numero" 
              type="text" 
              value="{{ old('numero') }}" 
              required 
              maxlength="50" 
              placeholder="Ej.: 101, 204-B, PH-1"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('numero'),
                'border-gray-300' => !$errors->has('numero')
              ])
            />
            @error('numero')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="piso" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Piso <span class="text-red-500">*</span>
            </label>
            <input 
              id="piso" 
              name="piso" 
              type="number" 
              value="{{ old('piso') }}" 
              required 
              min="-10" 
              max="200" 
              placeholder="Ej.: 1, 2, -1 (Subterráneo)"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('piso'),
                'border-gray-300' => !$errors->has('piso')
              ])
            />
            @error('piso')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="estado" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Estado Inicial
            </label>
            <select 
              id="estado" 
              name="estado" 
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700"
            >
              <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo (Permite residentes)</option>
              <option value="inactivo" @selected(old('estado') === 'inactivo')>Inactivo (Bloquea asociaciones)</option>
            </select>
            <p class="text-[11px] text-gray-500 mt-1">Los departamentos inactivos no admiten asignación de residentes (HU-08).</p>
          </div>

          <button 
            type="submit" 
            class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm rounded-lg px-4 py-2.5 flex items-center justify-center gap-2 transition-colors shadow-sm"
          >
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span>Registrar Departamento</span>
          </button>
        </form>
      </section>

      {{-- Listado de Departamentos --}}
      <section aria-labelledby="listado-heading" class="lg:col-span-2 space-y-4">
        {{-- Filtros y búsqueda --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
          <form method="GET" action="{{ route('admin.departamentos.index') }}" class="flex flex-1 w-full gap-2 items-center">
            <div class="relative flex-1">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-[18px]">search</span>
              <input 
                type="text" 
                name="buscar" 
                value="{{ $busqueda }}" 
                placeholder="Buscar por número o piso..." 
                class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-700"
              />
            </div>

            <select 
              name="estado" 
              onchange="this.form.submit()" 
              class="text-xs rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-700"
            >
              <option value="">Todos los estados</option>
              <option value="activo" @selected($filtroEstado === 'activo')>Solo Activos</option>
              <option value="inactivo" @selected($filtroEstado === 'inactivo')>Solo Inactivos</option>
            </select>

            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs px-3 py-2 rounded-lg font-medium">
              Filtrar
            </button>
            @if ($busqueda || $filtroEstado)
              <a href="{{ route('admin.departamentos.index') }}" class="text-xs text-red-600 hover:underline">Limpiar</a>
            @endif
          </form>
        </div>

        {{-- Tabla / Cards de Departamentos --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  <th class="px-5 py-3">Unidad</th>
                  <th class="px-5 py-3">Piso</th>
                  <th class="px-5 py-3">Estado</th>
                  <th class="px-5 py-3 text-center">Residentes</th>
                  <th class="px-5 py-3 text-center">Contactos</th>
                  <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($departamentos as $depto)
                  <tr class="hover:bg-gray-50/75 transition-colors">
                    <td class="px-5 py-4 font-bold text-gray-900 whitespace-nowrap">
                      <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400 text-[20px]">door_front</span>
                        <span>Depto. {{ $depto->numero }}</span>
                      </div>
                    </td>
                    <td class="px-5 py-4 text-gray-600 whitespace-nowrap">
                      Piso {{ $depto->piso }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                      @if ($depto->estaActivo())
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          Activo
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                          Inactivo
                        </span>
                      @endif
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $depto->residentes_count > 0 ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-400' }}">
                        {{ $depto->residentes_count }}
                      </span>
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $depto->contactos_emergencia_count > 0 ? 'bg-amber-50 text-amber-700 font-bold' : 'text-gray-400' }}">
                        {{ $depto->contactos_emergencia_count }}
                      </span>
                    </td>
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                      <div class="flex items-center justify-end gap-2">
                        <a 
                          href="{{ route('admin.departamentos.show', $depto) }}" 
                          class="bg-blue-50 hover:bg-blue-100 text-[#1e3a8a] text-xs font-semibold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition-colors"
                          title="Ficha técnica y residentes"
                        >
                          <span class="material-symbols-outlined text-[16px]">visibility</span>
                          <span>Gestionar</span>
                        </a>

                        {{-- Cambio rápido de estado (HU-08) --}}
                        <form action="{{ route('admin.departamentos.estado', $depto) }}" method="POST" class="inline">
                          @csrf
                          @method('PATCH')
                          @if ($depto->estaActivo())
                            <input type="hidden" name="estado" value="inactivo"/>
                            <button 
                              type="submit" 
                              class="text-xs text-gray-600 hover:text-red-700 px-2 py-1.5 rounded hover:bg-gray-100 transition-colors"
                              title="Desactivar departamento"
                              onclick="return confirm('¿Confirmas que deseas desactivar el Departamento {{ $depto->numero }}? No se podrán asociar nuevos residentes mientras permanezca inactivo.')"
                            >
                              Desactivar
                            </button>
                          @else
                            <input type="hidden" name="estado" value="activo"/>
                            <button 
                              type="submit" 
                              class="text-xs text-emerald-700 hover:text-emerald-900 font-medium px-2 py-1.5 rounded hover:bg-emerald-50 transition-colors"
                              title="Activar departamento"
                            >
                              Activar
                            </button>
                          @endif
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-500 text-sm">
                      <span class="material-symbols-outlined text-4xl text-gray-300 block mb-2">apartment</span>
                      No se encontraron departamentos registrados que coincidan con la búsqueda.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if ($departamentos->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
              {{ $departamentos->links() }}
            </div>
          @endif
        </div>
      </section>
    </div>
  </main>
</body>
</html>
