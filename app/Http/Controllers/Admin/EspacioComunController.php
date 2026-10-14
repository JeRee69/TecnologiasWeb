<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EspacioComun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EspacioComunController extends Controller
{
    public function index(): View
    {
        return view('admin.espacios-comunes.index', [
            'espacios' => EspacioComun::orderByDesc('id')->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['bail', 'required', 'string', 'max:150', Rule::unique('espacios_comunes', 'nombre')],
            'descripcion' => ['bail', 'required', 'string', 'max:2000'],
            'estado' => ['bail', 'required', 'string', Rule::in(['activo', 'inactivo'])],
        ], [
            'nombre.required' => 'Ingresa el nombre del espacio.',
            'nombre.string' => 'Ingresa un nombre válido.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'nombre.unique' => 'Ya existe un espacio común con este nombre.',
            'descripcion.required' => 'Describe el espacio común.',
            'descripcion.string' => 'Ingresa una descripción válida.',
            'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
            'estado.required' => 'Selecciona el estado del espacio.',
            'estado.string' => 'Selecciona un estado válido.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        EspacioComun::create($datos);

        return redirect()->route('admin.espacios-comunes.index')
            ->with('estado', 'El espacio común fue registrado correctamente.');
    }

    public function configurar(EspacioComun $espacio): View
    {
        return view('admin.espacios-comunes.configurar', compact('espacio'));
    }

    public function actualizarEstado(Request $request, EspacioComun $espacio): RedirectResponse
    {
        $datos = $request->validateWithBag('estadoEspacio', [
            'estado' => ['bail', 'required', 'string', Rule::in(['activo', 'inactivo'])],
        ], [
            'estado.required' => 'Selecciona el estado del espacio.',
            'estado.string' => 'Selecciona un estado válido.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        $espacio->update($datos);

        $accion = $datos['estado'] === 'activo' ? 'activado' : 'desactivado';
        return redirect()->route('admin.espacios-comunes.index')
            ->with('estado', "El espacio {$espacio->nombre} fue {$accion} correctamente.");
    }

    public function guardarConfiguracion(Request $request, EspacioComun $espacio): RedirectResponse
    {
        $datos = $request->validate([
            'aforo' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'hora_apertura' => ['bail', 'required', 'date_format:H:i'],
            'hora_cierre' => ['bail', 'required', 'date_format:H:i'],
            'costo' => ['bail', 'required', 'integer', 'min:0', 'max:999999999999'],
            'reglas' => ['bail', 'required', 'string', 'max:5000'],
        ], [
            'aforo.required' => 'Ingresa el aforo del espacio.',
            'aforo.integer' => 'El aforo debe ser un número entero.',
            'aforo.min' => 'El aforo debe ser de al menos una persona.',
            'aforo.max' => 'El aforo no puede superar las 100000 personas.',
            'hora_apertura.required' => 'Selecciona la hora de apertura.',
            'hora_apertura.date_format' => 'Ingresa una hora de apertura válida (HH:MM).',
            'hora_cierre.required' => 'Selecciona la hora de cierre.',
            'hora_cierre.date_format' => 'Ingresa una hora de cierre válida (HH:MM).',
            'costo.required' => 'Ingresa el costo; usa 0 si el espacio es gratuito.',
            'costo.integer' => 'El costo debe ser un monto entero en pesos chilenos.',
            'costo.min' => 'El costo no puede ser negativo.',
            'costo.max' => 'El costo no puede superar los 999999999999 pesos.',
            'reglas.required' => 'Ingresa las reglas de uso del espacio.',
            'reglas.string' => 'Ingresa reglas de uso válidas.',
            'reglas.max' => 'Las reglas no pueden superar los 5000 caracteres.',
        ]);

        // Comparar únicamente después de validar ambos horarios (incluidos sus tipos).
        if (strcmp($datos['hora_cierre'], $datos['hora_apertura']) <= 0) {
            throw ValidationException::withMessages([
                'hora_cierre' => 'La hora de cierre debe ser posterior a la apertura dentro del mismo día.',
            ]);
        }

        $espacio->update($datos);

        return redirect()->route('admin.espacios-comunes.configurar', $espacio)
            ->with('estado', 'La configuración del espacio fue guardada correctamente.');
    }
}
