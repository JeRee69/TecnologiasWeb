@extends('layouts.app')
@section('title', 'Gastos comunes')
@section('content')
<div class="legacy-page">
<div class="flex flex-wrap items-center justify-between gap-4">
  <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-900 hover:underline">← Volver al panel</a>
  <a href="{{ route('admin.comprobantes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold bg-blue-50 text-blue-900 hover:bg-blue-100 border border-blue-200 rounded-lg px-4 py-2 transition">
    <span class="material-symbols-outlined text-base" aria-hidden="true">fact_check</span>
    Revisar comprobantes de pago →
  </a>
</div>
    <h1 class="text-2xl font-bold mt-4">Gastos comunes</h1>
    <p class="text-gray-600 text-sm mt-1 mb-6">Genera cobros mensuales por departamento y consulta los registros emitidos.</p>

    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-900">
        <p class="font-semibold">Revisa los datos del formulario.</p>
        <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6 items-start">
      <section class="bg-white border rounded-xl p-6 shadow-sm" aria-labelledby="generar-heading">
        <h2 id="generar-heading" class="text-lg font-bold mb-2">Generar gasto común</h2>
        <p class="text-sm text-gray-600 mb-5">Ingresa el monto correspondiente a una unidad activa. Cada departamento admite un cobro por mes. Su estado vigente considera el vencimiento y la validación del pago.</p>
        @if ($departamentos->isEmpty())
          <p class="text-sm text-amber-900 bg-amber-50 p-3 rounded-lg">No hay departamentos activos para generar cobros.</p>
          <a href="{{ route('admin.departamentos.index') }}" class="block mt-3 text-blue-900 hover:underline">Administrar departamentos →</a>
        @else
          <form action="{{ route('admin.gastos-comunes.store') }}" method="POST" class="space-y-4"
                data-swal-confirm="¿Generar este gasto común?" data-swal-text="Se registrará el cobro para el departamento, período y vencimiento indicados." data-swal-button="Sí, generar">
            @csrf
            <div>
              <label for="departamento_id" class="block text-sm font-semibold mb-1">Departamento</label>
              <select id="departamento_id" name="departamento_id" required class="w-full border border-gray-300 rounded-lg p-2">
                <option value="">Selecciona una unidad</option>
                @foreach ($departamentos as $departamento)
                  <option value="{{ $departamento->id }}" @selected(old('departamento_id') == $departamento->id)>Depto. {{ $departamento->numero }} · Piso {{ $departamento->piso }}</option>
                @endforeach
              </select>
              @error('departamento_id')<p class="text-red-700 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="periodo" class="block text-sm font-semibold mb-1">Período mensual</label>
              <input id="periodo" name="periodo" type="month" required min="1900-01" max="9999-12" value="{{ is_string(old('periodo')) ? old('periodo') : ($periodo ?? now()->format('Y-m')) }}" class="w-full border border-gray-300 rounded-lg p-2"/>
              @error('periodo')<p class="text-red-700 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="monto" class="block text-sm font-semibold mb-1">Monto (CLP)</label>
              <input id="monto" name="monto" type="number" required min="1" max="999999999999" step="1" value="{{ is_scalar(old('monto')) ? old('monto') : '' }}" placeholder="Ej.: 85000" class="w-full border border-gray-300 rounded-lg p-2"/>
              <p class="text-xs text-gray-500 mt-1">Pesos enteros, sin puntos ni decimales.</p>
              @error('monto')<p class="text-red-700 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="fecha_vencimiento" class="block text-sm font-semibold mb-1">Fecha de vencimiento (opcional)</label>
              <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" min="1900-01-01" max="9999-12-31" value="{{ is_string(old('fecha_vencimiento')) ? old('fecha_vencimiento') : '' }}" class="w-full border border-gray-300 rounded-lg p-2"/>
              <p class="text-xs text-gray-500 mt-1">Si no está pagado, será moroso desde el día siguiente. Sin fecha, permanece pendiente.</p>
              @error('fecha_vencimiento')<p class="text-red-700 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold rounded-lg py-2.5">Generar gasto común</button>
          </form>
        @endif
      </section>

      <section class="lg:col-span-2 bg-white border rounded-xl shadow-sm overflow-hidden" aria-labelledby="listado-heading">
        <div class="p-5 border-b">
          <h2 id="listado-heading" class="text-lg font-bold mb-3">Cobros generados</h2>
          <form action="{{ route('admin.gastos-comunes.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div><label for="filtro-periodo" class="block text-sm mb-1">Filtrar por período</label><input id="filtro-periodo" type="month" name="periodo" value="{{ $periodo }}" min="1900-01" max="9999-12" class="border border-gray-300 rounded-lg p-2"/></div>
            <button type="submit" class="bg-blue-50 text-blue-900 rounded-lg px-4 py-2 font-semibold">Filtrar</button>
            <a href="{{ route('admin.gastos-comunes.index') }}" class="text-sm text-blue-900 hover:underline py-2">Ver todos</a>
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b text-gray-600"><tr><th scope="col" class="px-5 py-3">Departamento</th><th scope="col" class="px-5 py-3">Período</th><th scope="col" class="px-5 py-3 text-right">Monto CLP</th><th scope="col" class="px-5 py-3">Estado</th></tr></thead>
            <tbody class="divide-y">
              @forelse ($gastos as $gasto)
                <tr><td class="px-5 py-4 font-semibold">{{ $gasto->departamento->numero }}<a href="{{ route('admin.gastos-comunes.desglose', $gasto) }}" class="block mt-2 text-xs text-blue-900 hover:underline">{{ $gasto->detalles_count ? 'Ver y editar desglose' : 'Registrar desglose' }} →</a><a href="{{ route('admin.gastos-comunes.estado', $gasto) }}" class="block mt-2 text-xs text-blue-900 hover:underline">Gestionar estado y pago →</a></td><td class="px-5 py-4">{{ $gasto->periodo->format('m/Y') }}</td><td class="px-5 py-4 text-right whitespace-nowrap">${{ number_format($gasto->monto, 0, ',', '.') }}</td><td class="px-5 py-4">@include('components.estado-gasto', ['estadoGasto' => $gasto->estadoVigente()])</td></tr>
              @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-500">No hay gastos comunes para mostrar. Genera un cobro o consulta otro período.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="p-5 border-t text-sm text-gray-600">{{ $gastos->total() }} cobro(s) registrados. {{ $gastos->links() }}</div>
      </section>
    </div>
</div>
@endsection
