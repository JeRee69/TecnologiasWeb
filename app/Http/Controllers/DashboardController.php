<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Departamento;
use App\Models\User;
use App\Models\Reserva;
use App\Models\Encomienda;
use App\Models\Visita;
use App\Models\VehiculoAutorizado;
use App\Models\GastoComun;

class DashboardController extends Controller
{
    public function admin(): \Symfony\Component\HttpFoundation\Response
    {
        $usuario = Auth::user();
        $metricas = ['departamentos' => Departamento::count(), 'usuarios' => User::where('estado', 'activo')->count(),
            'reservas' => Reserva::where('estado', 'pendiente')->count(), 'cobros' => GastoComun::where('estado', '!=', 'pagado')->count()];
        return response()->view('dashboards.admin', compact('usuario', 'metricas'))->header('Cache-Control', 'private, no-store');
    }

    public function conserje(): \Symfony\Component\HttpFoundation\Response
    {
        $usuario = Auth::user();
        $metricas = ['encomiendas' => Encomienda::pendientes()->count(), 'visitas' => Visita::enRecinto()->count(),
            'vehiculos' => VehiculoAutorizado::autorizados()->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', today()->toDateString()))->count(),
            'departamentos' => Departamento::activos()->count()];
        $encomiendasRecientes = Encomienda::with('departamento')->pendientes()->orderByDesc('fecha_ingreso')->limit(4)->get();
        return response()->view('dashboards.conserje', compact('usuario', 'metricas', 'encomiendasRecientes'))->header('Cache-Control', 'private, no-store');
    }

    public function residente(): \Symfony\Component\HttpFoundation\Response
    {
        $usuario = Auth::user();
        $resumenGastos = \App\Models\GastoComun::resumenPara($usuario);
        $departamentos = $usuario->departamentos()->orderBy('numero')->get();
        $cobros = GastoComun::with('departamento')->whereIn('departamento_id', $departamentos->pluck('id'));
        $cobroDestacado = (clone $cobros)->where('estado', '!=', 'pagado')->orderByDesc('periodo')->first()
            ?? (clone $cobros)->orderByDesc('periodo')->first();
        $ultimoPago = (clone $cobros)->where('estado', 'pagado')->orderByDesc('periodo')->first();
        $paquetes = Encomienda::with('departamento')->pendientes()->whereIn('departamento_id', $departamentos->where('estado', 'activo')->pluck('id'));
        $totalEncomiendas = (clone $paquetes)->count();
        $encomiendas = $paquetes->orderByDesc('fecha_ingreso')->limit(2)->get();
        $proximaReserva = Reserva::with('espacioComun')->where('user_id', $usuario->id)->vigentes()
            ->where(fn ($query) => $query->where('fecha', '>', today()->toDateString())
                ->orWhere(fn ($q) => $q->where('fecha', today()->toDateString())->where('hora_inicio', '>', now()->format('H:i:s'))))
            ->orderBy('fecha')->orderBy('hora_inicio')->first();
        return response()->view('dashboards.residente', compact('usuario', 'resumenGastos', 'departamentos', 'cobroDestacado', 'ultimoPago', 'encomiendas', 'totalEncomiendas', 'proximaReserva'))
            ->header('Cache-Control', 'private, no-store');
    }
}
