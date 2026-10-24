<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\GastoComun;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GastoComunController extends Controller
{
    public function index(Request $request)
    {
        $datos = $request->validate([
            'periodo' => ['nullable', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:9999-12'],
        ], ['periodo.date_format' => 'Selecciona un período mensual válido.']);

        $periodo = $datos['periodo'] ?? null;
        $gastos = GastoComun::with('departamento')->withCount('detalles')
            ->when($periodo, fn ($query) => $query->where('periodo', $periodo.'-01'))
            ->orderByDesc('periodo')->orderByDesc('id')->paginate(15)->withQueryString();
        $departamentos = Departamento::activos()->orderBy('piso')->orderBy('numero')->get();

        return response()->view('admin.gastos-comunes.index', compact('gastos', 'departamentos', 'periodo'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'departamento_id' => ['bail', 'required', 'integer', 'exists:departamentos,id'],
            'periodo' => ['bail', 'required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:9999-12'],
            'monto' => ['bail', 'required', 'integer', 'min:1', 'max:999999999999'],
        ], [
            'departamento_id.required' => 'Selecciona un departamento.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'departamento_id.integer' => 'Selecciona un departamento válido.',
            'periodo.required' => 'Selecciona el mes y año del cobro.',
            'periodo.date_format' => 'Selecciona un período mensual válido.',
            'periodo.after_or_equal' => 'El período debe ser enero de 1900 o posterior.',
            'periodo.before_or_equal' => 'El período no puede superar diciembre de 9999.',
            'monto.required' => 'Ingresa el monto del gasto común.',
            'monto.integer' => 'El monto debe ser un número entero en CLP.',
            'monto.min' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto no puede superar $999.999.999.999 CLP.',
        ]);

        try {
            DB::transaction(function () use ($datos) {
                // Serializa generaciones simultáneas del mismo departamento.
                $departamento = Departamento::whereKey($datos['departamento_id'])->lockForUpdate()->firstOrFail();
                if (! $departamento->estaActivo()) {
                    throw ValidationException::withMessages([
                        'departamento_id' => 'Selecciona un departamento activo para generar el cobro.',
                    ]);
                }
                if ($departamento->gastosComunes()->where('periodo', $datos['periodo'].'-01')->exists()) {
                    $this->duplicado();
                }
                $departamento->gastosComunes()->create([
                    'periodo' => $datos['periodo'].'-01',
                    'monto' => $datos['monto'],
                    'estado' => 'pendiente',
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $error) {
            $this->duplicado();
        }

        return redirect()->route('admin.gastos-comunes.index', ['periodo' => $datos['periodo']])
            ->with('estado', 'Gasto común generado correctamente con estado Pendiente.');
    }

    public function desglose(GastoComun $gasto)
    {
        $gasto->load(['departamento', 'detalles']);

        return response()->view('admin.gastos-comunes.desglose', compact('gasto'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function guardarDesglose(Request $request, GastoComun $gasto): RedirectResponse
    {
        $datos = $request->validate([
            'detalles' => ['required', 'array', 'min:1', 'max:50', 'list'],
            'detalles.*' => ['required', 'array'],
            'detalles.*.concepto' => ['bail', 'required', 'string', 'max:200'],
            'detalles.*.monto' => ['bail', 'required', 'integer', 'min:1', 'max:999999999999'],
        ], [
            'detalles.required' => 'Ingresa al menos un concepto para el desglose.',
            'detalles.array' => 'El desglose debe contener una lista de conceptos.',
            'detalles.list' => 'El desglose debe contener una lista ordenada de conceptos.',
            'detalles.max' => 'Puedes registrar como máximo 50 conceptos.',
            'detalles.*.array' => 'Cada detalle debe contener un concepto y un monto.',
            'detalles.*.concepto.required' => 'Ingresa el nombre de cada concepto.',
            'detalles.*.concepto.string' => 'El concepto debe ser texto.',
            'detalles.*.concepto.max' => 'Cada concepto admite hasta 200 caracteres.',
            'detalles.*.monto.required' => 'Ingresa el monto de cada concepto.',
            'detalles.*.monto.integer' => 'Los montos deben ser pesos enteros en CLP.',
            'detalles.*.monto.min' => 'Cada monto debe ser mayor que cero.',
            'detalles.*.monto.max' => 'Cada monto admite hasta $999.999.999.999 CLP.',
        ]);

        DB::transaction(function () use ($datos, $gasto) {
            $actual = GastoComun::whereKey($gasto->id)->lockForUpdate()->firstOrFail();
            // Suma entera en el servidor; nunca se confía en el total del navegador.
            $total = array_sum(array_map(fn ($detalle) => (int) $detalle['monto'], $datos['detalles']));
            if ($total !== $actual->monto) {
                throw ValidationException::withMessages([
                    'detalles' => 'La suma de los conceptos ($'.number_format($total, 0, ',', '.')
                        .') debe coincidir con el monto asignado ($'.number_format($actual->monto, 0, ',', '.').').',
                ]);
            }

            $actual->detalles()->delete();
            $actual->detalles()->createMany(array_map(fn ($detalle) => [
                'concepto' => $detalle['concepto'],
                'monto' => $detalle['monto'],
            ], $datos['detalles']));
        }, 3);

        return redirect()->route('admin.gastos-comunes.desglose', $gasto)
            ->with('estado', 'Desglose guardado correctamente. El total coincide con el monto asignado.');
    }

    private function duplicado(): never
    {
        throw ValidationException::withMessages([
            'periodo' => 'Ya existe un gasto común para este departamento y período. No se generó un cobro duplicado.',
        ]);
    }
}
