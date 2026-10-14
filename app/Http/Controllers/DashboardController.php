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

    public function residente(): View
    {
        $usuario = Auth::user();
        return view('dashboards.residente', compact('usuario'));
    }
}
