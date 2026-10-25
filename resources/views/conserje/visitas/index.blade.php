@extends('layouts.app')
@section('title', 'Control de Visitas')
@section('content')
<div class="legacy-page">
<div class="mb-6">
      <a href="{{ route('conserje.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a la bitácora principal
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Control de Visitas y Accesos</h1>
          <p class="text-sm text-gray-600 mt-1">Registro de personas externas que ingresan al edificio, vinculadas al departamento de destino (HU-29).</p>
        </div>
      </div>
    </div>

    {{-- Tarjetas de Métricas Operativas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">person_pin</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $metricas['total_en_recinto'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Visitas en recinto ahora</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-blue-100 text-[#1e3a8a] flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">today</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $metricas['total_hoy'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Ingresos registrados hoy</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">history</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $metricas['total_historico'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Histórico total registrado</div>
        </div>
      </div>
    </div>

    {{-- Feedback de operaciones / Notificaciones Flash --}}
    @if (session('estado') || session('exito'))
      <div data-swal-feedback role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600 text-[22px]">check_circle</span>
        <span class="font-medium">{{ session('estado') ?? session('exito') }}</span>
      </div>
    @endif

    @if (session('error'))
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-red-600 text-[22px]">error</span>
        <span class="font-medium">{{ session('error') }}</span>
      </div>
    @endif

    @if (session('alerta'))
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-amber-600 text-[22px]">warning</span>
        <span class="font-medium">{{ session('alerta') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-900 p-4 text-sm shadow-sm">
        <div class="font-semibold mb-1.5 flex items-center gap-2">
          <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
          <span>Por favor corrige los siguientes inconvenientes:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs text-red-800 ml-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
      {{-- Formulario para registrar visita recibida (HU-29) --}}
      <section aria-labelledby="registro-visita-heading" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined text-[#1e3a8a] text-[22px]">badge</span>
          <h2 id="registro-visita-heading" class="text-lg font-bold text-gray-900">Registrar Visita</h2>
        </div>
        <p class="text-xs text-gray-500 mb-5">Ingresa los datos del visitante para habilitar su acceso a la bitácora operativa.</p>

        <form action="{{ route('conserje.visitas.store') }}" method="POST" class="space-y-4">
          @csrf

          <div>
            <label for="departamento_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Departamento de destino <span class="text-red-500">*</span>
            </label>
            <select
              id="departamento_id"
              name="departamento_id"
              required
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                'border-red-400 bg-red-50' => $errors->has('departamento_id'),
                'border-gray-300' => !$errors->has('departamento_id')
              ])
            >
              <option value="" disabled {{ old('departamento_id') ? '' : 'selected' }}>Selecciona departamento</option>
              @foreach ($departamentos as $depto)
                <option value="{{ $depto->id }}" {{ (string) old('departamento_id') === (string) $depto->id ? 'selected' : '' }}>
                  Departamento {{ $depto->numero }} (Piso {{ $depto->piso }})
                </option>
              @endforeach
            </select>
            @error('departamento_id')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="nombre_visitante" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Nombre completo de la visita <span class="text-red-500">*</span>
            </label>
            <input
              id="nombre_visitante"
              name="nombre_visitante"
              type="text"
              value="{{ old('nombre_visitante') }}"
              required
              maxlength="150"
              placeholder="Ej.: Carolina Morales R."
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                'border-red-400 bg-red-50' => $errors->has('nombre_visitante'),
                'border-gray-300' => !$errors->has('nombre_visitante')
              ])
            />
            @error('nombre_visitante')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="rut_visitante" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              RUT / Documento de identificación <span class="text-gray-400 font-normal">(Opcional)</span>
            </label>
            <input
              id="rut_visitante"
              name="rut_visitante"
              type="text"
              value="{{ old('rut_visitante') }}"
              maxlength="20"
              placeholder="Ej.: 12.345.678-9 o Pasaporte"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                'border-red-400 bg-red-50' => $errors->has('rut_visitante'),
                'border-gray-300' => !$errors->has('rut_visitante')
              ])
            />
            @error('rut_visitante')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="fecha" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Fecha de ingreso
              </label>
              <input
                id="fecha"
                name="fecha"
                type="date"
                value="{{ old('fecha', date('Y-m-d')) }}"
                @class([
                  'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                  'border-red-400 bg-red-50' => $errors->has('fecha'),
                  'border-gray-300' => !$errors->has('fecha')
                ])
              />
            </div>
            <div>
              <label for="hora" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Hora de ingreso
              </label>
              <input
                id="hora"
                name="hora"
                type="time"
                value="{{ old('hora', date('H:i')) }}"
                @class([
                  'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                  'border-red-400 bg-red-50' => $errors->has('hora'),
                  'border-gray-300' => !$errors->has('hora')
                ])
              />
            </div>
          </div>

          <div>
            <label for="observaciones" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Observaciones / Motivo <span class="text-gray-400 font-normal">(Opcional)</span>
            </label>
            <textarea
              id="observaciones"
              name="observaciones"
              rows="3"
              maxlength="500"
              placeholder="Ej.: Técnico de internet, familiar, entrega especial..."
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >{{ old('observaciones') }}</textarea>
            @error('observaciones')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <button
            type="submit"
            class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-all hover:shadow flex items-center justify-center gap-2 mt-2"
          >
            <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
            <span>Registrar Ingreso de Visita</span>
          </button>
        </form>
      </section>

      {{-- Listado y Bitácora de Visitas --}}
      <section aria-labelledby="bitacora-visitas-heading" class="lg:col-span-3 space-y-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 id="bitacora-visitas-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="material-symbols-outlined text-gray-600 text-[20px]">format_list_bulleted</span>
              <span>Bitácora de Accesos</span>
            </h2>

            {{-- Filtros Rápidos --}}
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg text-xs font-medium">
              <a
                href="{{ route('conserje.visitas.index', ['filtro' => 'todas', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'todas' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                Todas
              </a>
              <a
                href="{{ route('conserje.visitas.index', ['filtro' => 'en_recinto', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'en_recinto' ? 'bg-white text-emerald-800 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                En Recinto
              </a>
              <a
                href="{{ route('conserje.visitas.index', ['filtro' => 'finalizadas', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'finalizadas' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                Finalizadas
              </a>
            </div>
          </div>

          {{-- Buscador --}}
          <form method="GET" action="{{ route('conserje.visitas.index') }}" class="mb-4 flex gap-2">
            <input type="hidden" name="filtro" value="{{ $filtro }}"/>
            <div class="relative flex-1 min-w-0">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-[18px]">search</span>
              <input
                type="text"
                name="busqueda"
                value="{{ $busqueda }}"
                placeholder="Buscar por nombre, RUT o departamento..."
                class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
            </div>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold px-3 py-2 rounded-lg transition-colors">
              Buscar
            </button>
            @if ($busqueda)
              <a href="{{ route('conserje.visitas.index', ['filtro' => $filtro]) }}" class="bg-gray-50 hover:bg-gray-100 text-gray-500 text-xs px-2.5 py-2 rounded-lg flex items-center">
                Limpiar
              </a>
            @endif
          </form>

          {{-- Tabla de Registros --}}
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-gray-200 bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                  <th class="py-3 px-3.5">Estado</th>
                  <th class="py-3 px-3.5">Visitante</th>
                  <th class="py-3 px-3.5">Destino</th>
                  <th class="py-3 px-3.5">Ingreso / Salida</th>
                  <th class="py-3 px-3.5 text-right">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700 bg-white">
                @forelse ($visitas as $visita)
                  <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3 px-3.5 whitespace-nowrap">
                      @if ($visita->estado === 'En Recinto')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                          En Recinto
                        </span>
                      @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                          Finalizada
                        </span>
                      @endif
                    </td>

                    <td class="py-3 px-3.5">
                      <div class="font-semibold text-gray-900 text-sm">{{ $visita->nombre_visitante }}</div>
                      @if ($visita->rut_visitante)
                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">ID: {{ $visita->rut_visitante }}</div>
                      @endif
                      @if ($visita->observaciones)
                        <div class="text-[11px] text-gray-500 italic mt-0.5 max-w-xs truncate" title="{{ $visita->observaciones }}">
                          "{{ $visita->observaciones }}"
                        </div>
                      @endif
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <span class="font-semibold text-blue-900 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                        Depto. {{ $visita->departamento?->numero ?? 'N/A' }}
                      </span>
                      <div class="text-[10px] text-gray-400 mt-0.5">Piso {{ $visita->departamento?->piso ?? '-' }}</div>
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <div class="font-medium text-gray-800 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-emerald-600">login</span>
                        <span>{{ $visita->fecha_ingreso ? $visita->fecha_ingreso->format('d/m/Y H:i') : '-' }}</span>
                      </div>
                      @if ($visita->fecha_salida)
                        <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                          <span class="material-symbols-outlined text-[14px] text-gray-400">logout</span>
                          <span>{{ $visita->fecha_salida->format('d/m/Y H:i') }}</span>
                        </div>
                      @endif
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap text-right">
                      @if ($visita->estado === 'En Recinto')
                        <form
                          action="{{ route('conserje.visitas.salida', $visita) }}"
                          method="POST"
                          data-swal-confirm="¿Registrar salida de la visita?"
                          data-swal-text="Se registrará la fecha y hora de egreso de {{ $visita->nombre_visitante }} (Depto. {{ $visita->departamento?->numero ?? 'N/A' }})."
                          data-swal-button="Sí, registrar salida"
                        >
                          @csrf
                          @method('PATCH')
                          <button
                            type="submit"
                            class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-900 font-semibold px-2.5 py-1.5 rounded-lg border border-amber-200 transition-colors text-xs"
                          >
                            <span class="material-symbols-outlined text-[16px]">door_front</span>
                            <span>Marcar Salida</span>
                          </button>
                        </form>
                      @else
                        <span class="text-[11px] text-gray-400 font-medium">Egreso completado</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="py-12 px-4 text-center">
                      <div class="max-w-xs mx-auto">
                        <span class="material-symbols-outlined text-5xl text-gray-300 block mb-3">person_off</span>
                        <p class="font-semibold text-gray-800 text-sm">Sin visitas registradas</p>
                        <p class="text-xs text-gray-500 mt-1">No se encontraron registros de visitas que coincidan con los filtros seleccionados.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Paginación --}}
          @if ($visitas->hasPages())
            <div class="mt-4 pt-3 border-t border-gray-100">
              {{ $visitas->links() }}
            </div>
          @endif
        </div>
      </section>
    </div>
</div>
@endsection
