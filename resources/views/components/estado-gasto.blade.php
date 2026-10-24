@php
  $etiqueta = match ($estadoGasto) {
    'pagado' => 'Al día (Pagado)',
    'moroso' => 'Moroso',
    'sin_cobros' => 'Sin cobros registrados',
    default => 'Pendiente',
  };
  $color = match ($estadoGasto) {
    'pagado' => 'bg-emerald-50 text-emerald-900 border-emerald-200',
    'moroso' => 'bg-red-50 text-red-900 border-red-200',
    'sin_cobros' => 'bg-gray-50 text-gray-700 border-gray-200',
    default => 'bg-amber-50 text-amber-900 border-amber-200',
  };
@endphp
<span data-estado-gasto="{{ $estadoGasto }}" class="inline-block border rounded-full px-3 py-1 text-sm font-semibold {{ $color }}">{{ $etiqueta }}</span>
