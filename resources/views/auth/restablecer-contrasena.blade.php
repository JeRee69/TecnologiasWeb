@extends('layouts.auth')
@section('title', 'Restablecer Contraseña')
@section('content')
  <div
    class="relative w-full max-w-[480px] bg-white rounded-xl shadow-xl p-6 md:p-8 flex flex-col gap-5 border border-gray-100">
    <div class="flex flex-col items-center text-center">
      <div class="w-12 h-12 rounded-xl bg-[#091426] flex items-center justify-center text-white shadow-md mb-2">
        <span class="material-symbols-outlined text-[26px]">key</span>
      </div>
      <div class="flex items-center gap-1">
        <span class="font-bold text-xl tracking-tight text-[#1b1b1d]">EdiConnect</span>
      </div>
      <h1 class="text-2xl font-bold text-[#1b1b1d] mt-3">Nueva Contraseña</h1>
      <p class="text-sm text-gray-600 mt-1 max-w-xs">
        Establece una nueva contraseña segura para tu cuenta asociada a <strong>{{ $email }}</strong>.
      </p>
    </div>

    @if ($errors->any())
      <div data-swal-feedback
        class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-red-600 text-[20px]">error</span>
        <div class="flex flex-col">
          @foreach ($errors->all() as $error)
            <span>{{ $error }}</span>
          @endforeach
        </div>
      </div>
    @endif

    <form class="flex flex-col gap-4" action="{{ route('password.update') }}" method="POST">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="hidden" name="email" value="{{ $email }}">

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[#1b1b1d]" for="password">
          Nueva Contraseña
        </label>
        <div class="relative flex items-center">
          <span class="material-symbols-outlined absolute left-3 text-gray-400 text-[20px] pointer-events-none">
            lock
          </span>
          <input
            class="w-full bg-[#f5f3f4] text-[#1b1b1d] text-sm rounded-lg pl-10 pr-3 py-2.5 focus:outline-none focus:bg-white border border-transparent focus:border-secondary transition-colors"
            id="password" name="password" type="password" placeholder="Mínimo 12 caracteres" minlength="12" required />
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[#1b1b1d]" for="password_confirmation">
          Confirmar Nueva Contraseña
        </label>
        <div class="relative flex items-center">
          <span class="material-symbols-outlined absolute left-3 text-gray-400 text-[20px] pointer-events-none">
            lock_clock
          </span>
          <input
            class="w-full bg-[#f5f3f4] text-[#1b1b1d] text-sm rounded-lg pl-10 pr-3 py-2.5 focus:outline-none focus:bg-white border border-transparent focus:border-secondary transition-colors"
            id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la contraseña"
            required />
        </div>
      </div>

      <button
        class="w-full bg-[#0058be] hover:bg-[#001a42] text-white font-semibold text-sm py-3 rounded-lg transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer mt-1"
        type="submit">
        <span>Actualizar contraseña</span>
        <span class="material-symbols-outlined text-[18px]">check_circle</span>
      </button>
    </form>
  </div>
@endsection