<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class MuralController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'prioridad' => ['nullable', 'string', Rule::in(array_keys(Aviso::PRIORIDADES))],
            'busqueda' => ['nullable', 'string', 'max:150'],
        ], ['busqueda.string' => 'La búsqueda debe ser texto.']);
        $filtroPrioridad = $filtros['prioridad'] ?? null;
        $busqueda = trim($filtros['busqueda'] ?? '');

        $query = Aviso::with('autor')->vigentes();

        if (!empty($filtroPrioridad) && in_array($filtroPrioridad, Aviso::PRIORIDADES, true)) {
            $query->where('prioridad', $filtroPrioridad);
        }

        if (!empty($busqueda)) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('titulo', 'like', "%{$busqueda}%")
                  ->orWhere('contenido', 'like', "%{$busqueda}%");
            });
        }

        $avisos = $query->prioridadOrdenada()->paginate(10)->withQueryString();

        return view('residente.mural.index', compact('avisos', 'filtroPrioridad', 'busqueda'));
    }
}
