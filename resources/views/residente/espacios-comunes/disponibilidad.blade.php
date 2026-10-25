@extends('layouts.app')
@section('title', 'Disponibilidad de espacios')
@push('styles')
<style>
@scope (.legacy-page) {

    *{box-sizing:border-box}body{margin:0;background:#f9fafb;color:#111827;font-family:Inter,system-ui,sans-serif}header{background:var(--blue);color:white}header>div,main{max-width:1050px;margin:auto;padding:24px}header>div,.nav,.filters{display:flex;gap:20px;align-items:center;justify-content:space-between}header a{color:white}a{color:var(--blue)}header form{margin:0}.brand{font-size:22px;font-weight:700;text-decoration:none}h1{font-size:28px;margin-bottom:8px}h2{font-size:21px}.muted{color:#4b5563;font-size:14px}.panel{background:white;border:1px solid #d1d5db;border-radius:12px;padding:24px;margin-top:24px}.filters{align-items:end;flex-wrap:wrap;justify-content:flex-start}.field{flex:1;min-width:200px}label.field-label{display:block;font-size:14px;font-weight:600;margin-bottom:8px}select,input[type=date]{width:100%;padding:12px;border:1px solid #9ca3af;border-radius:8px;font:inherit}button{cursor:pointer;border:0;border-radius:8px;padding:13px 20px;background:var(--blue);color:white;font:inherit;font-weight:600}.legend{display:flex;flex-wrap:wrap;gap:16px;font-size:14px;margin:20px 0}.legend span{padding:6px 12px;border-radius:6px}.available{background:#ecfdf5;color:#065f46}.occupied{background:#fff1f2;color:#9f1239}.past{background:#f3f4f6;color:#4b5563}.blocks{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;border:0;padding:0;margin:20px 0}.blocks legend{font-weight:600;padding:0;margin-bottom:16px}.slot{display:flex;align-items:center;gap:10px;padding:16px;border:1px solid #a7f3d0;border-radius:8px;cursor:pointer}.slot small{display:block;margin-top:6px}.slot:has(input:checked){outline:3px solid var(--blue);outline-offset:2px}.slot:has(input:disabled){cursor:not-allowed;border-color:#d1d5db}input[type=radio]{accent-color:var(--blue);width:18px;height:18px;flex-shrink:0}:focus-visible{outline:3px solid #2563eb;outline-offset:3px}.rules{white-space:pre-line;overflow-wrap:anywhere}.error{color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:16px;border-radius:8px}#seleccion{font-weight:600;color:var(--blue)}h2{overflow-wrap:anywhere}@media(max-width:600px){header>div{align-items:flex-start}.nav{gap:12px;flex-wrap:wrap;justify-content:flex-end}main{padding:20px 16px}.panel{padding:18px}h1{font-size:24px}}

}
</style>
@endpush
@section('content')
<div class="legacy-page">
<a class="ui-link" href="{{ route('residente.reservas.index') }}">← Volver a mis reservas</a>
    <h1>Disponibilidad de espacios comunes</h1>
    <p class="muted">Elige un espacio y una fecha en el calendario para consultar sus horarios.</p>
    @if ($errors->any() || $errors->reserva->any())
      <div class="error" role="alert">@foreach (array_merge($errors->all(), $errors->reserva->all()) as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if ($espacios->isEmpty())
      <div class="panel"><p>No hay espacios comunes activos para consultar en este momento.</p></div>
    @else
      <form class="panel filters" action="{{ route('residente.espacios-comunes.disponibilidad') }}" method="GET">
        <div class="field">
          <label class="field-label" for="espacio_id">Espacio común</label>
          <select id="espacio_id" name="espacio_id" required>
            @foreach ($espacios as $opcion)
              <option value="{{ $opcion->id }}" @selected($espacio?->id === $opcion->id)>{{ $opcion->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label class="field-label" for="fecha">Fecha</label>
          <input type="date" id="fecha" name="fecha" value="{{ $fecha }}" required aria-invalid="{{ $errors->has('fecha') ? 'true' : 'false' }}">
        </div>
        <button type="submit">Consultar disponibilidad</button>
      </form>
      @if ($espacio && !$errors->any())
        <section class="panel" aria-labelledby="horarios">
          <h2 id="horarios">{{ $espacio->nombre }} · {{ \Carbon\CarbonImmutable::parse($fecha)->format('d/m/Y') }}</h2>
          <p class="muted rules">{{ $espacio->descripcion }}</p>
          @if (!$espacio->tieneConfiguracion())
            <p role="status">Este espacio tiene su configuración pendiente. Aún no se puede consultar su disponibilidad.</p>
          @else
            <p class="muted">Horario: {{ substr($espacio->hora_apertura, 0, 5) }}–{{ substr($espacio->hora_cierre, 0, 5) }} · Aforo: {{ $espacio->aforo }} personas · {{ (int) $espacio->costo === 0 ? 'Gratuito' : '$'.number_format((float) $espacio->costo, 0, ',', '.').' CLP' }}</p>
            <div class="legend" aria-label="Estados de los bloques"><span class="available">Disponible</span><span class="occupied">Ocupado</span><span class="past">Horario pasado</span></div>
            <p class="muted">Bloques de hasta una hora, en horario de Santiago. Los horarios ocupados o pasados no se pueden seleccionar.</p>
            <form action="{{ route('residente.reservas.store') }}" method="POST">
            @csrf
            <input type="hidden" name="espacio_id" value="{{ $espacio->id }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">
            <fieldset class="blocks">
              <legend>Horarios del día seleccionado</legend>
              @foreach ($bloques as $bloque)
                <label class="slot {{ ['disponible' => 'available', 'ocupado' => 'occupied', 'pasado' => 'past'][$bloque['estado']] }}">
                  <input type="radio" name="bloque" value="{{ $bloque['inicio'] }}–{{ $bloque['fin'] }}" required @disabled($bloque['estado'] !== 'disponible')>
                  <span>{{ $bloque['inicio'] }}–{{ $bloque['fin'] }}<small>{{ ['disponible' => 'Disponible', 'ocupado' => 'Ocupado', 'pasado' => 'Horario pasado'][$bloque['estado']] }}</small></span>
                </label>
              @endforeach
            </fieldset>
            @if (!collect($bloques)->contains('estado', 'disponible'))
              <p role="status">No hay bloques disponibles para esta fecha. Consulta otro día o espacio.</p>
            @endif
            <p id="seleccion" role="status" aria-live="polite"></p>
            @if (collect($bloques)->contains('estado', 'disponible'))
              <button type="submit">Enviar solicitud</button>
            @endif
            <p class="muted">La solicitud quedará pendiente de aprobación del administrador. El horario se mantiene ocupado mientras se revisa.</p>
            </form>
            <details><summary>Reglas de uso</summary><p class="rules">{{ $espacio->reglas }}</p></details>
          @endif
        </section>
      @endif
    @endif

<script>
    document.querySelectorAll('input[name="bloque"]:not(:disabled)').forEach(input => {
      input.addEventListener('change', () => {
        document.getElementById('seleccion').textContent = 'Bloque seleccionado: ' + input.value + '. Envía la solicitud para que el administrador la revise.';
      });
    });
    // Una modificación de filtros descarta el resultado anterior hasta volver a consultar.
    document.querySelectorAll('#espacio_id, #fecha').forEach(input => {
      input.addEventListener('change', () => {
        const resultados = document.querySelector('section[aria-labelledby="horarios"]');
        if (resultados) {
          resultados.hidden = true;
          resultados.querySelectorAll('input[name="bloque"]').forEach(bloque => { bloque.checked = false; bloque.disabled = true; });
        }
      });
    });
  </script>
</div>
@endsection
