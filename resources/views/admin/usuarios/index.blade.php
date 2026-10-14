<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios - EdiConnect</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] font-['Inter',sans-serif] text-gray-900 min-h-screen">
  <header class="bg-[#1e3a8a] text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
      <a href="{{ route('admin.dashboard') }}" class="font-bold text-xl">EdiConnect</a>
      <div class="flex items-center gap-4">
        <span class="text-sm">{{ auth()->user()->nombre }}</span>
        <a href="{{ route('perfil.editar') }}" class="text-sm font-semibold hover:underline whitespace-nowrap">Mi perfil</a>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="bg-blue-800 hover:bg-blue-700 rounded-lg px-3 py-2 text-sm">Salir</button>
        </form>
      </div>
    </div>
  </header>
  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-800 hover:underline">← Volver al panel</a>
    <div class="my-6">
      <h1 class="text-2xl font-bold">Usuarios de la comunidad</h1>
      <p class="text-sm text-gray-600 mt-2">Controla el acceso activando o desactivando cuentas. Al reactivar una cuenta, conserva su rol y contraseña.</p>
    </div>

    @if (session('estado'))
      <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full text-sm text-left">
        <caption class="sr-only">Listado de usuarios, rol, estado y acciones de acceso</caption>
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
          <tr>
            <th scope="col" class="p-4 font-semibold">Usuario</th>
            <th scope="col" class="hidden md:table-cell p-4 font-semibold">RUT</th>
            <th scope="col" class="p-4 font-semibold">Rol</th>
            <th scope="col" class="p-4 font-semibold">Estado</th>
            <th scope="col" class="p-4 font-semibold">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse ($usuarios as $usuario)
            <tr>
              <th scope="row" class="p-4 font-normal">
                <div class="font-semibold">{{ $usuario->nombre }}</div>
                <div class="text-gray-500 mt-1">{{ $usuario->email }}</div>
                <div class="md:hidden text-gray-500 mt-1 text-xs">{{ $usuario->rut ?? 'Sin RUT' }}</div>
              </th>
              <td class="hidden md:table-cell p-4 whitespace-nowrap">{{ $usuario->rut ?? 'Sin RUT' }}</td>
              <td class="p-4">{{ $usuario->rol?->nombre ?? 'Sin rol' }}</td>
              <td class="p-4">
                <span @class(['inline-flex px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-emerald-50 text-emerald-800' => $usuario->estaActivo(), 'bg-gray-100 text-gray-700' => !$usuario->estaActivo()])>
                  {{ $usuario->estaActivo() ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="p-4">
                @if (auth()->id() === $usuario->id)
                  <span class="text-xs text-gray-500">Tu cuenta</span>
                @else
                  <form action="{{ route('admin.usuarios.estado', $usuario) }}" method="POST"
                    @if ($usuario->estaActivo()) onsubmit="return confirm('¿Desactivar esta cuenta? El usuario perderá el acceso a la comunidad.');" @endif>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="estado" value="{{ $usuario->estaActivo() ? 'inactivo' : 'activo' }}"/>
                    <button type="submit" aria-label="{{ $usuario->estaActivo() ? 'Desactivar' : 'Activar' }} cuenta de {{ $usuario->nombre }}"
                      @class(['rounded-lg border px-3 py-2 text-xs font-semibold whitespace-nowrap', 'border-red-200 text-red-700 hover:bg-red-50' => $usuario->estaActivo(), 'border-emerald-200 text-emerald-800 hover:bg-emerald-50' => !$usuario->estaActivo()])>
                      {{ $usuario->estaActivo() ? 'Desactivar' : 'Activar' }}
                    </button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-8 text-center text-gray-500">No hay usuarios registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-5">{{ $usuarios->links() }}</div>
  </main>
</body>
</html>
