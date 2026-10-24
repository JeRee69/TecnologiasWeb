@extends('layouts.auth')
@section('title', 'Acceso No Autorizado (403)')
@section('content')
<div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center border border-gray-100">
    <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
      <span class="material-symbols-outlined text-3xl">block</span>
    </div>
    <span class="text-xs font-semibold bg-red-50 text-red-700 px-3 py-1 rounded-full uppercase tracking-wider">Error 403</span>
    <h1 class="text-2xl font-bold text-gray-900 mt-3 mb-2">Acceso No Autorizado</h1>
    <p class="text-sm text-gray-600 mb-6">
      {{ $mensaje ?? 'No tienes los permisos requeridos para acceder a este módulo del sistema.' }}
    </p>

    <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 mb-6">
      Tu rol actual en EdiConnect es: <strong class="text-gray-900">{{ $rolUsuario ?? 'Usuario' }}</strong>
    </div>

    <div class="flex flex-col gap-2">
      <a href="{{ route('login') }}" class="w-full bg-[#1e3a8a] hover:bg-[#172554] text-white font-semibold text-sm py-2.5 rounded-lg transition-colors inline-flex items-center justify-center gap-1">
        <span class="material-symbols-outlined text-[18px]">dashboard</span>
        Ir a mi Dashboard
      </a>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full text-xs text-gray-500 hover:text-gray-700 py-1 transition-colors">
          Cerrar sesión e ingresar con otra cuenta
        </button>
      </form>
    </div>
  </div>
@endsection
