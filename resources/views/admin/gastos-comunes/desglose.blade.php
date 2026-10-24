@extends('layouts.app')
@section('title', 'Desglose de gasto común')
@section('content')
<div class="legacy-page">
<a href="{{ route('admin.gastos-comunes.index', ['periodo' => $gasto->periodo->format('Y-m')]) }}" class="text-sm font-semibold text-blue-900 hover:underline">← Volver a gastos comunes</a>
    <h1 class="text-2xl font-bold mt-4">Desglose del gasto común</h1>
    <p class="text-sm text-gray-600 mt-1 mb-6">Detalla los conceptos que componen el cobro. La suma debe coincidir exactamente con el monto asignado.</p>
    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-900">
        <p class="font-semibold">Revisa el desglose.</p>
        <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @endif
    <section aria-label="Datos del cobro" class="bg-white border rounded-xl p-5 shadow-sm mb-6 grid sm:grid-cols-3 gap-4">
      <div><p class="text-sm text-gray-500">Departamento</p><p class="font-bold">{{ $gasto->departamento->numero }}</p></div>
      <div><p class="text-sm text-gray-500">Período</p><p class="font-bold">{{ $gasto->periodo->format('m/Y') }}</p></div>
      <div><p class="text-sm text-gray-500">Monto asignado (CLP)</p><p class="text-xl font-bold text-blue-900">${{ number_format($gasto->monto, 0, ',', '.') }}</p></div>
    </section>
    @if ($gasto->detalles->isEmpty())
      <p class="mb-4 text-sm text-amber-900 bg-amber-50 border border-amber-200 p-4 rounded-xl">Este cobro aún no tiene desglose registrado. Completa los conceptos para explicar su composición.</p>
    @endif
    @php
      $guardados = $gasto->detalles->map(fn ($detalle) => ['concepto' => $detalle->concepto, 'monto' => $detalle->monto])->all();
      $filas = old('detalles', $guardados ?: [
        ['concepto' => 'Fondo de reserva', 'monto' => ''],
        ['concepto' => 'Mantenciones', 'monto' => ''],
        ['concepto' => 'Consumos básicos', 'monto' => ''],
      ]);
      $filas = is_array($filas) ? array_slice(array_values($filas), 0, 50) : [];
      if (!$filas) $filas = [['concepto' => '', 'monto' => '']];
    @endphp
    <form id="desglose-form" action="{{ route('admin.gastos-comunes.desglose.guardar', $gasto) }}" method="POST" class="bg-white border rounded-xl p-5 sm:p-6 shadow-sm" data-monto="{{ $gasto->monto }}">
      @csrf
      @method('PUT')
      <h2 class="text-lg font-bold">Conceptos del cobro</h2>
      <p class="text-sm text-gray-600 mt-1 mb-5">Hasta 50 conceptos, con montos positivos en pesos enteros. Quita las filas que no correspondan.</p>
      <datalist id="conceptos-sugeridos"><option value="Fondo de reserva"></option><option value="Mantenciones"></option><option value="Consumos básicos"></option><option value="Aseo"></option><option value="Seguridad"></option></datalist>
      <div id="detalle-filas" class="space-y-4">
        @foreach ($filas as $i => $fila)
          <div data-detalle class="grid sm:grid-cols-[1fr_12rem_auto] items-start gap-3 border-b pb-4">
            <div>
              <label data-label="concepto" for="concepto-{{ $i }}" class="block text-sm font-semibold mb-1">Concepto {{ $i + 1 }}</label>
              <input data-campo="concepto" id="concepto-{{ $i }}" name="detalles[{{ $i }}][concepto]" list="conceptos-sugeridos" type="text" required maxlength="200" value="{{ is_array($fila) && is_string($fila['concepto'] ?? null) ? $fila['concepto'] : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
              @error('detalles.'.$i.'.concepto')<p data-error class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
              <label data-label="monto" for="monto-{{ $i }}" class="block text-sm font-semibold mb-1">Monto (CLP)</label>
              <input data-campo="monto" id="monto-{{ $i }}" name="detalles[{{ $i }}][monto]" type="number" required min="1" max="999999999999" step="1" value="{{ is_array($fila) && is_scalar($fila['monto'] ?? null) ? $fila['monto'] : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
              @error('detalles.'.$i.'.monto')<p data-error class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
            </div>
            <button data-quitar type="button" hidden class="sm:mt-7 px-3 py-2 text-red-700 rounded-lg hover:bg-red-50">Quitar</button>
          </div>
        @endforeach
      </div>
      <button id="agregar-concepto" type="button" hidden class="mt-4 px-4 py-2 text-blue-900 bg-blue-50 rounded-lg font-semibold">+ Agregar concepto</button>
      <div id="resumen-desglose" hidden role="status" aria-live="polite" class="mt-6 bg-gray-50 border rounded-xl p-4 text-sm">
        <p>Total de conceptos: <strong id="total-conceptos"></strong></p>
        <p class="mt-1">Diferencia con el monto asignado: <strong id="diferencia"></strong></p>
        <p id="mensaje-total" class="mt-2 font-semibold"></p>
      </div>
      <p class="text-xs text-gray-500 mt-5">Guardar reemplaza el desglose anterior. El monto asignado y el estado del cobro se conservan.</p>
      <button type="submit" class="mt-4 bg-[#1e3a8a] hover:bg-blue-900 text-white rounded-lg px-5 py-2.5 font-semibold">Guardar desglose</button>
    </form>

<script src="{{ asset('js/desglose-gastos.js') }}" defer></script>
</div>
@endsection
