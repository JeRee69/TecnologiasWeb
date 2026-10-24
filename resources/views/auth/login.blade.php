@extends('layouts.auth')
@section('title', 'Iniciar Sesión')
@section('content')
<main class="w-full bg-background">
    <div class="flex flex-col w-full">
      <div class="w-full flex items-center justify-center p-sm md:p-lg relative overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-secondary-fixed/30 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-surface-container-high/60 blur-3xl pointer-events-none"></div>
        <div class="relative w-full max-w-[440px] bg-surface-container-lowest rounded-xl shadow-xl p-md md:p-lg flex flex-col gap-md z-10">

          <div class="flex flex-col items-center text-center gap-xs">
            <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center text-on-primary shadow-md mb-xs">
              <span class="material-symbols-outlined text-[26px]">apartment</span>
            </div>
            <div class="flex items-center gap-xs">
              <span class="font-headline-md text-headline-md tracking-tight text-on-surface">EdiConnect</span>
              <span class="font-label-md text-label-md bg-secondary-fixed text-on-secondary-fixed px-xs py-0.5 rounded-full">Portal</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface mt-sm">Iniciar Sesión</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xs">
              Ingresa tus credenciales para acceder a EdiConnect
            </p>
          </div>

          {{-- Alertas de estado y mensajes de error según criterios de aceptación HU-01 --}}
          @if (session('estado'))
            <div data-swal-feedback class="p-sm bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-body-md flex items-center gap-xs">
              <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
              <span>{{ session('estado') }}</span>
            </div>
          @endif

          @if ($errors->any())
            <div data-swal-feedback class="p-sm bg-red-50 border border-red-200 text-red-700 rounded-lg text-body-md flex items-center gap-xs">
              <span class="material-symbols-outlined text-[20px] text-red-600">error</span>
              <div class="flex flex-col">
                @foreach ($errors->all() as $error)
                  <span>{{ $error }}</span>
                @endforeach
              </div>
            </div>
          @endif

          <form class="flex flex-col gap-md mt-xs" id="login-form" action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="flex flex-col gap-xs">
              <label class="font-label-md text-label-md text-on-surface" for="identifier">
                Correo electrónico o RUT
              </label>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-md text-outline text-[20px] pointer-events-none">
                  badge
                </span>
                <input class="w-full bg-surface-container-low text-on-surface font-body-md text-body-md rounded-lg pl-11 pr-md py-sm focus:outline-none focus:bg-surface-container-lowest transition-colors border border-transparent focus:border-secondary"
                       id="identifier"
                       name="identifier"
                       value="{{ old('identifier') }}"
                       placeholder="ejemplo@correo.cl o 12.345.678-9"
                       required
                       type="text"/>
              </div>
            </div>

            <div class="flex flex-col gap-xs">
              <div class="flex items-center justify-between">
                <label class="font-label-md text-label-md text-on-surface" for="password">
                  Contraseña
                </label>
                <a class="font-label-md text-label-md text-secondary hover:text-on-secondary-fixed transition-colors"
                   href="{{ route('password.request') }}">
                  ¿Olvidaste tu contraseña?
                </a>
              </div>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-md text-outline text-[20px] pointer-events-none">
                  lock
                </span>
                <input class="w-full bg-surface-container-low text-on-surface font-body-md text-body-md rounded-lg pl-11 pr-11 py-sm focus:outline-none focus:bg-surface-container-lowest transition-colors border border-transparent focus:border-secondary"
                       id="password"
                       name="password"
                       placeholder="••••••••••••"
                       required
                       type="password"/>
                <button aria-label="Ver u ocultar contraseña"
                        class="absolute right-md text-outline hover:text-on-surface flex items-center justify-center transition-colors"
                        id="toggle-password"
                        onclick="togglePasswordVisibility()"
                        type="button">
                  <span class="material-symbols-outlined text-[20px]" id="eye-icon">visibility</span>
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between pt-xs">
              <label class="flex items-center gap-sm cursor-pointer select-none">
                <input class="w-4 h-4 rounded text-secondary accent-secondary cursor-pointer"
                       id="remember"
                       name="remember"
                       type="checkbox"
                       {{ old('remember') ? 'checked' : '' }}/>
                <span class="font-body-md text-body-md text-on-surface-variant">
                  Recordarme en este equipo
                </span>
              </label>
            </div>

            <button class="w-full bg-secondary hover:bg-on-secondary-fixed text-on-secondary font-label-md text-label-md py-md rounded-lg transition-all flex items-center justify-center gap-xs shadow-sm mt-xs cursor-pointer"
                    id="submit-btn"
                    type="submit">
              <span>Ingresar a mi cuenta</span>
              <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </button>
          </form>

          <div class="pt-sm mt-xs flex flex-col items-center gap-xs text-center">
            <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-xs">
              <span class="material-symbols-outlined text-[16px] text-outline">support_agent</span>
              ¿Problemas para acceder?
              <span class="font-label-md text-label-md text-secondary">Contacta a la administración</span>
            </p>
            <span class="font-label-md text-label-md text-outline">
              © 2026 EdiConnect. Todos los derechos reservados.
            </span>
          </div>

        </div>
      </div>
    </div>

    <script>
      function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          eyeIcon.textContent = 'visibility_off';
        } else {
          passwordInput.type = 'password';
          eyeIcon.textContent = 'visibility';
        }
      }
    </script>
  </main>
@endsection
