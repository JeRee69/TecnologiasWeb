<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Configurar {{ $espacio->nombre }} - EdiConnect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 min-h-screen">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
      <a href="{{ route('admin.dashboard') }}" class="font-bold text-xl">EdiConnect</a>
      <div class="flex items-center gap-4">
        <a href="{{ route('perfil.editar') }}" class="text-sm font-semibold hover:underline">Mi perfil</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="bg-blue-800 hover:bg-blue-700 rounded-lg px-3 py-2 text-sm">Salir</button></form>
      </div>
    </div>
  </header>
  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('admin.espacios-comunes.index') }}" class="text-sm text-blue-800 hover:underline">← Volver a espacios comunes</a>
    <h1 class="text-2xl font-bold mt-6 break-words">Configurar {{ $espacio->nombre }}</h1>
    <p class="text-sm text-gray-600 mt-2 mb-6">Define las condiciones de uso de este espacio.</p>
    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">Revisa los campos indicados. La configuración no fue guardada.</div>
    @endif
    <div class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
      <p class="text-sm text-gray-600 whitespace-pre-line break-words mb-2">{{ $espacio->descripcion }}</p>
      <p class="text-xs text-gray-500 mb-6">Estado del espacio: {{ $espacio->estado === 'activo' ? 'Activo' : 'Inactivo' }}</p>
      <form action="{{ route('admin.espacios-comunes.configuracion', $espacio) }}" method="POST" class="space-y-5">
        @csrf
        @method('PATCH')
        <div class="grid sm:grid-cols-2 gap-5">
          @foreach ([
            'aforo' => ['Aforo máximo (personas)', 'number', $espacio->aforo, 1, 100000],
            'costo' => ['Costo (CLP)', 'number', $espacio->costo, 0, 999999999999],
            'hora_apertura' => ['Hora de apertura', 'time', $espacio->hora_apertura ? substr($espacio->hora_apertura, 0, 5) : '', null, null],
            'hora_cierre' => ['Hora de cierre', 'time', $espacio->hora_cierre ? substr($espacio->hora_cierre, 0, 5) : '', null, null],
          ] as $campo => [$etiqueta, $tipo, $valor, $minimo, $maximo])
            <div>
              <label for="{{ $campo }}" class="block text-sm font-semibold mb-2">{{ $etiqueta }}</label>
              <input id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" required
                value="{{ is_scalar(old($campo, $valor)) ? old($campo, $valor) : '' }}"
                @if ($tipo === 'number') min="{{ $minimo }}" max="{{ $maximo }}" step="1" @endif
                aria-invalid="{{ $errors->has($campo) ? 'true' : 'false' }}"
                @if ($errors->has($campo)) aria-describedby="{{ $campo }}-error" @endif
                @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has($campo), 'border-gray-300' => !$errors->has($campo)])/>
              @error($campo)<p id="{{ $campo }}-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
            </div>
          @endforeach
        </div>
        <p class="text-xs text-gray-500">El costo se expresa en pesos chilenos enteros; usa 0 si es gratuito. El horario es diario y la hora de cierre debe ser posterior a la apertura, dentro del mismo día.</p>
        <div>
          <label for="reglas" class="block text-sm font-semibold mb-2">Reglas de uso</label>
          <textarea id="reglas" name="reglas" required maxlength="5000" rows="5" placeholder="Ej.: Dejar el espacio limpio y respetar el aforo."
            aria-invalid="{{ $errors->has('reglas') ? 'true' : 'false' }}" @if ($errors->has('reglas')) aria-describedby="reglas-error" @endif
            @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('reglas'), 'border-gray-300' => !$errors->has('reglas')])>{{ is_string(old('reglas', $espacio->reglas)) ? old('reglas', $espacio->reglas) : '' }}</textarea>
          @error('reglas')<p id="reglas-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4">
          <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm rounded-lg px-5 py-3">Guardar configuración</button>
          <a href="{{ route('admin.espacios-comunes.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
        </div>
      </form>
    </div>
  </main>
  @include('components.alertas')
</body>
</html>
