@extends('layouts.app')
@section('title', 'Mis gastos comunes')
@section('content')
<div class="legacy-page">
<a href="{{ route('residente.dashboard') }}" class="text-sm font-semibold text-blue-900 hover:underline">← Volver al portal</a>
    <h1 class="text-2xl font-bold mt-4">Mis gastos comunes</h1>
    <p class="text-sm text-gray-600 mt-1 mb-6">Consulta los cobros de tus departamentos y los conceptos que componen cada monto.</p>
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
          <article data-gasto="{{ $gasto->id }}" class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm" aria-labelledby="gasto-{{ $gasto->id }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <h2 id="gasto-{{ $gasto->id }}" class="font-bold text-lg">Depto. {{ $gasto->departamento->numero }}</h2>
                <p class="text-sm text-gray-600 mt-1">Período: <time datetime="{{ $gasto->periodo->format('Y-m') }}">{{ $gasto->periodo->format('m/Y') }}</time></p>
                <div class="mt-2">@include('components.estado-gasto', ['estadoGasto' => $gasto->estadoVigente()])</div>
                <p class="text-sm text-gray-600 mt-2">Vencimiento: {{ $gasto->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha definida' }}</p>
                @if ($gasto->pago_validado_at)
                  <p class="text-xs text-gray-500 mt-1">Pago validado el {{ $gasto->pago_validado_at->format('d/m/Y H:i') }}.</p>
                @endif
              </div>
              <div class="text-right"><p class="text-sm text-gray-500">Monto del cobro (CLP)</p><p class="text-2xl font-bold text-blue-900">${{ number_format($gasto->monto, 0, ',', '.') }}</p></div>
            </div>
            <a href="{{ route('residente.gastos-comunes.pdf', $gasto) }}" class="inline-block mt-4 px-4 py-2 bg-blue-50 text-blue-900 rounded-lg text-sm font-semibold hover:bg-blue-100" aria-label="Descargar PDF del departamento {{ $gasto->departamento->numero }}, período {{ $gasto->periodo->format('m/Y') }}">Descargar PDF</a>
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
@endsection
