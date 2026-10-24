<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Estado y pago del gasto común - EdiConnect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 min-h-screen">
  <header class="bg-[#1e3a8a] text-white p-4"><div class="max-w-4xl mx-auto flex flex-wrap justify-between gap-3"><a href="{{ route('admin.dashboard') }}" class="font-bold text-xl">EdiConnect · Administración</a><span>{{ auth()->user()->nombre }}</span></div></header>
  <main class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('admin.gastos-comunes.index', ['periodo' => $gasto->periodo->format('Y-m')]) }}" class="text-sm text-blue-900 hover:underline">← Volver a gastos comunes</a>
    <h1 class="text-2xl font-bold mt-4 mb-6">Estado y pago del gasto común</h1>
    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-900 p-4 rounded-xl">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-5 bg-red-50 border border-red-200 text-red-900 p-4 rounded-xl"><ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section class="bg-white border rounded-xl p-6 mb-6 space-y-2" aria-label="Datos del cobro">
      <h2 class="font-bold text-lg">Depto. {{ $gasto->departamento->numero }} · {{ $gasto->periodo->format('m/Y') }}</h2>
      <p class="text-2xl font-bold text-blue-900">${{ number_format($gasto->monto, 0, ',', '.') }} CLP</p>
      @include('components.estado-gasto', ['estadoGasto' => $gasto->estadoVigente()])
      <p class="text-sm">Vencimiento: {{ $gasto->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha definida' }}</p>
    </section>
    @if ($gasto->estado === 'pagado')
      <section class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
        <h2 class="font-bold">Cobro pagado</h2>
        @if ($gasto->pago_validado_at)
          <p class="mt-2 text-sm">Validado el {{ $gasto->pago_validado_at->format('d/m/Y H:i') }} · Responsable #{{ $gasto->pago_validado_por ?? 'Cuenta eliminada' }}</p>
          <p class="mt-2 text-sm break-words">Referencia: {{ $gasto->referencia_pago }}</p>
        @else
          <p class="mt-2 text-sm">Registro previo sin datos de validación. Se conserva su estado pagado.</p>
        @endif
      </section>
    @else
      <div class="grid md:grid-cols-2 gap-6">
        <section class="bg-white border rounded-xl p-6">
          <h2 class="text-lg font-bold mb-3">Configurar vencimiento</h2>
          <p class="text-sm text-gray-600 mb-4">El cobro impago será moroso desde el día siguiente. Sin fecha permanece pendiente.</p>
          <form action="{{ route('admin.gastos-comunes.vencimiento', $gasto) }}" method="POST" class="space-y-3">
            @csrf
            @method('PATCH')
            <label for="fecha_vencimiento" class="block text-sm font-semibold">Fecha de vencimiento (opcional)</label>
            <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" min="1900-01-01" max="9999-12-31" value="{{ is_scalar(old('fecha_vencimiento', $gasto->fecha_vencimiento?->format('Y-m-d'))) ? old('fecha_vencimiento', $gasto->fecha_vencimiento?->format('Y-m-d')) : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
            <button type="submit" class="bg-blue-900 text-white rounded-lg px-4 py-2">Guardar vencimiento</button>
          </form>
        </section>
        <section class="bg-white border rounded-xl p-6">
          <h2 class="text-lg font-bold mb-3">Validar pago recibido</h2>
          <p class="text-sm text-gray-600 mb-4">Confirma esta acción después de verificar el pago completo. Se registrará tu cuenta, la fecha y la referencia.</p>
          <form action="{{ route('admin.gastos-comunes.pago.validar', $gasto) }}" method="POST" class="space-y-3" data-swal-confirm="¿Validar el pago de este cobro?" data-swal-text="Confirma que verificaste el pago completo. El cobro quedará al día." data-swal-button="Sí, validar pago">
            @csrf
            <label for="monto_recibido" class="block text-sm font-semibold">Monto recibido (CLP)</label>
            <input id="monto_recibido" name="monto_recibido" type="number" min="1" max="999999999999" step="1" required value="{{ is_scalar(old('monto_recibido')) ? old('monto_recibido') : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
            <label for="referencia_pago" class="block text-sm font-semibold">Referencia del pago verificado</label>
            <input id="referencia_pago" name="referencia_pago" type="text" maxlength="200" required value="{{ is_string(old('referencia_pago')) ? old('referencia_pago') : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
            <button type="submit" class="bg-emerald-800 text-white rounded-lg px-4 py-2">Validar pago</button>
          </form>
        </section>
      </div>
    @endif
  </main>
  @include('components.alertas')
</body>
</html>
