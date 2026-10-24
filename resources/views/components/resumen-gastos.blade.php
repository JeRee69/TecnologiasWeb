<section aria-label="Estado general de gastos comunes" class="bg-white border border-gray-200 rounded-xl p-5 mb-6">
  <h2 class="font-bold mb-3">Estado de tus gastos comunes</h2>
  @include('components.estado-gasto', ['estadoGasto' => $resumenGastos['estado']])
  <p class="text-sm text-gray-600 mt-3">{{ $resumenGastos['pagados'] }} al día · {{ $resumenGastos['pendientes'] }} pendientes · {{ $resumenGastos['morosos'] }} morosos</p>
  <p class="text-xs text-gray-500 mt-2">El resumen incluye todos los cobros de tus departamentos. Un cobro impago pasa a moroso al día siguiente de su vencimiento. Un pago queda al día cuando la administración lo valida.</p>
</section>
