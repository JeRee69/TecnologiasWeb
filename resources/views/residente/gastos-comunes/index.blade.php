@extends('layouts.app')
@section('title', 'Mis gastos comunes')
@section('content')
<div class="legacy-page">
<a href="{{ route('residente.dashboard') }}" class="text-sm font-semibold text-blue-900 hover:underline">← Volver al portal</a>
    <h1 class="text-2xl font-bold mt-4">Mis gastos comunes</h1>
    <p class="text-sm text-gray-600 mt-1 mb-6">Consulta los cobros de tus departamentos, los conceptos que componen cada monto y carga tus comprobantes de transferencia.</p>

    @if (session('estado') || session('exito'))
      <div data-swal-feedback role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600 text-[22px]" aria-hidden="true">check_circle</span>
        <span class="font-medium">{{ session('estado') ?? session('exito') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 p-4 text-sm shadow-sm">
        <div class="flex items-center gap-2 font-semibold mb-1 text-rose-700">
          <span class="material-symbols-outlined text-[20px]" aria-hidden="true">error</span>
          <span>No pudimos procesar la carga del comprobante:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-1 text-rose-800">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (isset($comprobantesRechazados) && $comprobantesRechazados->isNotEmpty())
      <section class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm" aria-label="Comprobantes rechazados">
        <div class="flex items-center gap-2 font-semibold text-amber-800 mb-2">
          <span class="material-symbols-outlined text-amber-600 text-[22px]" aria-hidden="true">warning</span>
          <span>Comprobantes con observaciones o rechazados</span>
        </div>
        <p class="text-xs text-amber-800 mb-3">La administración revisó tus transferencias y detectó observaciones. Revisa el motivo informado y sube un nuevo comprobante:</p>
        <div class="space-y-2">
          @foreach ($comprobantesRechazados as $rechazado)
            <div class="bg-white rounded-lg p-3 border border-amber-200 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <span class="font-bold text-gray-900">Período {{ $rechazado->gastoComun?->periodo?->format('m/Y') ?? 'General' }}:</span>
                <span class="text-rose-700 font-medium">Motivo: {{ $rechazado->motivo_rechazo ?? 'Información no coincide' }}</span>
              </div>
              <span class="text-[11px] text-gray-500">Revisado el {{ ($rechazado->revisado_at ?? $rechazado->updated_at)->format('d/m/Y H:i') }}</span>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    @include('components.resumen-gastos')
    @if ($departamentos->isEmpty())
      <section class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-900">
        <h2 class="font-bold">Sin departamento asociado</h2>
        <p class="text-sm mt-2">Contacta a la administración para vincular tu departamento y consultar sus gastos comunes.</p>
      </section>
    @else
      <div class="flex flex-wrap gap-2 mb-6" aria-label="Tus departamentos">
        @foreach ($departamentos as $departamento)
          <span class="bg-blue-50 text-blue-900 border border-blue-200 rounded-lg px-3 py-1 text-sm">
            Depto. {{ $departamento->numero }}
            @if (!$departamento->estaActivo())
              · Inactivo
            @endif
          </span>
        @endforeach
      </div>
      <div class="space-y-4">
        @forelse ($gastos as $gasto)
          @php
            $ultimoComprobante = $gasto->ultimoComprobante;
          @endphp
          <article data-gasto="{{ $gasto->id }}" class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm" aria-labelledby="gasto-{{ $gasto->id }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <h2 id="gasto-{{ $gasto->id }}" class="font-bold text-lg">Depto. {{ $gasto->departamento->numero }}</h2>
                <p class="text-sm text-gray-600 mt-1">Período: <time datetime="{{ $gasto->periodo->format('Y-m') }}">{{ $gasto->periodo->format('m/Y') }}</time></p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  @include('components.estado-gasto', ['estadoGasto' => $gasto->estadoVigente()])
                  @if ($ultimoComprobante && $ultimoComprobante->estado === 'en_revision')
                    <span class="inline-flex items-center gap-1.5 border border-blue-200 bg-blue-50 text-blue-900 rounded-full px-3 py-1 text-xs font-semibold">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                      Comprobante: En revisión
                    </span>
                  @elseif ($ultimoComprobante && $ultimoComprobante->estado === 'rechazado' && $gasto->estado !== 'pagado')
                    <span class="inline-flex items-center gap-1.5 border border-rose-200 bg-rose-50 text-rose-900 rounded-full px-3 py-1 text-xs font-semibold">
                      Comprobante rechazado
                    </span>
                  @endif
                </div>
                <p class="text-sm text-gray-600 mt-2">Vencimiento: {{ $gasto->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha definida' }}</p>
                @if ($gasto->pago_validado_at)
                  <p class="text-xs text-gray-500 mt-1">Pago validado el {{ $gasto->pago_validado_at->format('d/m/Y H:i') }}.</p>
                @endif
              </div>
              <div class="text-right"><p class="text-sm text-gray-500">Monto del cobro (CLP)</p><p class="text-2xl font-bold text-blue-900">${{ number_format($gasto->monto, 0, ',', '.') }}</p></div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
              <a href="{{ route('residente.gastos-comunes.pdf', $gasto) }}" class="inline-block px-4 py-2 bg-blue-50 text-blue-900 rounded-lg text-sm font-semibold hover:bg-blue-100" aria-label="Descargar PDF del departamento {{ $gasto->departamento->numero }}, período {{ $gasto->periodo->format('m/Y') }}">Descargar PDF</a>

              @if ($gasto->permiteCargarComprobante())
                <button type="button" onclick="abrirModalCarga({{ $gasto->id }}, '{{ $gasto->periodo->format('m/Y') }}', '${{ number_format($gasto->monto, 0, ',', '.') }}', {{ $gasto->monto }})" class="inline-flex items-center gap-1.5 bg-[#1e3a8a] hover:bg-blue-800 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition-colors">
                  <span class="material-symbols-outlined text-[18px]" aria-hidden="true">upload_file</span>
                  <span>Cargar Comprobante</span>
                </button>
              @endif

              @if ($ultimoComprobante)
                <a href="{{ route('residente.comprobantes.archivo', $ultimoComprobante) }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-900 hover:underline">
                  <span class="material-symbols-outlined text-[18px]" aria-hidden="true">attach_file</span>
                  <span>Ver comprobante ({{ $ultimoComprobante->estadoLegible() }})</span>
                </a>
              @endif
            </div>

            <details class="mt-5 border-t pt-4">
              <summary class="cursor-pointer text-sm font-semibold text-blue-900 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-800">Ver detalle del cobro</summary>
              @if ($gasto->detalles->isEmpty())
                <p class="text-sm text-gray-600 mt-4">Desglose aún no registrado por la administración.</p>
              @else
                <div class="overflow-x-auto mt-4">
                  <table class="w-full text-sm text-left">
                    <caption class="sr-only">Conceptos del cobro del período {{ $gasto->periodo->format('m/Y') }}</caption>
                    <thead class="bg-gray-50 text-gray-600"><tr><th scope="col" class="p-3">Concepto</th><th scope="col" class="p-3 text-right whitespace-nowrap">Monto CLP</th></tr></thead>
                    <tbody class="divide-y">
                      @foreach ($gasto->detalles as $detalle)
                        <tr><td class="p-3 break-words">{{ $detalle->concepto }}</td><td class="p-3 text-right whitespace-nowrap">${{ number_format($detalle->monto, 0, ',', '.') }}</td></tr>
                      @endforeach
                    </tbody>
                    <tfoot class="border-t font-bold"><tr><th scope="row" class="p-3">Total del cobro</th><td class="p-3 text-right whitespace-nowrap">${{ number_format($gasto->monto, 0, ',', '.') }}</td></tr></tfoot>
                  </table>
                </div>
              @endif
            </details>
          </article>
        @empty
          <section class="bg-white border rounded-xl p-8 text-center">
            @if ($gastos->total() > 0)
              <h2 class="font-bold">No hay cobros en esta página</h2>
              <a href="{{ route('residente.gastos-comunes.index') }}" class="inline-block mt-3 text-blue-900 hover:underline">Volver a la primera página</a>
            @else
              <h2 class="font-bold">Sin gastos comunes registrados</h2>
              <p class="text-sm text-gray-600 mt-2">Aún no se han generado cobros para tus departamentos.</p>
            @endif
          </section>
        @endforelse
      </div>
      @if ($gastos->total() > 0)
        <div class="mt-6 text-sm text-gray-600">{{ $gastos->total() }} cobro(s) registrados. {{ $gastos->links() }}</div>
      @endif
    @endif
</div>

{{-- Modal para Carga de Comprobante (HU-16) --}}
<div id="modalCarga" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
  <div role="dialog" aria-modal="true" aria-label="Cargar comprobante de transferencia" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 max-h-[calc(100dvh-2rem)] overflow-y-auto">
    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
      <div class="flex items-center gap-2 text-gray-900 font-bold text-lg">
        <span class="material-symbols-outlined text-blue-700" aria-hidden="true">upload_file</span>
        <span>Cargar Comprobante de Transferencia</span>
      </div>
      <button type="button" onclick="cerrarModalCarga()" class="text-gray-400 hover:text-gray-600 rounded-lg p-1" aria-label="Cerrar modal">
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </div>

    <form id="formCargaComprobante" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="space-y-4 text-sm text-gray-700">
        <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3.5 flex items-center justify-between">
          <div>
            <div class="text-xs text-blue-700 uppercase font-semibold">Período a Pagar</div>
            <div id="modalPeriodo" class="text-base font-bold text-gray-900">10/2026</div>
          </div>
          <div class="text-right">
            <div class="text-xs text-blue-700 uppercase font-semibold">Monto del Cobro</div>
            <div id="modalMonto" class="text-base font-bold text-[#1e3a8a]">$68.500</div>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-gray-800 mb-1" for="inputComprobante">
            Archivo del Comprobante <span class="text-rose-600">*</span>
          </label>
          <input type="file" id="inputComprobante" name="comprobante" required accept=".pdf,.jpg,.jpeg,.png"
                 class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2.5 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-[#1e3a8a] hover:file:bg-blue-200"/>
          <p class="mt-1 text-[11px] text-gray-500">
            Formatos permitidos: <strong>PDF, JPG, JPEG, PNG</strong>. Tamaño máximo: <strong>5 MB</strong>.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-gray-800 mb-1" for="inputMonto">Monto Transferido (CLP)</label>
            <input type="number" id="inputMonto" name="monto" min="1" step="1" placeholder="Ej: 68500"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"/>
          </div>
          <div>
            <label class="block font-semibold text-gray-800 mb-1" for="inputFecha">Fecha de Transferencia</label>
            <input type="date" id="inputFecha" name="fecha_pago" value="{{ today('America/Santiago')->toDateString() }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"/>
          </div>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
        <button type="button" onclick="cerrarModalCarga()" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
          Cancelar
        </button>
        <button type="submit" class="px-5 py-2 bg-[#1e3a8a] hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[16px]" aria-hidden="true">send</span>
          <span>Enviar Comprobante</span>
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  function abrirModalCarga(gastoId, periodo, montoTexto, montoNumero) {
    const modal = document.getElementById('modalCarga');
    const form = document.getElementById('formCargaComprobante');
    const periodoElem = document.getElementById('modalPeriodo');
    const montoElem = document.getElementById('modalMonto');
    const inputMonto = document.getElementById('inputMonto');

    form.action = `/residente/gastos-comunes/${gastoId}/comprobante`;
    periodoElem.textContent = periodo;
    montoElem.textContent = montoTexto;
    if (inputMonto) inputMonto.value = montoNumero || '';

    modal.classList.remove('hidden');
  }

  function cerrarModalCarga() {
    document.getElementById('modalCarga').classList.add('hidden');
  }
</script>
@endpush
@endsection
