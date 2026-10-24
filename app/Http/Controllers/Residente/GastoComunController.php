<?php

namespace App\Http\Controllers\Residente;

use App\Http\Controllers\Controller;
use App\Models\GastoComun;
use App\Services\GastoComunPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GastoComunController extends Controller
{
    public function descargarPdf(Request $request, int $gasto, GastoComunPdf $generador): Response
    {
        // Autoriza dentro de la consulta: un cobro ajeno y uno inexistente devuelven el mismo 404.
        $cobro = GastoComun::whereIn('departamento_id', $request->user()->departamentos()->select('departamentos.id'))
            ->with(['departamento', 'detalles'])->findOrFail($gasto);
        $nombre = 'gasto-comun-'.$cobro->periodo->format('Y-m').'-'.$cobro->id.'.pdf';

        return response($generador->generar($cobro), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function index(Request $request): Response
    {
        // La asociación vigente, incluso de unidades inactivas, define qué cobros puede consultar.
        $departamentos = $request->user()->departamentos()->orderBy('numero')->get();
        $gastos = GastoComun::with(['departamento', 'detalles'])
            ->whereIn('departamento_id', $departamentos->pluck('id'))
            ->orderByDesc('periodo')->orderByDesc('id')->paginate(10);

        $resumenGastos = GastoComun::resumenPara($request->user());
        return response()->view('residente.gastos-comunes.index', compact('departamentos', 'gastos', 'resumenGastos'))
            ->header('Cache-Control', 'private, no-store');
    }
}
