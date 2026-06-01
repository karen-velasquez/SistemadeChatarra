<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\Empleado;
use App\Models\Parametro;
use App\Models\Proveedor;
use App\Models\OperadorTransporte;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class BancoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $bancos      = Banco::with('pais')->withCount('cuentas')->whereNull('deleted_at')->orderBy('pais_id')->orderBy('nombre')->get();
        $proveedores = Proveedor::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();
        $operadores  = OperadorTransporte::with(['ciPais', 'licenciaPais'])->whereNull('deleted_at')->orderBy('nombre')->get();
        $empleados   = Empleado::with('cargo')->whereNull('deleted_at')->where('activo', true)->orderBy('apellido_paterno')->orderBy('nombre')->get();
        $clientes    = Cliente::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();
        $sucursales  = Parametro::where('tipo', 'sucursal_cuenta')->whereNull('deleted_at')->orderBy('descripcion')->get();
        $paises      = Parametro::where('tipo', 'paises')->whereNull('deleted_at')->orderBy('valor')->get();

        // Relaciones para titular diferente
        $relacionesEmpleado   = Parametro::where('tipo', 'relacion_titular_empleado')->whereNull('deleted_at')->orderBy('valor')->get();
        $relacionesProveedor  = Parametro::where('tipo', 'relacion_titular_proveedor')->whereNull('deleted_at')->orderBy('valor')->get();
        $relacionesCliente    = Parametro::where('tipo', 'relacion_titular_cliente')->whereNull('deleted_at')->orderBy('valor')->get();
        $relacionesOperador   = Parametro::where('tipo', 'relacion_titular_propietario_conductor')->whereNull('deleted_at')->orderBy('valor')->get();

        return view('bancos.index', compact('bancos', 'proveedores', 'operadores', 'empleados', 'clientes', 'sucursales', 'paises',
            'relacionesEmpleado', 'relacionesProveedor', 'relacionesCliente', 'relacionesOperador'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'       => 'required|string|max:150',
            'pais_id'      => 'required|exists:parametros,id',
            'codigo_swift' => 'nullable|string|max:20',
            'codigo_banco' => 'nullable|string|max:10',
        ], [
            'nombre.required'  => 'El nombre del banco es obligatorio.',
            'pais_id.required' => 'El país del banco es obligatorio.',
            'pais_id.exists'   => 'El país seleccionado no existe.',
        ]);

        Banco::create([
            'nombre'       => strtoupper($request->nombre),
            'pais_id'      => $request->pais_id,
            'codigo_swift' => $request->codigo_swift ? strtoupper($request->codigo_swift) : null,
            'codigo_banco' => $request->codigo_banco ?: null,
            'activo'       => true,
            'created_by'   => auth()->id(),
            'updated_by'   => auth()->id(),
        ]);

        Alert::success('Éxito', 'Banco registrado correctamente.');
        return redirect()->route('bancos.index');
    }

    public function update(Request $request, $uuid)
    {
        $banco = Banco::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'nombre'       => 'required|string|max:150',
            'pais_id'      => 'required|exists:parametros,id',
            'codigo_swift' => 'nullable|string|max:20',
            'codigo_banco' => 'nullable|string|max:10',
        ]);

        $banco->update([
            'nombre'       => strtoupper($request->nombre),
            'pais_id'      => $request->pais_id,
            'codigo_swift' => $request->codigo_swift ? strtoupper($request->codigo_swift) : null,
            'codigo_banco' => $request->codigo_banco ?: null,
            'updated_by'   => auth()->id(),
        ]);

        Alert::success('Éxito', 'Banco actualizado correctamente.');
        return redirect()->route('bancos.index');
    }

    public function destroy($uuid)
    {
        $banco = Banco::where('uuid', $uuid)->firstOrFail();

        if ($banco->cuentas()->whereNull('deleted_at')->exists()) {
            Alert::error('No permitido', 'No se puede eliminar un banco que tiene cuentas registradas.');
            return redirect()->route('bancos.index');
        }

        $banco->delete();
        Alert::success('Éxito', 'Banco eliminado.');
        return redirect()->route('bancos.index');
    }

    // Cuentas bancarias
    public function storeCuenta(Request $request)
    {
        $request->validate([
            'banco_id'      => 'required|exists:bancos,id',
            'tipo_titular'  => 'required|in:proveedor,operador,empleado,cliente',
            'titular_id'    => 'nullable|integer',
            'numero_cuenta' => 'required|string|max:100',
            'moneda'        => 'required|string|max:10',
            'alias'                    => 'nullable|string|max:150',
            'nombre_titular'           => 'nullable|string|max:100',
            'apellido_paterno_titular' => 'nullable|string|max:100',
            'apellido_materno_titular' => 'nullable|string|max:100',
            'nro_documento'            => 'required|digits_between:1,20',
            'email_notificacion'       => 'nullable|email|max:150',
            'sucursal_departamento'    => 'nullable|string|max:50',
            'tipo_relacion'            => 'nullable|string|max:50',
        ], [
            'banco_id.required'      => 'Debe seleccionar un banco.',
            'tipo_titular.required'  => 'Debe indicar el tipo de titular.',
            'numero_cuenta.required' => 'El número de cuenta es obligatorio.',
            'moneda.required'        => 'La moneda es obligatoria.',
            'nro_documento.required' => 'El CI/NIT es obligatorio.',
            'nro_documento.digits_between' => 'El CI/NIT debe contener solo números (máx. 20 dígitos).',
            'email_notificacion.email'     => 'El correo de notificación no tiene un formato válido.',
        ]);

        $titularType = match($request->tipo_titular) {
            'proveedor' => 'App\Models\Proveedor',
            'operador'  => 'App\Models\OperadorTransporte',
            'empleado'  => 'App\Models\Empleado',
            'cliente'   => 'App\Models\Cliente',
            default     => null,
        };

        // Verificar si el banco es de Bolivia
        $banco = Banco::with('pais')->find($request->banco_id);
        $sucursalDepartamento = ($banco && $banco->pais && $banco->pais->valor === 'BOLIVIA') ? $request->sucursal_departamento : null;

        // Generar alias automáticamente si no se proporciona
        $alias = $request->alias;
        if (empty($alias)) {
            $nombreTitular = '';
            $prefijoTipo = '';

            // Definir prefijo según tipo de titular
            switch ($request->tipo_titular) {
                case 'proveedor':
                    $prefijoTipo = 'CUENTA PRINCIPAL';
                    break;
                case 'operador':
                    $prefijoTipo = 'CUENTA FLETE';
                    break;
                case 'empleado':
                    $prefijoTipo = 'CUENTA SUELDO';
                    break;
                case 'cliente':
                    $prefijoTipo = 'CUENTA PRINCIPAL';
                    break;
            }

            // Obtener nombre del titular
            if ($request->titular_id && $titularType) {
                $titular = $titularType::find($request->titular_id);
                if ($titular) {
                    // Proveedor y Cliente solo tienen 'nombre'
                    if (in_array($request->tipo_titular, ['proveedor', 'cliente'])) {
                        $nombreTitular = $titular->nombre;
                    }
                    // Operador y Empleado tienen apellidos
                    else {
                        $nombreTitular = $titular->apellido_paterno . ' ' . $titular->nombre;
                    }
                }
            } else {
                // Usar titular diferente si existe
                if ($request->nombre_titular) {
                    if ($request->apellido_paterno_titular) {
                        $nombreTitular = $request->apellido_paterno_titular . ' ' . $request->nombre_titular;
                    } else {
                        $nombreTitular = $request->nombre_titular;
                    }
                }
            }

            // Formato: "CUENTA [TIPO] [NOMBRE_TITULAR]"
            $alias = strtoupper(
                $prefijoTipo . ' ' . ($nombreTitular ?: 'SIN NOMBRE')
            );
        }

        CuentaBancaria::create([
            'banco_id'      => $request->banco_id,
            'tipo_titular'  => $request->tipo_titular,
            'titular_id'    => $request->titular_id ?: null,
            'titular_type'  => $titularType,
            'numero_cuenta'            => $request->numero_cuenta,
            'moneda'                   => $request->moneda,
            'alias'                    => $alias,
            'nombre_titular'           => $request->nombre_titular ?: null,
            'apellido_paterno_titular' => $request->apellido_paterno_titular ?: null,
            'apellido_materno_titular' => $request->apellido_materno_titular ?: null,
            'nro_documento'            => $request->nro_documento ?: null,
            'email_notificacion'       => $request->email_notificacion ?: null,
            'sucursal_departamento'    => $sucursalDepartamento,
            'tipo_relacion'            => $request->tipo_relacion ?: null,
            'activo'        => true,
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        Alert::success('Éxito', 'Cuenta bancaria registrada correctamente.');
        return redirect()->route('bancos.index');
    }

    public function updateCuenta(Request $request, $uuid)
    {
        $cuenta = CuentaBancaria::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'banco_id'      => 'required|exists:bancos,id',
            'tipo_titular'  => 'required|in:proveedor,operador,empleado,cliente',
            'titular_id'    => 'nullable|integer',
            'numero_cuenta' => 'required|string|max:100',
            'moneda'        => 'required|string|max:10',
            'alias'                    => 'nullable|string|max:150',
            'nombre_titular'           => 'nullable|string|max:100',
            'apellido_paterno_titular' => 'nullable|string|max:100',
            'apellido_materno_titular' => 'nullable|string|max:100',
            'nro_documento'            => 'required|digits_between:1,20',
            'email_notificacion'       => 'nullable|email|max:150',
            'sucursal_departamento'    => 'nullable|string|max:50',
            'tipo_relacion'            => 'nullable|string|max:50',
        ], [
            'banco_id.required'      => 'Debe seleccionar un banco.',
            'tipo_titular.required'  => 'Debe indicar el tipo de titular.',
            'numero_cuenta.required' => 'El número de cuenta es obligatorio.',
            'moneda.required'        => 'La moneda es obligatoria.',
            'nro_documento.required' => 'El CI/NIT es obligatorio.',
            'nro_documento.digits_between' => 'El CI/NIT debe contener solo números (máx. 20 dígitos).',
            'email_notificacion.email'     => 'El correo de notificación no tiene un formato válido.',
        ]);

        $titularType = match($request->tipo_titular) {
            'proveedor' => 'App\Models\Proveedor',
            'operador'  => 'App\Models\OperadorTransporte',
            'empleado'  => 'App\Models\Empleado',
            'cliente'   => 'App\Models\Cliente',
            default     => null,
        };

        // Verificar si el banco es de Bolivia
        $banco = Banco::with('pais')->find($request->banco_id);
        $sucursalDepartamento = ($banco && $banco->pais && $banco->pais->valor === 'BOLIVIA') ? $request->sucursal_departamento : null;

        // Generar alias automáticamente si no se proporciona
        $alias = $request->alias;
        if (empty($alias)) {
            $nombreTitular = '';
            $prefijoTipo = '';

            // Definir prefijo según tipo de titular
            switch ($request->tipo_titular) {
                case 'proveedor':
                    $prefijoTipo = 'CUENTA PRINCIPAL';
                    break;
                case 'operador':
                    $prefijoTipo = 'CUENTA FLETE';
                    break;
                case 'empleado':
                    $prefijoTipo = 'CUENTA SUELDO';
                    break;
                case 'cliente':
                    $prefijoTipo = 'CUENTA PRINCIPAL';
                    break;
            }

            // Obtener nombre del titular
            if ($request->titular_id && $titularType) {
                $titular = $titularType::find($request->titular_id);
                if ($titular) {
                    // Proveedor y Cliente solo tienen 'nombre'
                    if (in_array($request->tipo_titular, ['proveedor', 'cliente'])) {
                        $nombreTitular = $titular->nombre;
                    }
                    // Operador y Empleado tienen apellidos
                    else {
                        $nombreTitular = $titular->apellido_paterno . ' ' . $titular->nombre;
                    }
                }
            } else {
                // Usar titular diferente si existe
                if ($request->nombre_titular) {
                    if ($request->apellido_paterno_titular) {
                        $nombreTitular = $request->apellido_paterno_titular . ' ' . $request->nombre_titular;
                    } else {
                        $nombreTitular = $request->nombre_titular;
                    }
                }
            }

            // Formato: "CUENTA [TIPO] [NOMBRE_TITULAR]"
            $alias = strtoupper(
                $prefijoTipo . ' ' . ($nombreTitular ?: 'SIN NOMBRE')
            );
        }

        $cuenta->update([
            'banco_id'      => $request->banco_id,
            'tipo_titular'  => $request->tipo_titular,
            'titular_id'    => $request->titular_id ?: null,
            'titular_type'  => $titularType,
            'numero_cuenta'            => $request->numero_cuenta,
            'moneda'                   => $request->moneda,
            'alias'                    => $alias,
            'nombre_titular'           => $request->nombre_titular ?: null,
            'apellido_paterno_titular' => $request->apellido_paterno_titular ?: null,
            'apellido_materno_titular' => $request->apellido_materno_titular ?: null,
            'nro_documento'            => $request->nro_documento ?: null,
            'email_notificacion'       => $request->email_notificacion ?: null,
            'sucursal_departamento'    => $sucursalDepartamento,
            'tipo_relacion'            => $request->tipo_relacion ?: null,
            'updated_by'    => auth()->id(),
        ]);

        Alert::success('Éxito', 'Cuenta bancaria actualizada correctamente.');
        return redirect()->route('bancos.index');
    }

    public function destroyCuenta($uuid)
    {
        $cuenta = CuentaBancaria::where('uuid', $uuid)->firstOrFail();
        $cuenta->delete();
        Alert::success('Éxito', 'Cuenta bancaria eliminada.');
        return redirect()->route('bancos.index');
    }
}
