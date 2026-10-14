<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mi perfil - EdiConnect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 min-h-screen">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
      <a href="{{ route($rutaPanel) }}" class="font-bold text-xl">EdiConnect</a>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-800 hover:bg-blue-700 rounded-lg px-3 py-2 text-sm">Salir</button>
      </form>
    </div>
  </header>
  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route($rutaPanel) }}" class="text-sm text-blue-800 hover:underline">← Volver al panel</a>
    <h1 class="text-2xl font-bold mt-6">Mi perfil</h1>
    <p class="text-sm text-gray-600 mt-2 mb-6">Mantén tus datos de contacto al día para la comunicación con tu comunidad.</p>

    @if (session('estado'))
      <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm">{{ session('estado') }}</div>
    @endif
    @if ($errors->any() || $errors->getBag('contrasena')->any())
      <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">Revisa los campos indicados. Tus cambios no se guardaron.</div>
    @endif

    <section aria-labelledby="cuenta-heading" class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 mb-6 shadow-sm">
      <h2 id="cuenta-heading" class="text-lg font-semibold mb-4">Datos de tu cuenta</h2>
      <dl class="grid sm:grid-cols-2 gap-5 text-sm">
        <div><dt class="text-gray-500">Nombre</dt><dd class="font-medium mt-1 break-words">{{ $usuario->nombre }}</dd></div>
        <div><dt class="text-gray-500">RUT</dt><dd class="font-medium mt-1">{{ $usuario->rut ?? 'Sin RUT' }}</dd></div>
        <div><dt class="text-gray-500">Correo de acceso</dt><dd class="font-medium mt-1 break-all">{{ $usuario->email }}</dd></div>
        <div><dt class="text-gray-500">Rol</dt><dd class="font-medium mt-1">{{ $usuario->rol?->nombre ?? 'Sin rol' }}</dd></div>
        <div><dt class="text-gray-500">Estado</dt><dd class="font-medium mt-1">{{ $usuario->estaActivo() ? 'Activo' : 'Inactivo' }}</dd></div>
      </dl>
      <p class="mt-5 text-xs text-gray-500">Para modificar estos datos, contacta a la administración.</p>
    </section>

    <section aria-labelledby="contacto-heading" class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
      <h2 id="contacto-heading" class="text-lg font-semibold">Datos de contacto</h2>
      <p class="text-sm text-gray-500 mt-1 mb-5">Ambos campos son opcionales. Puedes dejarlos en blanco para eliminar el dato guardado.</p>
      <form action="{{ route('perfil.actualizar') }}" method="POST" class="space-y-5">
        @csrf
        @method('PATCH')
        <div>
          <label for="telefono" class="block text-sm font-semibold mb-2">Teléfono</label>
          <input id="telefono" name="telefono" type="tel" autocomplete="tel" maxlength="30"
            value="{{ old('telefono', $usuario->telefono) }}" placeholder="Ej.: +56 9 1234 5678"
            aria-describedby="telefono-ayuda{{ $errors->has('telefono') ? ' telefono-error' : '' }}" aria-invalid="{{ $errors->has('telefono') ? 'true' : 'false' }}"
            @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('telefono'), 'border-gray-300' => !$errors->has('telefono')])/>
          <p id="telefono-ayuda" class="text-xs text-gray-500 mt-2">Entre 7 y 15 dígitos. Puedes incluir el código de país, espacios, paréntesis o guiones.</p>
          @error('telefono')<p id="telefono-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
        </div>
        <div>
          <label for="email_secundario" class="block text-sm font-semibold mb-2">Correo secundario</label>
          <input id="email_secundario" name="email_secundario" type="email" autocomplete="email" maxlength="254"
            value="{{ old('email_secundario', $usuario->email_secundario) }}" placeholder="Ej.: contacto@ejemplo.cl"
            aria-describedby="email-ayuda{{ $errors->has('email_secundario') ? ' email-error' : '' }}" aria-invalid="{{ $errors->has('email_secundario') ? 'true' : 'false' }}"
            @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('email_secundario'), 'border-gray-300' => !$errors->has('email_secundario')])/>
          <p id="email-ayuda" class="text-xs text-gray-500 mt-2">Este correo es de contacto. Tu correo para iniciar sesión seguirá siendo el mismo.</p>
          @error('email_secundario')<p id="email-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
          <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm rounded-lg px-5 py-3">Guardar cambios</button>
          <a href="{{ route($rutaPanel) }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
        </div>
      </form>
    </section>
    <section aria-labelledby="contrasena-heading" class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm mt-6">
      <h2 id="contrasena-heading" class="text-lg font-semibold">Cambiar contraseña</h2>
      <p class="text-sm text-gray-500 mt-1 mb-5">Elige una contraseña personal de al menos 12 caracteres. Al cambiarla, los demás dispositivos deberán volver a iniciar sesión.</p>
      <form action="{{ route('perfil.contrasena') }}" method="POST" class="space-y-5">
        @csrf
        @method('PATCH')
        @foreach (['current_password' => 'Contraseña actual', 'password' => 'Nueva contraseña', 'password_confirmation' => 'Confirmar nueva contraseña'] as $campo => $etiqueta)
          <div>
            <label for="{{ $campo }}" class="block text-sm font-semibold mb-2">{{ $etiqueta }}</label>
            <input id="{{ $campo }}" name="{{ $campo }}" type="password" required
              autocomplete="{{ $campo === 'current_password' ? 'current-password' : 'new-password' }}"
              @if ($campo !== 'current_password') minlength="12" @endif
              aria-invalid="{{ $errors->getBag('contrasena')->has($campo) ? 'true' : 'false' }}"
              @if ($errors->getBag('contrasena')->has($campo)) aria-describedby="{{ $campo }}-error" @endif
              @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->getBag('contrasena')->has($campo), 'border-gray-300' => !$errors->getBag('contrasena')->has($campo)])/>
            @error($campo, 'contrasena')<p id="{{ $campo }}-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
          </div>
        @endforeach
        <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm rounded-lg px-5 py-3">Actualizar contraseña</button>
      </form>
    </section>
  </main>
</body>
</html>
