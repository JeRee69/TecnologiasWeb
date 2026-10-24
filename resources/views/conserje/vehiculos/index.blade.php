@extends('layouts.app')
@section('title', 'Registro Vehicular y Control de Portón')
@section('content')
<div class="legacy-page">
<div class="mb-6">
      <a href="{{ route('conserje.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a la bitácora principal
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Control Vehicular y Portón de Acceso</h1>
          <p class="text-sm text-gray-600 mt-1">Verificación rápida de patentes y registro de vehículos autorizados por departamento (HU-30).</p>
        </div>
      </div>
    </div>

    {{-- BARRA DESTACADA DE VERIFICACIÓN RÁPIDA DE PATENTE (HU-30) --}}
    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white rounded-2xl p-6 mb-8 shadow-md">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-400 text-2xl">verified_user</span>
            <h2 class="text-lg font-bold">Verificador Rápido de Patente (Control de Portón)</h2>
          </div>
          <p class="text-xs text-blue-200 mt-1">Ingresa la patente del vehículo para comprobar al instante si cuenta con autorización vigente de acceso.</p>
        </div>

        <form method="GET" action="{{ route('conserje.vehiculos.index') }}" class="flex items-center gap-2 max-w-md w-full">
          <input
            type="text"
            name="verificar_patente"
            value="{{ $verificarPatente }}"
            placeholder="Ej.: BBCL12 o AB1234"
            maxlength="10"
            required
            class="flex-1 px-4 py-2.5 rounded-xl bg-white text-gray-900 font-mono font-bold tracking-wider uppercase text-sm border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-amber-400"
          />
          <button
            type="submit"
            class="bg-amber-500 hover:bg-amber-400 text-gray-900 font-bold px-4 py-2.5 rounded-xl text-sm transition-all hover:shadow flex items-center gap-1 shrink-0 shadow"
          >
            <span class="material-symbols-outlined text-[20px]">search</span>
            <span>Verificar</span>
          </button>
        </form>
      </div>

      {{-- Resultado de la Verificación en tiempo real --}}
      @if ($resultadoVerificacion)
        <div class="mt-4 pt-4 border-t border-blue-800">
          @if ($resultadoVerificacion['encontrado'] && $resultadoVerificacion['autorizado'])
            <div class="bg-emerald-500/20 border border-emerald-400 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-emerald-100">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-400 text-3xl">check_circle</span>
                <div>
                  <div class="font-bold text-white text-base flex items-center gap-2">
                    <span class="font-mono bg-white text-emerald-900 px-2.5 py-0.5 rounded font-extrabold text-sm">{{ $resultadoVerificacion['patente'] }}</span>
                    <span>VEHÍCULO AUTORIZADO — ACCESO PERMITIDO</span>
                  </div>
                  <div class="text-xs text-emerald-200 mt-0.5">
                    Asociado a: <strong class="text-white">Depto. {{ $resultadoVerificacion['vehiculo']->departamento?->numero }}</strong>
                    • Tipo: <span class="capitalize">{{ $resultadoVerificacion['vehiculo']->tipo }}</span>
                    @if ($resultadoVerificacion['vehiculo']->marca || $resultadoVerificacion['vehiculo']->modelo)
                      • {{ $resultadoVerificacion['vehiculo']->marca }} {{ $resultadoVerificacion['vehiculo']->modelo }} ({{ $resultadoVerificacion['vehiculo']->color ?? 'Color no esp.' }})
                    @endif
                  </div>
                </div>
              </div>
              <a href="{{ route('conserje.vehiculos.index') }}" class="text-xs text-emerald-200 hover:text-white underline">Cerrar verificación</a>
            </div>
          @else
            <div class="bg-rose-500/20 border border-rose-400 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-rose-100">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-400 text-3xl">gpp_bad</span>
                <div>
                  <div class="font-bold text-white text-base flex items-center gap-2">
                    <span class="font-mono bg-white text-rose-900 px-2.5 py-0.5 rounded font-extrabold text-sm">{{ $resultadoVerificacion['patente'] }}</span>
                    <span>VEHÍCULO NO AUTORIZADO — RESTRINGIR ACCESO</span>
                  </div>
                  <div class="text-xs text-rose-200 mt-0.5">
                    @if ($resultadoVerificacion['encontrado'])
                      Esta patente está registrada en el sistema pero su estado es <strong>{{ $resultadoVerificacion['vehiculo']->estado }}</strong> o su fecha de vigencia expiró.
                    @else
                      Esta patente no figura en el padrón de vehículos autorizados del edificio.
                    @endif
                  </div>
                </div>
              </div>
              <a href="{{ route('conserje.vehiculos.index') }}" class="text-xs text-rose-200 hover:text-white underline">Cerrar verificación</a>
            </div>
          @endif
        </div>
      @endif
    </div>

    {{-- Métricas Operativas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">check_box</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $metricas['total_autorizados'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Vehículos autorizados vigentes</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-blue-100 text-[#1e3a8a] flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">home</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-gray-900">{{ $metricas['total_residentes'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Vehículos de residentes</div>
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">block</span>
        </div>
        <div>
          <div class="text-2xl font-bold text-rose-700">{{ $metricas['total_revocados'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Permisos revocados o inactivos</div>
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

    <div class="grid lg:grid-cols-5 gap-8 items-start">
      {{-- Formulario para autorizar vehículo (HU-30) --}}
      <section aria-labelledby="registro-vehiculo-heading" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined text-[#1e3a8a] text-[22px]">directions_car</span>
          <h2 id="registro-vehiculo-heading" class="text-lg font-bold text-gray-900">Registrar Vehículo Autorizado</h2>
        </div>
        <p class="text-xs text-gray-500 mb-5">Incorpora una patente al padrón autorizado vinculado al departamento correspondiente.</p>

        <form action="{{ route('conserje.vehiculos.store') }}" method="POST" class="space-y-4">
          @csrf

          <div>
            <label for="departamento_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Departamento asociado <span class="text-red-500">*</span>
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
            <label for="patente" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Patente vehicular <span class="text-red-500">*</span>
            </label>
            <input
              id="patente"
              name="patente"
              type="text"
              value="{{ old('patente') }}"
              required
              maxlength="15"
              placeholder="Ej.: BBCL12 o AB1234"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase font-mono font-bold tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
            @error('patente')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="marca" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Marca <span class="text-gray-400 font-normal">(Opcional)</span>
              </label>
              <input
                id="marca"
                name="marca"
                type="text"
                value="{{ old('marca') }}"
                maxlength="60"
                placeholder="Ej.: Toyota"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
            </div>
            <div>
              <label for="modelo" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Modelo <span class="text-gray-400 font-normal">(Opcional)</span>
              </label>
              <input
                id="modelo"
                name="modelo"
                type="text"
                value="{{ old('modelo') }}"
                maxlength="60"
                placeholder="Ej.: Yaris"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="color" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Color <span class="text-gray-400 font-normal">(Opcional)</span>
              </label>
              <input
                id="color"
                name="color"
                type="text"
                value="{{ old('color') }}"
                maxlength="40"
                placeholder="Ej.: Gris Plata"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
            </div>
            <div>
              <label for="tipo" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Tipo de vínculo <span class="text-red-500">*</span>
              </label>
              <select
                id="tipo"
                name="tipo"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              >
                <option value="Residente" {{ old('tipo') === 'Residente' ? 'selected' : '' }}>Residente</option>
                <option value="Visita Frecuente" {{ old('tipo') === 'Visita Frecuente' ? 'selected' : '' }}>Visita Frecuente</option>
                <option value="Servicio" {{ old('tipo') === 'Servicio' ? 'selected' : '' }}>Servicio / Proveedor</option>
              </select>
            </div>
          </div>

          <div>
            <label for="vigente_hasta" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Autorizado hasta <span class="text-gray-400 font-normal">(Dejar en blanco para indefinido)</span>
            </label>
            <input
              id="vigente_hasta"
              name="vigente_hasta"
              type="date"
              value="{{ old('vigente_hasta') }}"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>

          <div>
            <label for="observaciones" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Observaciones / Estacionamiento <span class="text-gray-400 font-normal">(Opcional)</span>
            </label>
            <textarea
              id="observaciones"
              name="observaciones"
              rows="2"
              maxlength="500"
              placeholder="Ej.: Estacionamiento N° 45, torre A..."
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >{{ old('observaciones') }}</textarea>
          </div>

          <button
            type="submit"
            class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-all hover:shadow flex items-center justify-center gap-2 mt-2"
          >
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span>Registrar Autorización Vehicular</span>
          </button>
        </form>
      </section>

      {{-- Listado del Padrón de Vehículos --}}
      <section aria-labelledby="padron-vehicular-heading" class="lg:col-span-3 space-y-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 id="padron-vehicular-heading" class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="material-symbols-outlined text-gray-600 text-[20px]">directions_car</span>
              <span>Padrón de Vehículos Registrados</span>
            </h2>

            {{-- Filtros Rápidos --}}
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg text-xs font-medium">
              <a
                href="{{ route('conserje.vehiculos.index', ['filtro' => 'todos', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'todos' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                Todos
              </a>
              <a
                href="{{ route('conserje.vehiculos.index', ['filtro' => 'autorizados', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'autorizados' ? 'bg-white text-emerald-800 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                Autorizados
              </a>
              <a
                href="{{ route('conserje.vehiculos.index', ['filtro' => 'revocados', 'busqueda' => $busqueda]) }}"
                class="px-2.5 py-1 rounded-md transition-colors {{ $filtro === 'revocados' ? 'bg-white text-rose-800 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
              >
                Revocados
              </a>
            </div>
          </div>

          {{-- Buscador en la tabla --}}
          <form method="GET" action="{{ route('conserje.vehiculos.index') }}" class="mb-4 flex gap-2">
            <input type="hidden" name="filtro" value="{{ $filtro }}"/>
            <div class="relative flex-1">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-[18px]">search</span>
              <input
                type="text"
                name="busqueda"
                value="{{ $busqueda }}"
                placeholder="Buscar por patente, marca, modelo o depto..."
                class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
            </div>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold px-3 py-2 rounded-lg transition-colors">
              Buscar
            </button>
            @if ($busqueda)
              <a href="{{ route('conserje.vehiculos.index', ['filtro' => $filtro]) }}" class="bg-gray-50 hover:bg-gray-100 text-gray-500 text-xs px-2.5 py-2 rounded-lg flex items-center">
                Limpiar
              </a>
            @endif
          </form>

          {{-- Tabla de Registros --}}
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-gray-200 bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                  <th class="py-3 px-3.5">Patente</th>
                  <th class="py-3 px-3.5">Vehículo</th>
                  <th class="py-3 px-3.5">Depto</th>
                  <th class="py-3 px-3.5">Estado</th>
                  <th class="py-3 px-3.5 text-right">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700 bg-white">
                @forelse ($vehiculos as $vehiculo)
                  <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <span class="font-mono font-bold text-sm bg-gray-100 px-2.5 py-1 rounded border border-gray-300 text-gray-900 tracking-wider">
                        {{ $vehiculo->patente }}
                      </span>
                      <div class="text-[10px] text-gray-400 mt-1 capitalize">{{ $vehiculo->tipo }}</div>
                    </td>

                    <td class="py-3 px-3.5">
                      <div class="font-semibold text-gray-900">
                        {{ $vehiculo->marca ?? 'Marca no esp.' }} {{ $vehiculo->modelo ?? '' }}
                      </div>
                      <div class="text-[11px] text-gray-500">
                        Color: {{ $vehiculo->color ?? 'N/A' }}
                      </div>
                      @if ($vehiculo->observaciones)
                        <div class="text-[10px] text-gray-400 italic truncate max-w-xs mt-0.5" title="{{ $vehiculo->observaciones }}">
                          "{{ $vehiculo->observaciones }}"
                        </div>
                      @endif
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <span class="font-semibold text-blue-900 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                        Depto. {{ $vehiculo->departamento?->numero ?? 'N/A' }}
                      </span>
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap">
                      @if ($vehiculo->estaAutorizado())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          Autorizado
                        </span>
                        @if ($vehiculo->vigente_hasta)
                          <div class="text-[10px] text-gray-400 mt-0.5">Hasta: {{ $vehiculo->vigente_hasta->format('d/m/Y') }}</div>
                        @endif
                      @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                          <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                          {{ $vehiculo->estado === 'Revocado' ? 'Revocado' : 'Vencido' }}
                        </span>
                      @endif
                    </td>

                    <td class="py-3 px-3.5 whitespace-nowrap text-right">
                      <form
                        action="{{ route('conserje.vehiculos.estado', $vehiculo) }}"
                        method="POST"
                        @if ($vehiculo->estado === 'Autorizado')
                          data-swal-confirm="¿Revocar autorización vehicular?"
                          data-swal-text="La patente {{ $vehiculo->patente }} (Depto. {{ $vehiculo->departamento?->numero ?? 'N/A' }}) dejará de contar con acceso al portón."
                          data-swal-danger="true"
                          data-swal-button="Sí, revocar acceso"
                        @else
                          data-swal-confirm="¿Reactivar autorización vehicular?"
                          data-swal-text="La patente {{ $vehiculo->patente }} (Depto. {{ $vehiculo->departamento?->numero ?? 'N/A' }}) volverá a quedar habilitada para ingresar al condominio."
                          data-swal-button="Sí, reactivar acceso"
                        @endif
                      >
                        @csrf
                        @method('PATCH')
                        @if ($vehiculo->estado === 'Autorizado')
                          <button
                            type="submit"
                            title="Revocar autorización de portón"
                            class="text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 transition-colors text-xs font-semibold"
                          >
                            Revocar
                          </button>
                        @else
                          <button
                            type="submit"
                            title="Habilitar autorización de portón"
                            class="text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 transition-colors text-xs font-semibold"
                          >
                            Habilitar
                          </button>
                        @endif
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="py-12 px-4 text-center">
                      <div class="max-w-xs mx-auto">
                        <span class="material-symbols-outlined text-5xl text-gray-300 block mb-3">no_crash</span>
                        <p class="font-semibold text-gray-800 text-sm">Sin vehículos registrados</p>
                        <p class="text-xs text-gray-500 mt-1">No se encontraron patentes registradas con los filtros actuales.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Paginación --}}
          @if ($vehiculos->hasPages())
            <div class="mt-4 pt-3 border-t border-gray-100">
              {{ $vehiculos->links() }}
            </div>
          @endif
        </div>
      </section>
    </div>
</div>
@endsection
