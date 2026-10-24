<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <title>Gasto común #{{ $gasto->id }}</title>
  <style>
    @page { margin: 42pt 42pt 55pt; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }
    h1 { font-size: 23pt; color: #1e3a8a; margin: 4pt 0; }
    h2 { font-size: 13pt; margin: 22pt 0 10pt; }
    .marca { color: #1e3a8a; font-size: 14pt; font-weight: bold; }
    .subtitulo, .nota { color: #64748b; font-size: 9pt; }
    .cabecera { border-bottom: 2pt solid #1e3a8a; padding-bottom: 16pt; margin-bottom: 18pt; }
    .datos { width: 100%; border-collapse: collapse; table-layout: fixed; background: #f3f6fb; }
    .datos td { padding: 10pt; vertical-align: top; overflow-wrap: break-word; }
    .etiqueta { font-size: 8pt; color: #64748b; }
    .valor { font-weight: bold; }
    .estado { display: inline-block; padding: 4pt 9pt; font-weight: bold; border: 1pt solid; margin-top: 12pt; }
    .pagado { color: #065f46; background: #ecfdf5; border-color: #a7f3d0; }
    .pendiente { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .moroso { color: #991b1b; background: #fef2f2; border-color: #fecaca; }
    .detalle { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .detalle thead { display: table-header-group; }
    .detalle th { padding: 9pt; background: #1e3a8a; color: white; text-align: left; font-size: 9pt; }
    .detalle td { padding: 6pt 9pt; border-bottom: 1pt solid #e5e7eb; vertical-align: top; overflow-wrap: break-word; }
    .detalle tr { page-break-inside: avoid; }
    .detalle .importe { width: 25%; text-align: right; white-space: nowrap; }
    .total { text-align: right; font-size: 15pt; font-weight: bold; color: #1e3a8a; margin-top: 15pt; page-break-inside: avoid; }
    .aviso { background: #fffbeb; border: 1pt solid #fde68a; padding: 12pt; }
  </style>
</head>
<body>
  <div class="cabecera">
    <div class="marca">EdiConnect</div>
    <h1>Detalle de gasto común</h1>
    <div class="subtitulo">Cobro #{{ $gasto->id }} | Emitido el {{ $emitido->format('d/m/Y H:i') }} (America/Santiago)</div>
  </div>
  <table class="datos">
    <tr>
      <td><div class="etiqueta">DEPARTAMENTO</div><div class="valor">{{ $gasto->departamento->numero }}</div></td>
      <td><div class="etiqueta">PERÍODO</div><div class="valor">{{ $gasto->periodo->format('m/Y') }}</div></td>
      <td><div class="etiqueta">VENCIMIENTO</div><div class="valor">{{ $gasto->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha definida' }}</div></td>
    </tr>
  </table>
  <div class="estado {{ $estado }}">Estado: {{ match ($estado) { 'pagado' => 'Al día (Pagado)', 'moroso' => 'Moroso', default => 'Pendiente' } }}</div>
  @if ($gasto->pago_validado_at)
    <p class="nota">Pago validado el {{ $gasto->pago_validado_at->format('d/m/Y H:i') }}.</p>
  @endif
  <h2>Desglose del cobro</h2>
  @if ($gasto->detalles->isEmpty())
    <div class="aviso">Desglose aún no registrado por la administración.</div>
  @else
    <table class="detalle">
      <thead><tr><th>Concepto</th><th class="importe">Monto (CLP)</th></tr></thead>
      <tbody>
        @foreach ($gasto->detalles as $detalle)
          <tr><td>{{ $detalle->concepto }}</td><td class="importe">${{ number_format($detalle->monto, 0, ',', '.') }}</td></tr>
        @endforeach
      </tbody>
    </table>
  @endif
  <div class="total">Monto total: ${{ number_format($gasto->monto, 0, ',', '.') }} CLP</div>
</body>
</html>
