@extends('layouts.app')
@section('title', 'Espacios comunes')
@section('content')
<div class="legacy-page">
<a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-800 hover:underline">← Volver al panel</a>
    <div class="my-6">
      <h1 class="text-2xl font-bold">Espacios comunes</h1>
      <p class="text-sm text-gray-600 mt-2">Registra las áreas compartidas y define cuáles están habilitadas en la comunidad.</p>
    </div>
    @if (session('estado'))
      <div data-swal-feedback role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">Revisa los campos indicados. El espacio no fue registrado.</div>
    @endif
    @if ($errors->getBag('estadoEspacio')->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-900 p-4 text-sm">
        @foreach ($errors->getBag('estadoEspacio')->all() as $error)<p>{{ $error }}</p>@endforeach
      </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-6 items-start">
      <section aria-labelledby="registro-heading" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
        <h2 id="registro-heading" class="text-lg font-semibold mb-5">Registrar espacio</h2>
        <form action="{{ route('admin.espacios-comunes.store') }}" method="POST" class="space-y-5">
          @csrf
          <div>
            <label for="nombre" class="block text-sm font-semibold mb-2">Nombre</label>
            <input id="nombre" name="nombre" type="text" value="{{ is_string(old('nombre')) ? old('nombre') : '' }}" required maxlength="150" placeholder="Ej.: Quincho norte"
              aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}" @if ($errors->has('nombre')) aria-describedby="nombre-error" @endif
              @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('nombre'), 'border-gray-300' => !$errors->has('nombre')])/>
            @error('nombre')<p id="nombre-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
          </div>
          <div>
            <label for="descripcion" class="block text-sm font-semibold mb-2">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4" required maxlength="2000" placeholder="Describe el espacio y sus características."
              aria-invalid="{{ $errors->has('descripcion') ? 'true' : 'false' }}" @if ($errors->has('descripcion')) aria-describedby="descripcion-error" @endif
              @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('descripcion'), 'border-gray-300' => !$errors->has('descripcion')])>{{ is_string(old('descripcion')) ? old('descripcion') : '' }}</textarea>
            @error('descripcion')<p id="descripcion-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
          </div>
          <div>
            <label for="estado" class="block text-sm font-semibold mb-2">Estado</label>
            <select id="estado" name="estado" required aria-describedby="estado-ayuda{{ $errors->has('estado') ? ' estado-error' : '' }}" aria-invalid="{{ $errors->has('estado') ? 'true' : 'false' }}"
              @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-700', 'border-red-400' => $errors->has('estado'), 'border-gray-300' => !$errors->has('estado')])>
              <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo</option>
              <option value="inactivo" @selected(old('estado', 'activo') === 'inactivo')>Inactivo</option>
            </select>
            <p id="estado-ayuda" class="text-xs text-gray-500 mt-2">Selecciona Activo si el espacio está habilitado o Inactivo si aún no puede utilizarse.</p>
            @error('estado')<p id="estado-error" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
          </div>
          <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-semibold text-sm rounded-lg px-5 py-3">Registrar espacio</button>
        </form>
      </section>

      <section aria-labelledby="listado-heading" class="lg:col-span-3">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h2 id="listado-heading" class="text-lg font-semibold">Espacios registrados</h2>
          <span class="text-xs text-gray-500">Total: {{ $espacios->total() }}</span>
        </div>
        <div class="space-y-4">
          @forelse ($espacios as $espacio)
            <article class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <h3 class="font-semibold text-lg break-words min-w-0 flex-1">{{ $espacio->nombre }}</h3>
                <span @class(['px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-emerald-50 text-emerald-800' => $espacio->estado === 'activo', 'bg-gray-100 text-gray-700' => $espacio->estado === 'inactivo'])>{{ $espacio->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
              </div>
              <p class="text-sm text-gray-600 mt-3 whitespace-pre-line break-words">{{ $espacio->descripcion }}</p>
              @if ($espacio->tieneConfiguracion())
                <dl class="grid sm:grid-cols-3 gap-3 mt-4 text-sm">
                  <div><dt class="text-gray-500">Aforo</dt><dd class="font-medium">{{ $espacio->aforo }} personas</dd></div>
                  <div><dt class="text-gray-500">Horario diario</dt><dd class="font-medium">{{ substr($espacio->hora_apertura, 0, 5) }}–{{ substr($espacio->hora_cierre, 0, 5) }}</dd></div>
                  <div><dt class="text-gray-500">Costo</dt><dd class="font-medium">{{ (int) $espacio->costo === 0 ? 'Gratuito' : '$'.number_format((float) $espacio->costo, 0, ',', '.').' CLP' }}</dd></div>
                </dl>
              @else
                <p class="text-xs text-amber-800 mt-4">Configuración pendiente</p>
              @endif
              <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
                <a href="{{ route('admin.espacios-comunes.configurar', $espacio) }}" class="inline-flex text-sm font-semibold text-[#1e3a8a] hover:underline">{{ $espacio->tieneConfiguracion() ? 'Ver y editar configuración' : 'Configurar espacio' }}</a>
                <form action="{{ route('admin.espacios-comunes.estado', $espacio) }}" method="POST"
                  data-swal-confirm="{{ $espacio->estado === 'activo' ? '¿Desactivar este espacio?' : '¿Activar este espacio?' }}"
                  data-swal-text="{{ $espacio->estado === 'activo' ? 'El espacio quedará inactivo. Se conservarán todos sus datos y configuración.' : 'El espacio quedará habilitado, conservando su configuración.' }}"
                  data-swal-button="{{ $espacio->estado === 'activo' ? 'Sí, desactivar' : 'Sí, activar' }}"
                  data-swal-danger="{{ $espacio->estado === 'activo' ? 'true' : 'false' }}">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="estado" value="{{ $espacio->estado === 'activo' ? 'inactivo' : 'activo' }}"/>
                  <button type="submit" aria-label="{{ $espacio->estado === 'activo' ? 'Desactivar' : 'Activar' }} espacio {{ $espacio->nombre }}"
                    @class(['rounded-lg border px-3 py-2 text-xs font-semibold', 'border-red-200 text-red-700 hover:bg-red-50' => $espacio->estado === 'activo', 'border-emerald-200 text-emerald-800 hover:bg-emerald-50' => $espacio->estado === 'inactivo'])>{{ $espacio->estado === 'activo' ? 'Desactivar' : 'Activar' }}</button>
                </form>
              </div>
            </article>
          @empty
            <div class="bg-white border border-dashed border-gray-300 rounded-xl p-8 text-center">
              <h3 class="font-semibold">Aún no hay espacios registrados</h3>
              <p class="text-sm text-gray-500 mt-2">Completa el formulario para agregar el primer espacio de tu comunidad.</p>
            </div>
          @endforelse
        </div>
        <div class="mt-5">{{ $espacios->links() }}</div>
      </section>
    </div>
</div>
@endsection
