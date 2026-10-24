@extends('layouts.auth')
@section('title', 'Recuperar Contraseña')
@section('content')
<div class="relative w-full max-w-[480px] bg-white rounded-xl shadow-xl p-6 md:p-8 flex flex-col gap-5 border border-gray-100">
    <div class="flex flex-col items-center text-center">
      <div class="w-12 h-12 rounded-xl bg-[#091426] flex items-center justify-center text-white shadow-md mb-2">
        <span class="material-symbols-outlined text-[26px]">lock_reset</span>
      </div>
      <div class="flex items-center gap-1">
        <span class="font-bold text-xl tracking-tight text-[#1b1b1d]">EdiConnect</span>
        <span class="text-xs bg-[#d8e2ff] text-[#001a42] px-2 py-0.5 rounded-full font-semibold">Seguridad</span>
      </div>
      <h1 class="text-2xl font-bold text-[#1b1b1d] mt-3">Recuperar Contraseña</h1>
      <p class="text-sm text-gray-600 mt-1 max-w-xs">
        Ingresa tu correo electrónico registrado y te enviaremos un enlace con vigencia limitada para restablecer tu contraseña.
      </p>
    </div>

    @if (session('estado'))
      <div data-swal-feedback class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm flex flex-col gap-2">
        <div data-swal-message class="flex items-center gap-2">
          <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
          <span class="font-medium">{{ session('estado') }}</span>
        </div>
        @if (session('enlace_simulado'))
          <div class="mt-1 p-2 bg-white rounded border border-emerald-100 text-xs break-all">
            <span class="font-semibold block text-emerald-900 mb-1">Enlace generado para pruebas locales:</span>
            <a href="{{ session('enlace_simulado') }}" class="text-secondary underline font-medium hover:text-blue-800">
              {{ session('enlace_simulado') }}
            </a>
          </div>
        @endif
      </div>
    @endif

    @if ($errors->any())
      <div data-swal-feedback class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
        <div class="flex flex-col">
          @foreach ($errors->all() as $error)
            <span>{{ $error }}</span>
          @endforeach
        </div>
      </div>
    @endif

    <form class="flex flex-col gap-4" action="{{ route('password.email') }}" method="POST">
      @csrf
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[#1b1b1d]" for="email">
          Correo electrónico registrado
        </label>
        <div class="relative flex items-center">
          <span class="material-symbols-outlined absolute left-3 text-gray-400 text-[20px] pointer-events-none">
            mail
          </span>
          <input class="w-full bg-[#f5f3f4] text-[#1b1b1d] text-sm rounded-lg pl-10 pr-3 py-2.5 focus:outline-none focus:bg-white border border-transparent focus:border-secondary transition-colors"
                 id="email"
                 name="email"
                 type="email"
                 value="{{ old('email') }}"
                 placeholder="ejemplo@correo.cl"
                 required/>
        </div>
      </div>

      <button class="w-full bg-[#0058be] hover:bg-[#001a42] text-white font-semibold text-sm py-3 rounded-lg transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer mt-1"
              type="submit">
        <span>Enviar enlace de recuperación</span>
        <span class="material-symbols-outlined text-[18px]">send</span>
      </button>
    </form>

    <div class="text-center pt-2">
      <a href="{{ route('login') }}" class="text-sm font-semibold text-secondary hover:underline inline-flex items-center gap-1">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Volver a Iniciar Sesión
      </a>
    </div>
  </div>
@endsection
