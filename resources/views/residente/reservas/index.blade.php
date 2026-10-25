@extends('layouts.app')
@section('title', 'Mis reservas')
@push('styles')
<style>
@scope (.legacy-page) {

    * { box-sizing: border-box; }
    body { margin: 0; background: #f9fafb; color: #111827; font-family: Inter, system-ui, sans-serif; }
    header { background: var(--blue); color: white; }
    header > div, main { max-width: 1050px; margin: auto; padding: 24px; }
    header > div, nav { display: flex; gap: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    header a { color: white; }
    a { color: var(--blue); }
    .brand { font-size: 22px; font-weight: 700; text-decoration: none; }
    button { background: var(--blue); color: white; border: 1px solid #93c5fd; border-radius: 8px; padding: 10px 16px; cursor: pointer; }
    .panel { margin-top: 24px; background: white; border: 1px solid #d1d5db; border-radius: 12px; padding: 24px; }
    .muted { color: #4b5563; }
    .success { padding: 16px; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; background: #ecfdf5; }
    .scroll { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th, td { padding: 16px 12px; border-bottom: 1px solid #e5e7eb; }
    td:first-child { overflow-wrap: anywhere; min-width: 140px; }
    .pagination { margin-top: 24px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
    .error { padding: 16px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; }
    .cancelacion { min-width: 240px; max-width: 340px; }
    .cancelacion summary { cursor: pointer; color: #991b1b; }
    .cancelacion label { display: block; margin: 12px 0 8px; }
    .cancelacion textarea { width: 100%; border: 1px solid #9ca3af; border-radius: 6px; padding: 10px; font: inherit; }
    .cancelacion button { background: #b91c1c; margin-top: 10px; }
    .motivo { white-space: pre-line; overflow-wrap: anywhere; max-width: 340px; }
    :focus-visible { outline: 3px solid #2563eb; outline-offset: 3px; }
    @media(max-width:600px) { main { padding: 20px 16px; } .panel { padding: 16px; } }

}
</style>
@endpush
@section('content')
<div class="legacy-page">
<a href="{{ route('residente.dashboard') }}">← Volver al portal</a>
    <div class="page-heading mt-4">
      <div>
        <h1>Mis reservas</h1>
        <p class="muted">Consulta tus solicitudes y reservas. Solo las confirmadas han sido aprobadas por el administrador. Horarios de Santiago.</p>
      </div>
      <a class="ui-button shrink-0" href="{{ route('residente.espacios-comunes.disponibilidad') }}"><span class="material-symbols-outlined" aria-hidden="true">calendar_month</span>Ver espacios disponibles</a>
    </div>
    @if (session('estado'))
      <p class="success" data-swal-feedback role="status">{{ session('estado') }}</p>
    @endif
    @if ($errors->cancelacion->any())
      <div class="error" data-swal-feedback role="alert">@foreach ($errors->cancelacion->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <section class="panel" aria-label="Listado personal de reservas">
      @if ($reservas->total() === 0)
        <p>Aún no tienes reservas registradas.</p>
      @elseif ($reservas->isEmpty())
        <p>No hay reservas en esta página.</p>
        <a href="{{ $reservas->url(1) }}">Volver a la primera página</a>
      @else
        <div class="scroll">
          <table>
            <caption class="muted">Reservas asociadas a tu cuenta, ordenadas por fecha descendente</caption>
            <thead><tr><th scope="col">Espacio</th><th scope="col">Fecha</th><th scope="col">Horario</th><th scope="col">Estado</th><th scope="col">N.º</th><th scope="col">Cancelación</th></tr></thead>
            <tbody>
              @foreach ($reservas as $reserva)
                <tr class="reservation-row" data-estado-reserva="{{ $reserva->estado }}">
                  <td>{{ $reserva->espacioComun->nombre }}</td>
                  <td>{{ $reserva->fecha->format('d/m/Y') }}</td>
                  <td>{{ substr($reserva->hora_inicio, 0, 5) }}–{{ substr($reserva->hora_fin, 0, 5) }}</td>
                  <td>@include('components.estado-reserva', ['estadoReserva' => $reserva->estado])</td>
                  <td>#{{ $reserva->id }}</td>
                  <td>
                    @if ($reserva->puedeCancelarse())
                      <details class="cancelacion" @if (old('reserva_cancelacion') == $reserva->id) open @endif>
                        <summary>Cancelar reserva #{{ $reserva->id }}</summary>
                        <form action="{{ route('residente.reservas.cancelar', $reserva) }}" method="POST"
                          data-swal-confirm="¿Cancelar esta reserva?" data-swal-text="Se liberará el horario y se guardará tu justificación." data-swal-danger="true" data-swal-button="Sí, cancelar reserva">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="reserva_cancelacion" value="{{ $reserva->id }}">
                          <label for="motivo-{{ $reserva->id }}">Justificación (obligatoria)</label>
                          <textarea id="motivo-{{ $reserva->id }}" name="motivo_cancelacion" required maxlength="1000" rows="3">{{ old('reserva_cancelacion') == $reserva->id && is_string(old('motivo_cancelacion')) ? old('motivo_cancelacion') : '' }}</textarea>
                          <button type="submit">Confirmar cancelación</button>
                        </form>
                      </details>
                    @elseif ($reserva->estado === 'cancelada')
                      @if ($reserva->cancelada_at)<p class="muted">Cancelada el {{ $reserva->cancelada_at->format('d/m/Y H:i') }}</p>@endif
                      <p class="motivo">{{ $reserva->motivo_cancelacion ?? 'Sin justificación registrada.' }}</p>
                    @elseif ($reserva->estado === 'rechazada')
                      <span class="muted">Solicitud rechazada.</span>
                      <p class="motivo">{{ $reserva->eventos->firstWhere('estado_nuevo', 'rechazada')?->observacion ?? 'Sin observación registrada.' }}</p>
                    @else
                      <span class="muted">El horario ya comenzó.</span>
                    @endif
                    @if ($reserva->eventos->isNotEmpty())
                      <details class="motivo"><summary>Historial de la solicitud</summary>
                        @foreach ($reserva->eventos as $evento)
                          <p><strong>{{ \App\Models\Reserva::ESTADOS[$evento->estado_nuevo] }}</strong> · {{ $evento->created_at->format('d/m/Y H:i') }}<br>{{ $evento->observacion }}</p>
                        @endforeach
                      </details>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if ($reservas->hasPages())
          <nav class="pagination" aria-label="Paginación de reservas">
            @if (!$reservas->onFirstPage())<a href="{{ $reservas->previousPageUrl() }}" rel="prev">Anterior</a>@endif
            <span>Página {{ $reservas->currentPage() }} de {{ $reservas->lastPage() }}</span>
            @if ($reservas->hasMorePages())<a href="{{ $reservas->nextPageUrl() }}" rel="next">Siguiente</a>@endif
          </nav>
        @endif
      @endif
    </section>
</div>
@endsection
