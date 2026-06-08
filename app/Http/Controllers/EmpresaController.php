<?php

namespace App\Http\Controllers;
use App\Models\Banco;
use App\Models\Empresa;
use App\Models\Parametro;
use App\Models\Movimiento;
use App\Models\CuentaEmpresa;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
class EmpresaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $empresas = Empresa::withCount('cuentas')->with(['cuentas' => function($query) {$query->withCount('movimientos')->with('banco');}])->get();
        $bancos   = Banco::whereNull('deleted_at')->where('activo', true)->orderBy('nombre')->get();
        $monedas  = \App\Models\Parametro::where('tipo', 'tipo_moneda')->whereNull('deleted_at')->orderBy('valor')->get();
        return view('empresas.index', compact('empresas', 'bancos', 'monedas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:150',
            'nit'         => 'required|digits_between:1,15',
            'razon_social'=> 'required|string|max:200',
            'direccion'   => 'nullable|string|max:255',
            'telefono'    => 'nullable|digits_between:8,11',
            'email'       => 'nullable|email|max:100',
        ], [
            'nombre.required'      => 'El nombre de la empresa es obligatorio.',
            'nit.required'         => 'El NIT / RUC es obligatorio.',
            'nit.digits_between'   => 'El NIT / RUC debe contener solo números (máx. 15 dígitos).',
            'razon_social.required'=> 'La razón social es obligatoria.',
            'telefono.digits_between' => 'El teléfono debe contener entre 8 y 11 dígitos numéricos.',
            'email.email'          => 'Ingresa un correo electrónico válido.',
        ]);

        Empresa::create([
            'nombre'       => strtoupper($request->nombre),
            'nit'          => $request->nit,
            'razon_social' => $request->razon_social,
            'direccion'    => $request->direccion,
            'telefono'     => $request->telefono,
            'email'        => $request->email,
            'created_by'   => auth()->id(),
            'updated_by'   => auth()->id(),
        ]);
        Alert::success('Guardado', 'Empresa registrada correctamente.');
        return redirect()->route('empresas.index');
    }

    public function edit(string $uuid)
    {
        $empresa = Empresa::where('uuid', $uuid)->firstOrFail();
        return response()->json($empresa);
    }
    public function update(Request $request, string $uuid)
    {
        $empresa = Empresa::where('uuid', $uuid)->firstOrFail();
        $request->validate([
            'nombre'      => 'required|string|max:150',
            'nit'         => 'required|digits_between:1,15',
            'razon_social'=> 'required|string|max:200',
            'direccion'   => 'nullable|string|max:255',
            'telefono'    => 'nullable|digits_between:8,11',
            'email'       => 'nullable|email|max:100',
        ], [
            'nombre.required'      => 'El nombre de la empresa es obligatorio.',
            'nit.required'         => 'El NIT / RUC es obligatorio.',
            'nit.digits_between'   => 'El NIT / RUC debe contener solo números (máx. 15 dígitos).',
            'razon_social.required'=> 'La razón social es obligatoria.',
            'telefono.digits_between' => 'El teléfono debe contener entre 8 y 11 dígitos numéricos.',
            'email.email'          => 'Ingresa un correo electrónico válido.',
        ]);
        $empresa->update([
            'nombre'       => strtoupper($request->nombre),
            'nit'          => $request->nit,
            'razon_social' => $request->razon_social,
            'direccion'    => $request->direccion,
            'telefono'     => $request->telefono,
            'email'        => $request->email,
            'updated_by'   => auth()->id(),
        ]);
        Alert::success('Actualizado', 'Empresa actualizada correctamente.');
        return redirect()->route('empresas.index');
    }

    public function destroy(string $uuid)
    {
        $empresa = Empresa::where('uuid', $uuid)->firstOrFail();
        $empresa->delete();
        Alert::success('Eliminado', 'Empresa eliminada correctamente.');
        return redirect()->route('empresas.index');
    }

    public function cuentas(string $uuid)
    {
        $empresa = Empresa::where('uuid', $uuid)->with('cuentas.banco')->firstOrFail();
            $movimientos = Movimiento::withTrashed()->whereIn('cuenta_empresa_id', $empresa->cuentas->pluck('id'))->with(['cuentaEmpresa.empresa','cuentaEmpresa.banco','lotePago.cuentaOrigen'])->orderByDesc('fecha')->orderByDesc('id')->get();
            foreach ($movimientos as $mov) {
            if ($mov->origen) {
                if (method_exists($mov->origen, 'cuentaOrigen') && $mov->origen->cuentaOrigen) {
                    if (get_class($mov->origen->cuentaOrigen) === 'App\Models\CuentaBancaria') {
                        $mov->origen->cuentaOrigen->load('banco');
                    } elseif (get_class($mov->origen->cuentaOrigen) === 'App\Models\CuentaEmpresa') {
                        $mov->origen->cuentaOrigen->load('empresa');
                    }
                }

                if (method_exists($mov->origen, 'cuentaDestino') && $mov->origen->cuentaDestino) {
                    if (get_class($mov->origen->cuentaDestino) === 'App\Models\CuentaBancaria') {
                        $mov->origen->cuentaDestino->load('banco');
                    } elseif (get_class($mov->origen->cuentaDestino) === 'App\Models\CuentaEmpresa') {
                        $mov->origen->cuentaDestino->load('empresa');
                    }
                }
            }
        }
        $bancos = Banco::whereNull('deleted_at')->where('activo', true)->orderBy('nombre')->get();
        $monedas = Parametro::where('tipo', 'tipo_moneda')->whereNull('deleted_at')->orderBy('valor')->get();
        return view('empresas.cuentas', compact('empresa', 'movimientos', 'bancos', 'monedas'));
    }

    public function storeCuenta(Request $request, string $uuid)
    {
        $empresa = Empresa::where('uuid', $uuid)->firstOrFail();
        $request->validate([
            'nombre_cuenta' => 'required|string|max:150',
            'banco_id'      => 'required|exists:bancos,id',
            'numero_cuenta' => 'required|digits_between:1,20',
            'moneda'        => 'required|string|max:10',
            'saldo_inicial' => 'required|numeric|min:0',
        ], [
            'nombre_cuenta.required' => 'El nombre de la cuenta es obligatorio.',
            'banco_id.required'      => 'Debe seleccionar un banco.',
            'banco_id.exists'        => 'El banco seleccionado no existe.',
            'numero_cuenta.required' => 'El número de cuenta es obligatorio.',
            'numero_cuenta.digits_between' => 'El número de cuenta debe tener entre 1 y 20 dígitos numéricos.',
            'moneda.required'        => 'La moneda es obligatoria.',
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
        ]);

        CuentaEmpresa::create([
            'empresa_id'    => $empresa->id,
            'nombre_cuenta' => $request->nombre_cuenta,
            'banco_id'      => $request->banco_id,
            'numero_cuenta' => $request->numero_cuenta,
            'moneda'        => $request->moneda,
            'saldo_inicial' => $request->saldo_inicial,
            'saldo_actual'  => $request->saldo_inicial,
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);
        Alert::success('Guardado', 'Cuenta registrada correctamente.');
        if ($request->get('redirect_to') === 'index') {
            return redirect()->route('empresas.index');
        }
        return redirect()->route('empresas.cuentas', $uuid);
    }
}
