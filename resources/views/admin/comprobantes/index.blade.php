<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Revisión de Comprobantes de Pago - EdiConnect</title>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 antialiased min-h-screen flex flex-col">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-3xl">receipt_long</span>
        <span class="font-bold text-xl tracking-tight">EdiConnect</span>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Gestión de Pagos</span>
      </div>
      <div class="flex items-center gap-4">
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold text-blue-200 hover:text-white flex items-center gap-1 transition-colors">
          <span class="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Volver al panel</span>
        </a>
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
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Revisión de Comprobantes de Pago</h1>
        <p class="text-sm text-gray-500">Valida los comprobantes de transferencia bancaria enviados por los residentes para sus gastos comunes.</p>
      </div>
      <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-medium">
        <span class="material-symbols-outlined text-[18px]">verified</span>
        Control Financiero y Conciliación
      </div>
    </div>

    @if (session('exito') || session('estado'))
      <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600 text-[22px]">check_circle</span>
        <span class="font-medium">{{ session('exito') ?? session('estado') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 p-4 text-sm shadow-sm">
        <div class="flex items-center gap-2 font-semibold mb-1 text-rose-700">
          <span class="material-symbols-outlined text-[20px]">error</span>
          <span>Ocurrió un error al procesar la solicitud:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-1 text-rose-800">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Métricas superiores --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="text-xs font-semibold text-gray-500 uppercase">Total Comprobantes</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $totales['total'] }}</div>
        <div class="text-[11px] text-gray-500 mt-0.5">Histórico general registrado</div>
      </div>
      <div class="bg-white p-5 rounded-xl border border-blue-200 shadow-sm bg-blue-50/40">
        <div class="text-xs font-semibold text-blue-700 uppercase flex items-center justify-between">
          <span>En Revisión</span>
          <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
        </div>
        <div class="text-2xl font-bold text-[#1e3a8a] mt-1">{{ $totales['en_revision'] }}</div>
        <div class="text-[11px] text-blue-600 mt-0.5">Pendientes de verificación</div>
      </div>
      <div class="bg-white p-5 rounded-xl border border-emerald-200 shadow-sm bg-emerald-50/40">
        <div class="text-xs font-semibold text-emerald-700 uppercase">Aprobados</div>
        <div class="text-2xl font-bold text-emerald-800 mt-1">{{ $totales['aprobados'] }}</div>
        <div class="text-[11px] text-emerald-600 mt-0.5">Pagos confirmados</div>
      </div>
      <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm bg-rose-50/40">
        <div class="text-xs font-semibold text-rose-700 uppercase">Rechazados</div>
        <div class="text-2xl font-bold text-rose-800 mt-1">{{ $totales['rechazados'] }}</div>
        <div class="text-[11px] text-rose-600 mt-0.5">Con observaciones</div>
      </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <form action="{{ route('admin.comprobantes.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
        <div>
          <label for="filtroEstado" class="sr-only">Estado</label>
          <select id="filtroEstado" name="estado" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-blue-600 font-medium text-gray-700">
            <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>Todos los estados</option>
            <option value="en_revision" {{ $estadoFiltro === 'en_revision' ? 'selected' : '' }}>En revisión ({{ $totales['en_revision'] }})</option>
            <option value="aprobado" {{ $estadoFiltro === 'aprobado' ? 'selected' : '' }}>Aprobados ({{ $totales['aprobados'] }})</option>
            <option value="rechazado" {{ $estadoFiltro === 'rechazado' ? 'selected' : '' }}>Rechazados ({{ $totales['rechazados'] }})</option>
          </select>
        </div>

        <div>
          <label for="filtroDepto" class="sr-only">Departamento</label>
          <select id="filtroDepto" name="departamento_id" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-blue-600 font-medium text-gray-700">
            <option value="">Todos los departamentos</option>
            @foreach ($departamentos as $depto)
              <option value="{{ $depto->id }}" {{ $departamentoFiltro == $depto->id ? 'selected' : '' }}>
                Depto {{ $depto->numero }}
              </option>
            @endforeach
          </select>
        </div>

        @if ($estadoFiltro !== 'todos' || $departamentoFiltro)
          <a href="{{ route('admin.comprobantes.index') }}" class="text-xs text-blue-700 hover:underline font-semibold flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">close</span>
            Limpiar filtros
          </a>
        @endif
      </form>
    </div>

    {{-- Tabla de comprobantes --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-700 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Residente</th>
              <th class="px-6 py-3">Depto</th>
              <th class="px-6 py-3">Gasto Asociado</th>
              <th class="px-6 py-3">Fecha y Monto</th>
              <th class="px-6 py-3">Comprobante</th>
              <th class="px-6 py-3">Estado</th>
              <th class="px-6 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            @forelse ($comprobantes as $comp)
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-semibold text-gray-900">{{ $comp->residente?->nombre ?? 'Usuario eliminado' }}</div>
                  <div class="text-xs text-gray-500">{{ $comp->residente?->email }}</div>
                  @if ($comp->residente?->rut)
                    <div class="text-[11px] text-gray-400">RUT: {{ $comp->residente->rut }}</div>
                  @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-800">
                    Depto {{ $comp->departamento?->numero }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-semibold text-gray-900">Período {{ $comp->gastoComun?->periodo ?? 'S/I' }}</div>
                  <div class="text-xs text-gray-500">Cobro: {{ $comp->gastoComun ? $comp->gastoComun->montoFormateado() : 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-bold text-[#1e3a8a]">{{ $comp->montoFormateado() }}</div>
                  <div class="text-xs text-gray-500">
                    {{ $comp->fecha_pago ? $comp->fecha_pago->format('d/m/Y') : $comp->created_at->format('d/m/Y') }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a href="{{ route('admin.comprobantes.archivo', $comp) }}" target="_blank"
                     class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-xs font-semibold text-[#1e3a8a] hover:bg-blue-100 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    <span>Ver comprobante</span>
                  </a>
                  <div class="text-[11px] text-gray-400 mt-1">{{ $comp->tamanoLegible() }} · {{ strtoupper(pathinfo($comp->archivo_nombre_original, PATHINFO_EXTENSION)) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  @if ($comp->estado === 'aprobado')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <span class="material-symbols-outlined text-[14px]">check_circle</span>
                      Aprobado
                    </span>
                  @elseif ($comp->estado === 'rechazado')
                    <div>
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                        <span class="material-symbols-outlined text-[14px]">cancel</span>
                        Rechazado
                      </span>
                      @if ($comp->motivo_rechazo)
                        <div class="text-[11px] text-rose-700 mt-1 max-w-xs truncate" title="{{ $comp->motivo_rechazo }}">
                          {{ $comp->motivo_rechazo }}
                        </div>
                      @endif
                    </div>
                  @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                      En revisión
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  @if ($comp->estado === 'en_revision')
                    <div class="inline-flex items-center gap-2">
                      <form action="{{ route('admin.comprobantes.aprobar', $comp) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1 shadow-sm transition-colors">
                          <span class="material-symbols-outlined text-[16px]">check</span>
                          <span>Aprobar</span>
                        </button>
                      </form>

                      <button type="button" onclick="mostrarModalRechazo({{ $comp->id }}, '{{ $comp->residente?->nombre }}', '{{ $comp->departamento?->numero }}')"
                              class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1 shadow-sm transition-colors">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                        <span>Rechazar</span>
                      </button>
                    </div>
                  @else
                    <div class="text-[11px] text-gray-500">
                      Revisado por {{ $comp->revisor?->nombre ?? 'Admin' }}<br/>
                      {{ $comp->revisado_at ? $comp->revisado_at->format('d/m/Y H:i') : '' }}
                    </div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-4xl text-gray-400">inbox</span>
                    <p class="font-medium text-gray-700">No se encontraron comprobantes con los filtros seleccionados.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($comprobantes->hasPages())
        <div class="p-4 border-t border-gray-200 bg-gray-50">
          {{ $comprobantes->links() }}
        </div>
      @endif
    </div>
  </main>

  {{-- Modal para Rechazo de Comprobante --}}
  <div id="modalRechazo" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
        <div class="flex items-center gap-2 text-rose-700 font-bold text-base">
          <span class="material-symbols-outlined">report</span>
          <span>Rechazar Comprobante de Pago</span>
        </div>
        <button type="button" onclick="cerrarModalRechazo()" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form id="formRechazo" method="POST">
        @csrf
        @method('PATCH')

        <p class="text-xs text-gray-600 mb-3">
          Estás por rechazar el comprobante del residente <strong id="nombreResidenteRechazo"></strong> (Depto <strong id="deptoRechazo"></strong>).
          El gasto común se mantendrá en estado <strong>Pendiente</strong> y el residente podrá ver el motivo indicado.
        </p>

        <div class="mb-4">
          <label class="block font-semibold text-xs text-gray-800 mb-1" for="motivoRechazoInput">
            Motivo u observación del rechazo <span class="text-rose-600">*</span>
          </label>
          <textarea id="motivoRechazoInput" name="motivo_rechazo" rows="3" required
                    placeholder="Ej: Monto transferido no coincide con el total o número de operación ilegible."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
          <button type="button" onclick="cerrarModalRechazo()" class="px-3.5 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
            Cancelar
          </button>
          <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">cancel</span>
            <span>Confirmar Rechazo</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>

  <script>
    function mostrarModalRechazo(comprobanteId, nombre, depto) {
      const modal = document.getElementById('modalRechazo');
      const form = document.getElementById('formRechazo');
      const nombreElem = document.getElementById('nombreResidenteRechazo');
      const deptoElem = document.getElementById('deptoRechazo');

      form.action = `/admin/comprobantes/${comprobanteId}/rechazar`;
      nombreElem.textContent = nombre;
      deptoElem.textContent = depto;

      modal.classList.remove('hidden');
    }

    function cerrarModalRechazo() {
      document.getElementById('modalRechazo').classList.add('hidden');
    }
  </script>
</body>
</html>
