<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Mis Gastos Comunes - EdiConnect</title>
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
        <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
        <span class="font-bold text-xl tracking-tight">EdiConnect</span>
        <span class="text-xs bg-blue-900 text-blue-200 px-2 py-0.5 rounded-full font-medium ml-2">Gastos Comunes</span>
      </div>
      <div class="flex items-center gap-4">
        <a href="{{ route('residente.dashboard') }}" class="text-xs font-semibold text-blue-200 hover:text-white flex items-center gap-1 transition-colors">
          <span class="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Volver al portal</span>
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
        <h1 class="text-2xl font-bold text-gray-900">Gastos Comunes y Comprobantes</h1>
        <p class="text-sm text-gray-500">Revisa el estado de cobro mensual de tu departamento y carga tus comprobantes de transferencia.</p>
      </div>
      <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 px-3 py-1.5 rounded-lg border border-blue-200 text-xs font-medium">
        <span class="material-symbols-outlined text-[18px]">verified_user</span>
        Pagos auditados por la administración
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
          <span>No pudimos procesar la carga del comprobante:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-1 text-rose-800">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if ($comprobantesRechazados->isNotEmpty())
      <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
        <div class="flex items-center gap-2 font-semibold text-amber-800 mb-2">
          <span class="material-symbols-outlined text-amber-600 text-[22px]">warning</span>
          <span>Comprobantes con observaciones o rechazados</span>
        </div>
        <p class="text-xs text-amber-800 mb-3">La administración revisó tus transferencias y detectó observaciones. Por favor revisa el motivo y sube un comprobante actualizado:</p>
        <div class="space-y-2">
          @foreach ($comprobantesRechazados as $rechazado)
            <div class="bg-white rounded-lg p-3 border border-amber-200 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <span class="font-bold text-gray-900">Período {{ $rechazado->gastoComun?->periodo ?? 'General' }}:</span>
                <span class="text-rose-700 font-medium">Motivo: {{ $rechazado->motivo_rechazo ?? 'Información no coincide' }}</span>
              </div>
              <span class="text-[11px] text-gray-500">Revisado el {{ $rechazado->updated_at->format('d/m/Y H:i') }}</span>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-600">receipt_long</span>
          Estado de Cobro de Gastos Comunes
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-700 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Período</th>
              <th class="px-6 py-3">Depto</th>
              <th class="px-6 py-3">Monto Total</th>
              <th class="px-6 py-3">Vencimiento</th>
              <th class="px-6 py-3">Estado</th>
              <th class="px-6 py-3">Comprobante</th>
              <th class="px-6 py-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            @forelse ($gastos as $gasto)
              @php
                $ultimoComprobante = $gasto->ultimoComprobante;
              @endphp
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">
                  {{ $gasto->periodo }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="font-medium text-gray-800">Depto {{ $gasto->departamento?->numero }}</span>
                </td>
                <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">
                  {{ $gasto->montoFormateado() }}
                </td>
                <td class="px-6 py-4 text-xs text-gray-600 whitespace-nowrap">
                  {{ $gasto->fecha_vencimiento ? $gasto->fecha_vencimiento->format('d/m/Y') : 'Sin fecha' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  @if ($gasto->estado === 'Pagado')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                      Al día / Pagado
                    </span>
                  @elseif ($gasto->estado === 'En revisión')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                      En revisión
                    </span>
                  @elseif ($gasto->estado === 'Moroso')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                      Moroso
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                      Pendiente
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs">
                  @if ($ultimoComprobante)
                    <div class="flex items-center gap-2">
                      <a href="{{ route('residente.comprobantes.archivo', $ultimoComprobante) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-900 font-medium hover:underline">
                        <span class="material-symbols-outlined text-[16px]">attach_file</span>
                        <span>Ver archivo</span>
                      </a>
                      <span class="text-gray-400">({{ $ultimoComprobante->estadoLegible() }})</span>
                    </div>
                  @else
                    <span class="text-gray-400 italic">Sin comprobante</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  @if ($gasto->permiteCargarComprobante())
                    <button type="button" onclick="abrirModalCarga({{ $gasto->id }}, '{{ $gasto->periodo }}', '{{ $gasto->montoFormateado() }}')" class="inline-flex items-center gap-1.5 bg-[#1e3a8a] hover:bg-blue-800 text-white text-xs font-semibold px-3 py-2 rounded-lg shadow-sm transition-colors">
                      <span class="material-symbols-outlined text-[16px]">upload_file</span>
                      <span>Cargar Comprobante</span>
                    </button>
                  @else
                    <span class="text-xs text-emerald-700 font-semibold inline-flex items-center gap-1">
                      <span class="material-symbols-outlined text-[16px]">check_circle</span>
                      Validado
                    </span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-4xl text-gray-400">check_box</span>
                    <p class="font-medium text-gray-700">No se registran cobros de gastos comunes para tu departamento.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </main>

  {{-- Modal para Carga de Comprobante --}}
  <div id="modalCarga" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100">
      <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
        <div class="flex items-center gap-2 text-gray-900 font-bold text-lg">
          <span class="material-symbols-outlined text-blue-700">upload_file</span>
          <span>Cargar Comprobante de Transferencia</span>
        </div>
        <button type="button" onclick="cerrarModalCarga()" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form id="formCargaComprobante" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="space-y-4 text-sm text-gray-700">
          <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3.5 flex items-center justify-between">
            <div>
              <div class="text-xs text-blue-700 uppercase font-semibold">Período a Pagar</div>
              <div id="modalPeriodo" class="text-base font-bold text-gray-900">Octubre 2026</div>
            </div>
            <div class="text-right">
              <div class="text-xs text-blue-700 uppercase font-semibold">Monto Sugerido</div>
              <div id="modalMonto" class="text-base font-bold text-[#1e3a8a]">$ 68.500</div>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-800 mb-1" for="inputComprobante">
              Archivo del Comprobante <span class="text-rose-600">*</span>
            </label>
            <input type="file" id="inputComprobante" name="comprobante" required accept=".pdf,.jpg,.jpeg,.png"
                   class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2.5 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-[#1e3a8a] hover:file:bg-blue-200"/>
            <p class="mt-1 text-[11px] text-gray-500">
              Formatos aceptados: <strong>PDF, JPG, JPEG, PNG</strong>. Tamaño máximo permitido: <strong>5 MB</strong>.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-800 mb-1" for="inputMonto">Monto Transferido ($)</label>
              <input type="number" id="inputMonto" name="monto" min="1" placeholder="Ej: 68500"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"/>
            </div>
            <div>
              <label class="block font-semibold text-gray-800 mb-1" for="inputFecha">Fecha de Transferencia</label>
              <input type="date" id="inputFecha" name="fecha_pago" value="{{ date('Y-m-d') }}"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"/>
            </div>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <button type="button" onclick="cerrarModalCarga()" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
            Cancelar
          </button>
          <button type="submit" class="px-5 py-2 bg-[#1e3a8a] hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">send</span>
            <span>Enviar Comprobante</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
    © 2026 EdiConnect - Plataforma de Gestión de Comunidades Residenciales
  </footer>

  <script>
    function abrirModalCarga(gastoId, periodo, monto) {
      const modal = document.getElementById('modalCarga');
      const form = document.getElementById('formCargaComprobante');
      const periodoElem = document.getElementById('modalPeriodo');
      const montoElem = document.getElementById('modalMonto');

      form.action = `/residente/gastos-comunes/${gastoId}/comprobante`;
      periodoElem.textContent = periodo;
      montoElem.textContent = monto;

      modal.classList.remove('hidden');
    }

    function cerrarModalCarga() {
      document.getElementById('modalCarga').classList.add('hidden');
    }
  </script>
</body>
</html>
