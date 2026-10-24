<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        $usuario = Auth::user();
        return view('dashboards.admin', compact('usuario'));
    }

    public function conserje(): View
    {
        $usuario = Auth::user();
        return view('dashboards.conserje', compact('usuario'));
    }

    public function residente(): \Symfony\Component\HttpFoundation\Response
    {
        $usuario = Auth::user();
        $resumenGastos = \App\Models\GastoComun::resumenPara($usuario);
        return response()->view('dashboards.residente', compact('usuario', 'resumenGastos'))
            ->header('Cache-Control', 'private, no-store');
    }
}
