@extends('layouts.app')
@section('title', 'Bitácora de Encomiendas')
@section('content')
<div class="legacy-page">
<div class="mb-6">
      <a href="{{ route('conserje.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a la bitácora
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bitácora de Encomiendas</h1>
          <p class="text-sm text-gray-600 mt-1">Recepción, control de tiempo de permanencia y registro de retiro de paquetes en conserjería.</p>
        </div>
      </div>
    </div>

    {{-- Métricas de Permanencia para Conserjería (HU-28) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-100 text-[#1e3a8a] flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">package_2</span>
        </div>
        <div>
          <div class="text-xl font-bold text-gray-900">{{ $metricas['total_pendientes'] }}</div>
          <div class="text-xs text-gray-500 font-medium">En custodia pendiente</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">hourglass_top</span>
        </div>
        <div>
          <div class="text-sm font-bold text-gray-900">
            @if ($metricas['mayor_permanencia'])
              Depto. {{ $metricas['mayor_permanencia']->departamento?->numero }} ({{ $metricas['mayor_permanencia']->tiempoTranscurrido() }})
            @else
              Sin paquetes
            @endif
          </div>
          <div class="text-xs text-gray-500 font-medium">Mayor tiempo en custodia</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">alarm</span>
        </div>
        <div>
          <div class="text-xl font-bold text-rose-700">{{ $metricas['total_prolongadas'] }}</div>
          <div class="text-xs text-gray-500 font-medium">Permanencia prolongada (&gt; 3 días)</div>
        </div>
      </div>
    </div>

    {{-- Feedback de operaciones --}}
    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
        <span>{{ session('estado') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm shadow-sm">
        <div class="font-semibold mb-1 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
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
      {{-- Formulario para registrar encomienda recibida (HU-25) --}}
      <section aria-labelledby="registro-encomienda-heading" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined text-[#1e3a8a] text-[22px]">inventory_2</span>
          <h2 id="registro-encomienda-heading" class="text-lg font-bold text-gray-900">Registrar encomienda</h2>
        </div>
        <p class="text-xs text-gray-500 mb-5">Ingresa los datos del paquete recibido para incorporarlo a la bitácora digital.</p>

        <form action="{{ route('conserje.encomiendas.store') }}" method="POST" class="space-y-4">
          @csrf

          <div>
            <label for="departamento_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Departamento destinatario <span class="text-red-500">*</span>
            </label>
            <select
              id="departamento_id"
              name="departamento_id"
              required
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-700',
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
            <label for="empresa_delivery" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Empresa de delivery / Courier <span class="text-red-500">*</span>
            </label>
            <input
              id="empresa_delivery"
              name="empresa_delivery"
              type="text"
              list="empresas-list"
              value="{{ old('empresa_delivery') }}"
              required
              maxlength="100"
              placeholder="Ej.: Chilexpress, Starken, Mercado Libre..."
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('empresa_delivery'),
                'border-gray-300' => !$errors->has('empresa_delivery')
              ])
            />
            <datalist id="empresas-list">
              <option value="Chilexpress"></option>
              <option value="Starken"></option>
              <option value="Correos de Chile"></option>
              <option value="Mercado Libre"></option>
              <option value="Blue Express"></option>
              <option value="DHL"></option>
              <option value="FedEx"></option>
              <option value="PedidosYa"></option>
              <option value="Rappi"></option>
              <option value="Uber Eats"></option>
            </datalist>
            @error('empresa_delivery')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="fecha" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Fecha de recepción
              </label>
              <input
                id="fecha"
                name="fecha"
                type="date"
                value="{{ old('fecha', date('Y-m-d')) }}"
                @class([
                  'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                  'border-red-400 bg-red-50' => $errors->has('fecha'),
                  'border-gray-300' => !$errors->has('fecha')
                ])
              />
              @error('fecha')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="hora" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Hora de recepción
              </label>
              <input
                id="hora"
                name="hora"
                type="time"
                value="{{ old('hora', date('H:i')) }}"
                @class([
                  'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                  'border-red-400 bg-red-50' => $errors->has('hora'),
                  'border-gray-300' => !$errors->has('hora')
                ])
              />
              @error('hora')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div>
            <label for="codigo_seguimiento" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Código de rastreo / paquete <span class="text-xs text-gray-400 font-normal lowercase">(opcional)</span>
            </label>
            <input
              id="codigo_seguimiento"
              name="codigo_seguimiento"
              type="text"
              value="{{ old('codigo_seguimiento') }}"
              maxlength="100"
              placeholder="Ej.: CHX-99887766"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('codigo_seguimiento'),
                'border-gray-300' => !$errors->has('codigo_seguimiento')
              ])
            />
            @error('codigo_seguimiento')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="descripcion" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Descripción u observación <span class="text-xs text-gray-400 font-normal lowercase">(opcional)</span>
            </label>
            <textarea
              id="descripcion"
              name="descripcion"
              rows="2"
              maxlength="500"
              placeholder="Ej.: Sobre sellado, caja mediana frágil..."
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('descripcion'),
                'border-gray-300' => !$errors->has('descripcion')
              ])
            >{{ old('descripcion') }}</textarea>
            @error('descripcion')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <button
            type="submit"
            class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span class="material-symbols-outlined text-[18px]">add_box</span>
            <span>Registrar encomienda</span>
          </button>
        </form>
      </section>

      {{-- Listado de la Bitácora de Encomiendas con control de permanencia (HU-28) --}}
      <section aria-labelledby="listado-encomiendas-heading" class="lg:col-span-3 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 id="listado-encomiendas-heading" class="text-base font-bold text-gray-900">Bitácora de paquetes</h2>
            <span class="text-xs text-gray-500">Mostrando {{ $encomiendas->count() }} de {{ $encomiendas->total() }} encomiendas</span>
          </div>

          {{-- Opciones de ordenamiento y filtrado de permanencia (HU-28) --}}
          <div class="flex flex-wrap items-center gap-1 text-xs">
            <span class="text-gray-500 font-medium mr-1">Ordenar:</span>
            <a href="{{ route('conserje.encomiendas.index', ['orden' => 'recientes', 'filtro' => $filtro]) }}"
               @class([
                 'px-2.5 py-1 rounded-md border font-medium transition-colors',
                 'bg-blue-50 border-blue-200 text-[#1e3a8a] font-bold' => $orden !== 'mayor_permanencia',
                 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' => $orden === 'mayor_permanencia'
               ])>
              Recientes
            </a>
            <a href="{{ route('conserje.encomiendas.index', ['orden' => 'mayor_permanencia', 'filtro' => $filtro]) }}"
               @class([
                 'inline-flex items-center gap-1 px-2.5 py-1 rounded-md border font-medium transition-colors',
                 'bg-amber-50 border-amber-300 text-amber-900 font-bold' => $orden === 'mayor_permanencia',
                 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' => $orden !== 'mayor_permanencia'
               ])>
              <span class="material-symbols-outlined text-[14px]">history</span>
              Mayor permanencia
            </a>

            @if ($filtro === 'prolongadas')
              <a href="{{ route('conserje.encomiendas.index', ['orden' => $orden]) }}"
                 class="ml-1 px-2 py-1 bg-rose-50 text-rose-800 border border-rose-200 rounded-md font-semibold hover:bg-rose-100 inline-flex items-center gap-1">
                <span>Filtro: &gt; 3 días</span>
                <span class="material-symbols-outlined text-[13px]">close</span>
              </a>
            @else
              <a href="{{ route('conserje.encomiendas.index', ['orden' => $orden, 'filtro' => 'prolongadas']) }}"
                 class="ml-1 px-2 py-1 bg-gray-50 text-gray-700 border border-gray-200 rounded-md hover:bg-rose-50 hover:text-rose-800 hover:border-rose-200 transition-colors">
                Solo prolongadas (&gt; 3d)
              </a>
            @endif
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <caption class="sr-only">Listado de encomiendas recibidas, tiempo de permanencia y control de retiro</caption>
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
              <tr>
                <th scope="col" class="p-4 font-semibold">Depto.</th>
                <th scope="col" class="p-4 font-semibold">Delivery</th>
                <th scope="col" class="p-4 font-semibold">Recepción</th>
                <th scope="col" class="p-4 font-semibold">Permanencia</th>
                <th scope="col" class="p-4 font-semibold">Estado</th>
                <th scope="col" class="p-4 font-semibold">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @forelse ($encomiendas as $encomienda)
                <tr class="hover:bg-gray-50/60 transition-colors">
                  <th scope="row" class="p-4 font-normal whitespace-nowrap">
                    <span class="font-bold text-gray-900">Depto. {{ $encomienda->departamento?->numero ?? 'N/A' }}</span>
                    @if ($encomienda->codigo_seguimiento)
                      <div class="text-[11px] text-gray-500 font-mono mt-0.5">{{ $encomienda->codigo_seguimiento }}</div>
                    @endif
                  </th>
                  <td class="p-4 whitespace-nowrap">
                    <span class="font-semibold text-gray-800">{{ $encomienda->empresa_delivery }}</span>
                    @if ($encomienda->descripcion)
                      <div class="text-xs text-gray-500 truncate max-w-xs">{{ $encomienda->descripcion }}</div>
                    @endif
                  </td>
                  <td class="p-4 whitespace-nowrap text-gray-600 text-xs">
                    <div class="font-medium text-gray-800">{{ $encomienda->fecha_ingreso?->format('d/m/Y') }}</div>
                    <div class="text-gray-500">{{ $encomienda->fecha_ingreso?->format('H:i') }} hrs</div>
                  </td>
                  {{-- Tiempo de Permanencia Calculado (HU-28) --}}
                  <td class="p-4 whitespace-nowrap">
                    @if ($encomienda->estado !== 'Retirado')
                      @if ($encomienda->tienePermanenciaProlongada(3))
                        <div class="flex flex-col gap-0.5">
                          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300 w-max">
                            <span class="material-symbols-outlined text-[13px]">warning</span>
                            Prolongado ({{ $encomienda->diasPermanencia() }} días)
                          </span>
                          <span class="text-[11px] text-rose-700 font-semibold">{{ $encomienda->tiempoTranscurrido() }} en custodia</span>
                        </div>
                      @else
                        <div class="flex flex-col">
                          <span class="text-xs font-semibold text-gray-800">{{ $encomienda->tiempoTranscurrido() }}</span>
                          <span class="text-[11px] text-gray-400">en conserjería</span>
                        </div>
                      @endif
                    @else
                      <div class="text-xs text-gray-400">
                        <span>Permaneció {{ $encomienda->tiempoTranscurrido() }}</span>
                      </div>
                    @endif
                  </td>
                  <td class="p-4 whitespace-nowrap">
                    @if ($encomienda->estado === 'Retirado')
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                        Retirado
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        {{ $encomienda->estado }}
                      </span>
                    @endif
                  </td>
                  <td class="p-4 whitespace-nowrap">
                    @if ($encomienda->estado === 'Retirado')
                      <div class="text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                          <span class="material-symbols-outlined text-[15px]">check_circle</span>
                          Entregado
                        </span>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                          {{ $encomienda->fecha_retiro?->format('d/m/Y H:i') }} hrs
                        </div>
                      </div>
                    @else
                      {{-- Formulario para registrar el retiro de la encomienda (HU-27) --}}
                      <form action="{{ route('conserje.encomiendas.retiro', $encomienda) }}" method="POST"
                        data-swal-confirm="¿Registrar retiro de encomienda?"
                        data-swal-text="Se cambiará el estado a Retirado y se almacenará la fecha y hora del retiro en la bitácora."
                        data-swal-button="Sí, registrar retiro"
                        data-swal-danger="false">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                          aria-label="Registrar retiro de encomienda para Depto. {{ $encomienda->departamento?->numero }}"
                          class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer shadow-sm">
                          <span class="material-symbols-outlined text-[16px]">done_all</span>
                          <span>Registrar retiro</span>
                        </button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="p-8 text-center text-gray-500">
                    <span class="material-symbols-outlined text-4xl text-gray-300 block mb-2">inbox</span>
                    No se encontraron encomiendas con los criterios seleccionados.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($encomiendas->hasPages())
          <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $encomiendas->links() }}
          </div>
        @endif
      </section>
    </div>
</div>
@endsection
