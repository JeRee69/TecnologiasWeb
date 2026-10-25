@extends('layouts.app')
@section('title', 'Reservas y cancelaciones')
@push('styles')
<style>
@scope (.legacy-page) {

    * { box-sizing: border-box; }
    body { margin: 0; background: #f9fafb; color: #111827; font-family: Inter, system-ui, sans-serif; }
    header { background: var(--blue); color: white; }
    header > div, main { max-width: 1250px; margin: auto; padding: 24px; }
    header > div, nav, .filters { display: flex; gap: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    header a { color: white; } a { color: var(--blue); }
    .brand { font-size: 22px; font-weight: 700; text-decoration: none; }
    .panel { padding: 24px; margin-top: 24px; background: white; border: 1px solid #d1d5db; border-radius: 12px; }
    .filters { align-items: end; justify-content: flex-start; }
    .field { flex: 1; min-width: 180px; }
    label { display: block; font-weight: 600; margin-bottom: 8px; }
    input, select { width: 100%; padding: 12px; border: 1px solid #9ca3af; border-radius: 8px; font: inherit; }
    button { cursor: pointer; padding: 12px 18px; border: 0; border-radius: 8px; background: var(--blue); color: white; font: inherit; }
    .muted { font-size: 14px; color: #4b5563; }
    .scroll { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th, td { padding: 14px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    .text { min-width: 150px; max-width: 320px; overflow-wrap: anywhere; }
    .reason { white-space: pre-line; margin: 0; }
    .badge { display: inline-block; padding: 5px 8px; border-radius: 6px; }
    .confirmada { background: #ecfdf5; color: #065f46; }
    .pendiente { background: #fffbeb; color: #92400e; }
    .cancelada, .rechazada, .error { background: #fef2f2; color: #991b1b; }
    .gestion { min-width: 240px; max-width: 340px; }
    .gestion textarea { width: 100%; font: inherit; padding: 10px; border: 1px solid #9ca3af; border-radius: 8px; }
    .gestion form { margin-top: 12px; }
    .success { padding: 16px; background: #ecfdf5; color: #065f46; border-radius: 8px; }
    .error { padding: 16px; border-radius: 8px; }
    .pagination { margin-top: 20px; justify-content: flex-start; }
    :focus-visible { outline: 3px solid #2563eb; outline-offset: 3px; }
    @media(max-width:600px) { main { padding: 20px 16px; } .panel { padding: 16px; } }

}
</style>
@endpush
@section('content')
<div class="legacy-page">
<a href="{{ route('admin.dashboard') }}">← Volver al panel</a>
    <h1>Reservas y cancelaciones</h1>
    <p class="muted">Revisa solicitudes, aprueba o rechaza las pendientes y consulta el historial. Fechas y horarios de Santiago.</p>
    <p><a href="{{ route('admin.reservas.index', ['estado' => 'pendiente']) }}">Ver solicitudes pendientes ({{ $pendientes }})</a></p>
    @if (session('estado'))<p class="success" data-swal-feedback role="status">{{ session('estado') }}</p>@endif
    @if ($errors->revision->any())<div class="error" data-swal-feedback role="alert">@foreach ($errors->revision->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if ($errors->any())<div class="error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form class="panel filters" method="GET" action="{{ route('admin.reservas.index') }}">
      <div class="field">
        <label for="espacio_id">Espacio</label>
        <select name="espacio_id" id="espacio_id">
          <option value="">Todos los espacios</option>
          @foreach ($espacios as $espacio)<option value="{{ $espacio->id }}" @selected(($filtros['espacio_id'] ?? '') == $espacio->id)>{{ $espacio->nombre }}{{ $espacio->estado === 'inactivo' ? ' (Inactivo)' : '' }}</option>@endforeach
        </select>
      </div>
      <div class="field"><label for="fecha">Fecha de la reserva</label><input type="date" name="fecha" id="fecha" value="{{ $filtros['fecha'] ?? '' }}"></div>
      <div class="field">
        <label for="estado">Estado</label>
        <select name="estado" id="estado">
          <option value="">Todos los estados</option>
          @foreach (\App\Models\Reserva::ESTADOS as $valor => $etiqueta)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>@endforeach
        </select>
      </div>
      <button type="submit">Filtrar</button><a href="{{ route('admin.reservas.index') }}">Limpiar filtros</a>
    </form>
    @if (!$errors->any())
      <section class="panel" aria-label="Listado de reservas de la comunidad">
        <p class="muted">{{ $reservas->total() }} reservas encontradas</p>
        @if ($reservas->total() === 0)
          <p>No hay reservas que coincidan con los filtros.</p>
        @elseif ($reservas->isEmpty())
          <p>No hay reservas en esta página.</p><a href="{{ $reservas->url(1) }}">Volver a la primera página</a>
        @else
          <div class="scroll"><table>
            <caption class="muted">Ordenadas por fecha y horario descendentes</caption>
            <thead><tr><th scope="col">N.º</th><th scope="col">Residente</th><th scope="col">Espacio</th><th scope="col">Fecha</th><th scope="col">Horario</th><th scope="col">Estado</th><th scope="col">Cancelación</th><th scope="col">Gestión e historial</th></tr></thead>
            <tbody>
              @foreach ($reservas as $reserva)
                <tr class="reservation-row" data-estado-reserva="{{ $reserva->estado }}">
                  <td>#{{ $reserva->id }}</td>
                  <td class="text">{{ $reserva->usuario->nombre }}<p class="muted">{{ $reserva->usuario->email }}</p></td>
                  <td class="text">{{ $reserva->espacioComun->nombre }}</td>
                  <td>{{ $reserva->fecha->format('d/m/Y') }}</td>
                  <td>{{ substr($reserva->hora_inicio, 0, 5) }}–{{ substr($reserva->hora_fin, 0, 5) }}</td>
                  <td>@include('components.estado-reserva', ['estadoReserva' => $reserva->estado])</td>
                  <td class="text">
                    @if ($reserva->estado === 'cancelada')
                      @if ($reserva->cancelada_at)<p class="muted">{{ $reserva->cancelada_at->format('d/m/Y H:i') }}</p>@endif
                      <p class="reason">{{ $reserva->motivo_cancelacion ?? 'Sin justificación registrada.' }}</p>
                    @else<span class="muted">—</span>@endif
                  </td>
                  <td class="gestion">
                    @if ($reserva->estado === 'pendiente' || ($reserva->estado === 'confirmada' && $reserva->puedeCancelarse()))
                      <details @if (old('revision_reserva') == $reserva->id) open @endif><summary>Gestionar solicitud #{{ $reserva->id }}</summary>
                        <form action="{{ route('admin.reservas.estado', $reserva) }}" method="POST" data-swal-confirm="¿Aplicar esta decisión?" data-swal-text="Se guardará el estado y tu observación en el historial.">
                          @csrf @method('PATCH')
                          <input type="hidden" name="revision_reserva" value="{{ $reserva->id }}">
                          <label for="decision-{{ $reserva->id }}">Decisión</label>
                          <select name="estado" id="decision-{{ $reserva->id }}" required>
                            <option value="">Selecciona una acción</option>
                            @foreach ($reserva->estado === 'pendiente' ? ['confirmada' => 'Aprobar solicitud', 'rechazada' => 'Rechazar solicitud'] : ['cancelada' => 'Cancelar reserva aprobada'] as $valor => $etiqueta)
                              <option value="{{ $valor }}" @selected(old('revision_reserva') == $reserva->id && old('estado') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                          </select>
                          <label for="observacion-{{ $reserva->id }}">Observación (obligatoria al rechazar o cancelar)</label>
                          <textarea name="observacion" id="observacion-{{ $reserva->id }}" rows="3" maxlength="1000">{{ old('revision_reserva') == $reserva->id && is_string(old('observacion')) ? old('observacion') : '' }}</textarea>
                          <button type="submit">Aplicar decisión</button>
                        </form>
                      </details>
                    @endif
                    @if ($reserva->eventos->isNotEmpty())
                      <details><summary>Ver historial</summary>
                        @foreach ($reserva->eventos as $evento)
                          <p class="reason"><strong>{{ \App\Models\Reserva::ESTADOS[$evento->estado_nuevo] }}</strong> · {{ $evento->created_at->format('d/m/Y H:i') }}<br>{{ $evento->usuario?->nombre ?? 'Cuenta eliminada' }}<br>{{ $evento->observacion }}</p>
                        @endforeach
                      </details>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table></div>
          @if ($reservas->hasPages())<nav class="pagination" aria-label="Paginación de reservas">
            @if (!$reservas->onFirstPage())<a href="{{ $reservas->previousPageUrl() }}" rel="prev">Anterior</a>@endif
            <span>Página {{ $reservas->currentPage() }} de {{ $reservas->lastPage() }}</span>
            @if ($reservas->hasMorePages())<a href="{{ $reservas->nextPageUrl() }}" rel="next">Siguiente</a>@endif
          </nav>@endif
        @endif
      </section>
    @endif

<script>
    document.querySelectorAll('.gestion select[name="estado"]').forEach(select => {
      const update = () => { select.form.querySelector('textarea').required = ['rechazada', 'cancelada'].includes(select.value); };
      select.addEventListener('change', update);
      update();
    });
  </script>
</div>
@endsection
