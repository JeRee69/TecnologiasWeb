@extends('layouts.app')
@section('title', 'Departamento '.e($departamento->numero))
@section('content')
<div class="legacy-page">
    <div class="mb-6">
      <a href="{{ route('admin.departamentos.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1e3a8a] hover:underline mb-3">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al padrón de departamentos
      </a>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-blue-100 text-[#1e3a8a] flex items-center justify-center font-bold text-xl shadow-sm">
            {{ $departamento->numero }}
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Departamento {{ $departamento->numero }}</h1>
            <p class="text-xs text-gray-500">Piso {{ $departamento->piso }} &bull; Registrado el {{ $departamento->created_at->format('d/m/Y') }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          @if ($departamento->estaActivo())
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              Departamento Activo
            </span>
            <form
              action="{{ route('admin.departamentos.estado', $departamento) }}"
              method="POST"
              data-swal-confirm="¿Desactivar el Departamento {{ $departamento->numero }}?"
              data-swal-text="Los residentes no podrán ser asociados mientras la unidad permanezca inactiva."
              data-swal-danger="true"
              data-swal-button="Sí, desactivar"
            >
              @csrf
              @method('PATCH')
              <input type="hidden" name="estado" value="inactivo"/>
              <button
                type="submit"
                class="text-xs bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg font-semibold transition-colors flex items-center gap-1"
              >
                <span class="material-symbols-outlined text-[16px]">do_not_disturb_on</span>
                Desactivar unidad
              </button>
            </form>
          @else
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
              <span class="w-2 h-2 rounded-full bg-gray-400"></span>
              Departamento Inactivo
            </span>
            <form
              action="{{ route('admin.departamentos.estado', $departamento) }}"
              method="POST"
              data-swal-confirm="¿Activar el Departamento {{ $departamento->numero }}?"
              data-swal-text="La unidad volverá a quedar habilitada para asociar residentes."
              data-swal-button="Sí, activar"
            >
              @csrf
              @method('PATCH')
              <input type="hidden" name="estado" value="activo"/>
              <button
                type="submit"
                class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-semibold transition-colors flex items-center gap-1 shadow-sm"
              >
                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                Activar unidad
              </button>
            </form>
          @endif
        </div>
      </div>
    </div>

    @if (session('estado') || session('exito'))
      <div data-swal-feedback role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
        <span>{{ session('estado') ?? session('exito') }}</span>
      </div>
    @endif

    @if (session('error'))
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-900 p-4 text-sm flex items-center gap-3 shadow-sm">
        <span class="material-symbols-outlined text-red-600">error</span>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-900 p-4 text-sm shadow-sm">
        <div class="flex items-center gap-2 font-semibold mb-1">
          <span class="material-symbols-outlined text-red-600">error</span>
          <span>Por favor atiende las siguientes observaciones:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 ml-6 text-xs text-red-700">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (! $departamento->estaActivo())
      <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl shadow-sm flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-600 shrink-0 mt-0.5">warning</span>
        <div>
          <h2 class="text-sm font-bold text-amber-800">Restricción de asociación (HU-08)</h2>
          <p class="text-xs text-amber-700 mt-0.5">
            Este departamento se encuentra actualmente <strong>Inactivo</strong>. La plataforma no permite asociar nuevos residentes a departamentos inactivos. Si requieres vincular habitantes, activa el departamento primero usando el botón de la cabecera.
          </p>
        </div>
      </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
      <section aria-labelledby="residentes-heading" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-700">group</span>
            <h2 id="residentes-heading" class="text-lg font-bold text-gray-900">Residentes Asociados</h2>
          </div>
          <span class="text-xs bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-full">
            {{ $departamento->residentes->count() }} asignado(s)
          </span>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Asociar Nueva Cuenta de Residente</h3>

          @if ($departamento->estaActivo())
            @if ($residentesDisponibles->isNotEmpty())
              <form action="{{ route('admin.departamentos.residentes.asociar', $departamento) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                  <label for="residente_id" class="sr-only">Seleccionar Residente</label>
                  <select
                    id="residente_id"
                    name="residente_id"
                    required
                    class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-700"
                  >
                    <option value="">-- Selecciona un residente activo --</option>
                    @foreach ($residentesDisponibles as $disp)
                      <option value="{{ $disp->id }}">
                        {{ $disp->nombre }} ({{ $disp->rut ?? $disp->email }})
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="flex items-center justify-between gap-2">
                  <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 font-medium">
                    <input
                      type="checkbox"
                      name="es_titular"
                      value="1"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4"
                    />
                    <span>Designar como Titular Principal</span>
                  </label>

                  <button
                    type="submit"
                    class="bg-[#1e3a8a] hover:bg-blue-900 text-white text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1 transition-colors shadow-sm"
                  >
                    <span class="material-symbols-outlined text-[16px]">person_add</span>
                    <span>Asociar</span>
                  </button>
                </div>
              </form>
            @else
              <p class="text-xs text-gray-500">No hay residentes activos adicionales disponibles para asociar a esta unidad.</p>
            @endif
          @else
            <div class="text-xs text-gray-500 flex items-center gap-2">
              <span class="material-symbols-outlined text-gray-400 text-[18px]">lock</span>
              <span>Habilita el departamento para permitir la vinculación de cuentas.</span>
            </div>
          @endif
        </div>

        <div class="space-y-3">
          @forelse ($departamento->residentes as $residente)
            <div class="flex items-center justify-between p-3.5 rounded-lg border border-gray-200 bg-white hover:border-blue-200 transition-colors">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm text-gray-900 truncate">{{ $residente->nombre }}</span>
                  @if ($residente->pivot->es_titular)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                      Titular Principal
                    </span>
                  @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600">
                      Habitante
                    </span>
                  @endif
                </div>
                <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                  <span>{{ $residente->email }}</span>
                  @if ($residente->telefono)
                    <span>&bull; {{ $residente->telefono }}</span>
                  @endif
                  @if ($residente->rut)
                    <span>&bull; RUT: {{ $residente->rut }}</span>
                  @endif
                </div>
              </div>

              <form
                action="{{ route('admin.departamentos.residentes.desasociar', [$departamento, $residente]) }}"
                method="POST"
                class="ml-3 shrink-0"
                data-swal-confirm="¿Desasociar a {{ $residente->nombre }}?"
                data-swal-text="El usuario dejará de estar vinculado al Departamento {{ $departamento->numero }}."
                data-swal-danger="true"
                data-swal-button="Sí, desasociar"
              >
                @csrf
                @method('DELETE')
                <button
                  type="submit"
                  class="text-xs text-red-600 hover:text-red-800 p-1.5 rounded hover:bg-red-50 transition-colors"
                  title="Desasociar del departamento"
                >
                  <span class="material-symbols-outlined text-[18px]">person_remove</span>
                </button>
              </form>
            </div>
          @empty
            <div class="p-8 text-center border-2 border-dashed border-gray-200 rounded-lg text-gray-500 text-xs">
              <span class="material-symbols-outlined text-3xl text-gray-300 block mb-1">person_off</span>
              Aún no hay cuentas de residentes asociadas a esta unidad habitacional.
            </div>
          @endforelse
        </div>
      </section>

      <section aria-labelledby="contactos-heading" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-600">contact_phone</span>
            <h2 id="contactos-heading" class="text-lg font-bold text-gray-900">Contactos de Emergencia</h2>
          </div>
          <span class="text-xs bg-amber-50 text-amber-700 font-semibold px-2.5 py-1 rounded-full">
            {{ $departamento->contactosEmergencia->count() }} registrado(s)
          </span>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Registrar Contacto de Emergencia</h3>

          <form action="{{ route('admin.departamentos.contactos.store', $departamento) }}" method="POST" class="space-y-3">
            @csrf
            <div>
              <label for="nombre_contacto" class="block text-xs font-semibold text-gray-700 mb-1">
                Nombre Completo <span class="text-red-500">*</span>
              </label>
              <input
                id="nombre_contacto"
                name="nombre"
                type="text"
                value="{{ old('nombre') }}"
                required
                maxlength="150"
                placeholder="Ej.: Dra. Claudia Morales"
                class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label for="telefono_contacto" class="block text-xs font-semibold text-gray-700 mb-1">
                  Teléfono <span class="text-red-500">*</span>
                </label>
                <input
                  id="telefono_contacto"
                  name="telefono"
                  type="text"
                  value="{{ old('telefono') }}"
                  required
                  maxlength="30"
                  placeholder="Ej.: +56912345678"
                  class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white"
                />
              </div>

              <div>
                <label for="relacion_contacto" class="block text-xs font-semibold text-gray-700 mb-1">
                  Relación o Parentesco
                </label>
                <input
                  id="relacion_contacto"
                  name="relacion"
                  type="text"
                  value="{{ old('relacion') }}"
                  maxlength="100"
                  placeholder="Ej.: Madre, Propietario, Pareja"
                  class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white"
                />
              </div>
            </div>

            @if ($departamento->residentes->isNotEmpty())
              <div>
                <label for="user_id_contacto" class="block text-xs font-semibold text-gray-700 mb-1">
                  Residente Vinculado (Opcional)
                </label>
                <select
                  id="user_id_contacto"
                  name="user_id"
                  class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-700"
                >
                  <option value="">General (Asociado a todo el departamento)</option>
                  @foreach ($departamento->residentes as $residente)
                    <option value="{{ $residente->id }}">
                      {{ $residente->nombre }} ({{ $residente->pivot->es_titular ? 'Titular' : 'Habitante' }})
                    </option>
                  @endforeach
                </select>
              </div>
            @endif

            <button
              type="submit"
              class="w-full bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-4 py-2 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-sm"
            >
              <span class="material-symbols-outlined text-[16px]">add_call</span>
              <span>Guardar Contacto de Emergencia</span>
            </button>
          </form>
        </div>

        <div class="space-y-3">
          @forelse ($departamento->contactosEmergencia as $contacto)
            <div class="flex items-center justify-between p-3.5 rounded-lg border border-gray-200 bg-white hover:border-amber-200 transition-colors">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm text-gray-900 truncate">{{ $contacto->nombre }}</span>
                  @if ($contacto->relacion)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                      {{ $contacto->relacion }}
                    </span>
                  @endif
                </div>

                <div class="text-xs text-gray-600 mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                  <a href="tel:{{ $contacto->telefono }}" class="inline-flex items-center gap-1 text-blue-700 hover:underline font-semibold">
                    <span class="material-symbols-outlined text-[14px]">call</span>
                    <span>{{ $contacto->telefono }}</span>
                  </a>

                  @if ($contacto->residente)
                    <span class="text-gray-500">
                      &bull; Vinculado a: <strong>{{ $contacto->residente->nombre }}</strong>
                    </span>
                  @else
                    <span class="text-gray-400">&bull; Contacto General de Unidad</span>
                  @endif
                </div>
              </div>

              <form
                action="{{ route('admin.departamentos.contactos.destroy', [$departamento, $contacto]) }}"
                method="POST"
                class="ml-3 shrink-0"
                data-swal-confirm="¿Eliminar el contacto {{ $contacto->nombre }}?"
                data-swal-text="Esta acción eliminará el registro de contacto de emergencia de forma permanente."
                data-swal-danger="true"
                data-swal-button="Sí, eliminar"
              >
                @csrf
                @method('DELETE')
                <button
                  type="submit"
                  class="text-xs text-red-600 hover:text-red-800 p-1.5 rounded hover:bg-red-50 transition-colors"
                  title="Eliminar contacto de emergencia"
                >
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </form>
            </div>
          @empty
            <div class="p-8 text-center border-2 border-dashed border-gray-200 rounded-lg text-gray-500 text-xs">
              <span class="material-symbols-outlined text-3xl text-gray-300 block mb-1">contact_phone</span>
              No hay contactos de emergencia registrados para esta unidad residencial.
            </div>
          @endforelse
        </div>
      </section>
    </div>
</div>
@endsection
