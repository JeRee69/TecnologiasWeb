<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\Encomienda;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EncomiendaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        // La asociación vigente conserva la consulta de paquetes ya recibidos,
        // incluso si la unidad dejó de admitir nuevos registros.
        $departamentos = $usuario->departamentos()
            ->orderBy('numero')
            ->get();

        $departamentosIds = $departamentos->pluck('id');

        // Encomiendas pendientes asociadas exclusivamente a sus departamentos
        $encomiendasPendientes = Encomienda::with(['departamento', 'conserje'])
            ->whereIn('departamento_id', $departamentosIds)
            ->pendientes()
            ->orderBy('fecha_ingreso', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('residente.encomiendas.index', [
            'usuario' => $usuario,
            'departamentos' => $departamentos,
            'encomiendas' => $encomiendasPendientes,
        ]);
    }
}
