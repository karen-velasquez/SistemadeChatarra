<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
class MovimientoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }
    // Vista general de todas las empresas y cuentas
    public function index()
    {
        $empresas = Empresa::with(['cuentas'])->get();
        $totalIngresos = Movimiento::where('tipo', 'ingreso')->whereNull('deleted_at')->sum('monto_bolivianos');
        $totalEgresos  = Movimiento::where('tipo', 'egreso')->whereNull('deleted_at')->sum('monto_bolivianos');
        $saldoGeneral  = CuentaEmpresa::whereNull('deleted_at')->sum('saldo_actual');
        $ultimosMovimientos = Movimiento::withTrashed()->with(['cuentaEmpresa.empresa','cuentaEmpresa.banco','lotePago.cuentaOrigen'])->orderByDesc('fecha')->orderByDesc('id')->get();
        foreach ($ultimosMovimientos as $mov) {
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
        $idempotencyToken = $this->generarToken('movimiento_store_token');
        return view('movimientos.index', compact('empresas', 'totalIngresos', 'totalEgresos', 'saldoGeneral', 'ultimosMovimientos', 'idempotencyToken'));
    }
    // Movimientos de una cuenta específica
  
    public function porCuenta(string $uuid)
    {
        $cuenta = CuentaEmpresa::where('uuid', $uuid)->with(['empresa', 'banco'])->firstOrFail();
        $movimientos = Movimiento::withTrashed()->where('cuenta_empresa_id', $cuenta->id)->with(['cuentaEmpresa.empresa','cuentaEmpresa.banco','lotePago.cuentaOrigen'])->orderByDesc('fecha')->orderByDesc('id')->get();
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
        $totalIngresos = Movimiento::where('cuenta_empresa_id', $cuenta->id)->where('tipo', 'ingreso')->whereNull('deleted_at')->sum('monto_bolivianos');
        $totalEgresos  = Movimiento::where('cuenta_empresa_id', $cuenta->id)->where('tipo', 'egreso')->whereNull('deleted_at')->sum('monto_bolivianos');
        $idempotencyToken = $this->generarToken('movimiento_store_token');
        return view('movimientos.por_cuenta', compact('cuenta', 'movimientos', 'totalIngresos', 'totalEgresos', 'idempotencyToken'));
}
    // Registrar movimiento manual
    public function store(Request $request)
    {
        if (!$this->tokenValido('movimiento_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->back();
        }

        $request->validate([
            'cuenta_empresa_id' => 'required|exists:cuentas_empresa,id',
            'tipo'              => 'required|in:ingreso,egreso',
            'categoria'         => 'required|string',
            'monto'             => 'required|numeric|min:0.01',
            'moneda'            => 'required|string|max:10',
            'tipo_cambio'       => 'required|numeric|min:0.0001',
            'fecha'             => 'required|date',
            'concepto'          => 'required|string|max:255',
        ]);
        Movimiento::create([
            'cuenta_empresa_id' => $request->cuenta_empresa_id,
            'tipo'              => $request->tipo,
            'categoria'         => $request->categoria,
            'monto'             => $request->monto,
            'moneda'            => $request->moneda,
            'tipo_cambio'       => $request->tipo_cambio,
            'monto_bolivianos'  => $request->monto * $request->tipo_cambio,
            'fecha'             => $request->fecha,
            'concepto'          => $request->concepto,
            'observaciones'     => $request->observaciones,
            'created_by'        => auth()->id(),
            'updated_by'        => auth()->id(),
        ]);
        Alert::success('Guardado', 'Movimiento registrado correctamente.');
        return redirect()->back();
    }

    // Solo movimientos manuales (categoría "otro", sin origen de pago) son
    // editables desde aquí — mismo criterio que destroy().
    public function update(Request $request, string $uuid)
    {
        $movimiento = Movimiento::where('uuid', $uuid)->firstOrFail();

        if ($movimiento->origen_type || $movimiento->categoria !== 'otro') {
            Alert::error('No permitido', 'Solo los movimientos manuales (categoría "Otro") se pueden editar desde aquí.');
            return redirect()->back();
        }

        $request->validate([
            'monto'       => 'required|numeric|min:0.01',
            'tipo_cambio' => 'required|numeric|min:0.0001',
            'fecha'       => 'required|date',
            'concepto'    => 'required|string|max:255',
        ]);

        // El tipo (ingreso/egreso) no se edita: cambiarlo significaría revertir
        // el saldo en una dirección y aplicarlo en la otra, fuera del alcance
        // de una simple corrección de monto/fecha/concepto.
        $movimiento->update([
            'monto'         => $request->monto,
            'tipo_cambio'   => $request->tipo_cambio,
            'fecha'         => $request->fecha,
            'concepto'      => $request->concepto,
            'observaciones' => $request->observaciones,
            'updated_by'    => auth()->id(),
        ]);

        Alert::success('Actualizado', 'Movimiento actualizado correctamente.');
        return redirect()->back();
    }

    public function destroy(string $uuid)
    {
        $movimiento = Movimiento::where('uuid', $uuid)->firstOrFail();
        if ($movimiento->origen_type) {
            Alert::error('No permitido', 'Este movimiento fue generado automáticamente por un pago. Para eliminarlo, hazlo desde el módulo de pagos correspondiente.');
            return redirect()->back();
        }
        $movimiento->delete();
        Alert::success('Eliminado', 'Movimiento eliminado.');
        return redirect()->back();
    }
}
