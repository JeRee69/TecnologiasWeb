@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
<div class="legacy-page">
<a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-800 hover:underline inline-flex items-center gap-1 mb-3">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Volver al panel
    </a>
    <div class="my-6">
      <h1 class="text-2xl font-bold">Usuarios de la comunidad</h1>
      <p class="text-sm text-gray-600 mt-2">Registra usuarios, asigna roles válidos y gestiona el acceso mediante la activación o desactivación de cuentas.</p>
    </div>

    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
        <span>{{ session('estado') }}</span>
      </div>
    @endif

    {{-- Banner de credenciales generadas para el nuevo usuario (HU-03) --}}
    @if (session('credencial_generada'))
      <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950 shadow-sm">
        <div class="flex items-center gap-2 font-bold text-base text-[#1e3a8a] mb-2">
          <span class="material-symbols-outlined text-[22px]">badge</span>
          <span>Credenciales generadas para la nueva cuenta</span>
        </div>
        <p class="text-xs text-blue-900 mb-3">Entrega estas credenciales al usuario para que pueda acceder a la plataforma por primera vez:</p>
        <div class="grid sm:grid-cols-3 gap-3 bg-white p-3 rounded-lg border border-blue-100 font-mono text-xs">
          <div>
            <span class="block text-gray-500 font-sans font-semibold text-[11px] uppercase">Correo</span>
            <span class="font-semibold text-gray-900">{{ session('credencial_generada.email') }}</span>
          </div>
          <div>
            <span class="block text-gray-500 font-sans font-semibold text-[11px] uppercase">Contraseña asignada</span>
            <span class="font-bold text-[#1e3a8a] bg-blue-100 px-2 py-0.5 rounded">{{ session('credencial_generada.password') }}</span>
          </div>
          <div>
            <span class="block text-gray-500 font-sans font-semibold text-[11px] uppercase">Rol</span>
            <span class="font-semibold text-gray-900">{{ session('credencial_generada.rol') }}</span>
          </div>
        </div>
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">
        <div class="font-semibold mb-1 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
          <span>No fue posible registrar los datos. Revisa los siguientes campos:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs text-red-800 ml-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
      {{-- Formulario para registrar usuario y asignar rol (HU-03) --}}
      <section aria-labelledby="registro-usuario-heading" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
          <span class="material-symbols-outlined text-[#1e3a8a] text-[22px]">person_add</span>
          <h2 id="registro-usuario-heading" class="text-lg font-bold text-gray-900">Registrar usuario</h2>
        </div>
        <p class="text-xs text-gray-500 mb-5">Ingresa los datos obligatorios para crear la cuenta y asignarle un rol.</p>

        <form action="{{ route('admin.usuarios.store') }}" method="POST" class="space-y-4">
          @csrf

          <div>
            <label for="nombre" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Nombre completo <span class="text-red-500">*</span>
            </label>
            <input
              id="nombre"
              name="nombre"
              type="text"
              value="{{ old('nombre') }}"
              required
              maxlength="150"
              placeholder="Ej.: Carlos Silva Pérez"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('nombre'),
                'border-gray-300' => !$errors->has('nombre')
              ])
            />
            @error('nombre')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Correo electrónico <span class="text-red-500">*</span>
            </label>
            <input
              id="email"
              name="email"
              type="email"
              value="{{ old('email') }}"
              required
              maxlength="255"
              placeholder="ejemplo@correo.cl"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('email'),
                'border-gray-300' => !$errors->has('email')
              ])
            />
            @error('email')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="rol_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Rol asignado <span class="text-red-500">*</span>
            </label>
            <select
              id="rol_id"
              name="rol_id"
              required
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('rol_id'),
                'border-gray-300' => !$errors->has('rol_id')
              ])
            >
              <option value="" disabled {{ old('rol_id') ? '' : 'selected' }}>Selecciona un rol válido</option>
              @foreach ($roles as $rol)
                <option value="{{ $rol->id }}" {{ (string) old('rol_id') === (string) $rol->id ? 'selected' : '' }}>
                  {{ $rol->nombre }}
                </option>
              @endforeach
            </select>
            @error('rol_id')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="rut" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              RUT <span class="text-xs text-gray-400 font-normal lowercase">(opcional)</span>
            </label>
            <input
              id="rut"
              name="rut"
              type="text"
              value="{{ old('rut') }}"
              maxlength="20"
              placeholder="12.345.678-9"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('rut'),
                'border-gray-300' => !$errors->has('rut')
              ])
            />
            @error('rut')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="telefono" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Teléfono <span class="text-xs text-gray-400 font-normal lowercase">(opcional)</span>
            </label>
            <input
              id="telefono"
              name="telefono"
              type="text"
              value="{{ old('telefono') }}"
              maxlength="20"
              placeholder="+56 9 1234 5678"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('telefono'),
                'border-gray-300' => !$errors->has('telefono')
              ])
            />
            @error('telefono')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Contraseña inicial <span class="text-xs text-gray-400 font-normal lowercase">(opcional)</span>
            </label>
            <input
              id="password"
              name="password"
              type="password"
              minlength="12"
              maxlength="100"
              placeholder="Dejar en blanco para autogenerar"
              @class([
                'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700',
                'border-red-400 bg-red-50' => $errors->has('password'),
                'border-gray-300' => !$errors->has('password')
              ])
            />
            <p class="text-[11px] text-gray-500 mt-1">Si la dejas vacía, el sistema generará una clave temporal automáticamente.</p>
            @error('password')
              <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
          </div>

          <button
            type="submit"
            class="w-full bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm py-2.5 px-4 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            <span>Registrar usuario</span>
          </button>
        </form>
      </section>

      {{-- Listado de Usuarios y Gestión de Estados (HU-04) --}}
      <section aria-labelledby="listado-usuarios-heading" class="lg:col-span-3 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-200 flex items-center justify-between">
          <h2 id="listado-usuarios-heading" class="text-base font-bold text-gray-900">Usuarios registrados</h2>
          <span class="text-xs text-gray-500">Total: {{ $usuarios->total() }}</span>
        </div>

        <div class="overflow-x-auto">
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
                <tr class="hover:bg-gray-50/60 transition-colors">
                  <th scope="row" class="p-4 font-normal">
                    <div class="font-semibold text-gray-900">{{ $usuario->nombre }}</div>
                    <div class="text-gray-500 mt-0.5 text-xs">{{ $usuario->email }}</div>
                    <div class="md:hidden text-gray-500 mt-1 text-xs">{{ $usuario->rut ?? 'Sin RUT' }}</div>
                  </th>
                  <td class="hidden md:table-cell p-4 whitespace-nowrap text-gray-600">{{ $usuario->rut ?? 'Sin RUT' }}</td>
                  <td class="p-4 whitespace-nowrap font-medium text-gray-800">{{ $usuario->rol?->nombre ?? 'Sin rol' }}</td>
                  <td class="p-4 whitespace-nowrap">
                    <span @class(['inline-flex px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-emerald-50 text-emerald-800' => $usuario->estaActivo(), 'bg-gray-100 text-gray-700' => !$usuario->estaActivo()])>
                      {{ $usuario->estaActivo() ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="p-4 whitespace-nowrap">
                    @if (auth()->id() === $usuario->id)
                      <span class="text-xs text-gray-400 font-medium">Tu cuenta</span>
                    @else
                      <form action="{{ route('admin.usuarios.estado', $usuario) }}" method="POST"
                        data-swal-confirm="{{ $usuario->estaActivo() ? '¿Desactivar esta cuenta?' : '¿Activar esta cuenta?' }}"
                        data-swal-text="{{ $usuario->estaActivo() ? 'El usuario perderá el acceso a la comunidad. Se conservarán sus datos y su rol.' : 'El usuario recuperará el acceso según su rol asignado.' }}"
                        data-swal-button="{{ $usuario->estaActivo() ? 'Sí, desactivar' : 'Sí, activar' }}"
                        data-swal-danger="{{ $usuario->estaActivo() ? 'true' : 'false' }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ $usuario->estaActivo() ? 'inactivo' : 'activo' }}"/>
                        <button type="submit" aria-label="{{ $usuario->estaActivo() ? 'Desactivar' : 'Activar' }} cuenta de {{ $usuario->nombre }}"
                          @class(['rounded-lg border px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer', 'border-red-200 text-red-700 hover:bg-red-50' => $usuario->estaActivo(), 'border-emerald-200 text-emerald-800 hover:bg-emerald-50' => !$usuario->estaActivo()])>
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

        @if ($usuarios->hasPages())
          <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $usuarios->links() }}
          </div>
        @endif
      </section>
    </div>
</div>
@endsection
