<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Parametro;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class EmpleadoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $empleados = Empleado::with('cargo')->whereNull('deleted_at')->orderBy('apellido_paterno')->orderBy('nombre')->get();
        $cargos = Parametro::tipo('cargo_empleados')->whereNull('deleted_at')->orderBy('valor')->get();
        $idempotencyToken = $this->generarToken('empleado_store_token');
        return view('empleados.index', compact('empleados', 'cargos', 'idempotencyToken'));
    }

    public function nuevoToken()
    {
        return response()->json([
            'token' => $this->generarToken('empleado_store_token'),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('empleado_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('empleados.index');
        }
        $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'ci'               => 'required|string|max:20',
            'cargo_id'         => 'required|exists:parametros,id',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El apellido materno es obligatorio.',
            'ci.required'               => 'El CI es obligatorio.',
            'cargo_id.required'         => 'El cargo es obligatorio.',
            'cargo_id.exists'           => 'El cargo seleccionado no es válido.',
            'email.email'               => 'El correo no tiene un formato válido.',
        ]);

        Empleado::create([
            'nombre'           => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'ci'               => $request->ci ?: null,
            'cargo_id'         => $request->cargo_id,
            'telefono'         => $request->telefono ?: null,
            'email'            => $request->email ?: null,
            'activo'           => true,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        Alert::success('Éxito', 'Empleado registrado correctamente.');
        return redirect()->route('empleados.index');
    }

    public function update(Request $request, $uuid)
    {
        $empleado = Empleado::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'ci'               => 'required|string|max:20',
            'cargo_id'         => 'required|exists:parametros,id',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El apellido materno es obligatorio.',
            'ci.required'               => 'El CI es obligatorio.',
            'cargo_id.required'         => 'El cargo es obligatorio.',
            'cargo_id.exists'           => 'El cargo seleccionado no es válido.',
            'email.email'               => 'El correo no tiene un formato válido.',
        ]);

        $empleado->update([
            'nombre'           => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'ci'               => $request->ci ?: null,
            'cargo_id'         => $request->cargo_id,
            'telefono'         => $request->telefono ?: null,
            'email'            => $request->email ?: null,
            'updated_by'       => auth()->id(),
        ]);

        Alert::success('Éxito', 'Empleado actualizado correctamente.');
        return redirect()->route('empleados.index');
    }

    public function toggleActivo($uuid)
    {
        $empleado = Empleado::where('uuid', $uuid)->firstOrFail();

        // Cambiar estado del empleado
        $nuevoEstado = !$empleado->activo;
        $empleado->update([
            'activo'     => $nuevoEstado,
            'updated_by' => auth()->id(),
        ]);

        // Si el empleado tiene un usuario asociado, sincronizar el estado
        if ($empleado->usuario) {
            $empleado->usuario->update([
                'estado' => $nuevoEstado,
            ]);
        }

        $msg = $empleado->activo ? 'Empleado activado.' : 'Empleado desactivado.';

        // Agregar mensaje adicional si también se actualizó el usuario
        if ($empleado->usuario) {
            $msgUsuario = $nuevoEstado ? ' Su usuario del sistema también fue activado.' : ' Su usuario del sistema también fue desactivado.';
            $msg .= $msgUsuario;
        }

        Alert::success('Listo', $msg);
        return redirect()->route('empleados.index');
    }

    public function destroy($uuid)
    {
        $empleado = Empleado::where('uuid', $uuid)->firstOrFail();

        // Verificar si el empleado está siendo utilizado
        $verificacion = $empleado->verificarUso();

        if ($verificacion['enUso']) {
            Alert::warning('No se puede eliminar', $verificacion['mensaje']);
            return redirect()->route('empleados.index');
        }

        $empleado->delete();
        Alert::success('Éxito', 'Empleado eliminado.');
        return redirect()->route('empleados.index');
    }
}
