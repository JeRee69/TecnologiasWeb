@once
  @php
    $mensajesError = collect($errors->getBags())->flatMap(fn ($bolsa) => $bolsa->all())->unique()->values()->all();
    $alertas = ['exito' => session('estado'), 'errores' => $mensajesError];
  @endphp
  <script id="ediconnect-alertas" type="application/json">{!! json_encode($alertas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
  <script src="{{ asset('lib/sweetalert2/sweetalert2.all.min.js') }}" defer></script>
  <script src="{{ asset('js/alertas.js') }}" defer></script>
@endonce
