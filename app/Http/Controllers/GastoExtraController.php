<?php

namespace App\Http\Controllers;

use App\Models\GastoExtra;
use App\Models\Contrato;
use App\Models\Proveedor;
use App\Http\Requests\GastoExtraRequest;
use App\Models\CuentaBancaria;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;

class GastoExtraController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function index(Request $request)
    {
        $contratos = Contrato::whereNull('deleted_at')->get();
        $categorias = Parametro::where('tipo','categoria_gasto_extra')->get();
        $cuentas_banco = CuentaBancaria::where('tipo_titular','empleado')->get();
        $proveedores = Proveedor::whereNull('deleted_at')->orderBy('nombre')->get();
        $contratosFiltrados = Contrato::with('proveedor');

        if ($request->filled('proveedor_id')) {
            $contratosFiltrados->where('proveedor_id',$request->proveedor_id);
        }
        $contratosFiltrados = $contratosFiltrados->orderBy('created_at', 'desc')->get();
        foreach ($contratosFiltrados as $contrato) {
            $gastos = GastoExtra::where('contrato_id',$contrato->id)->whereNull('deleted_at')->orderBy('updated_at', 'desc')->get();
            $contrato->gastos_detalle = $gastos;
            $contrato->total_gastos = $gastos->sum('monto_bolivianos');
        }
        $total = GastoExtra::where('estado','PAGADO')->sum('monto_bolivianos');
        $pendientes = GastoExtra::where('estado','PENDIENTE')->sum('monto_bolivianos');
        $pagados = GastoExtra::where('estado','PAGADO')->sum('monto_bolivianos');
        $aduaneros = GastoExtra::where('estado','PAGADO')->where('categoria','ADUANERO')->sum('monto_bolivianos');
        $carga = GastoExtra::where('estado','PAGADO')->where('categoria','CARGUIO')->sum('monto_bolivianos');
        $otros = 0;
        $idempotencyToken = $this->generarToken('gasto_extra_store_token');
        return view('gastos_extras.index',compact('contratos','categorias','cuentas_banco','proveedores','contratosFiltrados','total','pendientes','pagados','aduaneros','carga','otros','idempotencyToken'));
    }
    public function store(GastoExtraRequest $request)
    {
        if (!$this->tokenValido('gasto_extra_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('gastos_extras.index');
        }
        $cuenta = CuentaBancaria::findOrFail($request->cuenta_bancaria_id);
        $monedaEsBob = $request->moneda === 'BOB';
        $tipoCambioVacio = empty($request->tipo_cambio);
        if (
            ($monedaEsBob && !$tipoCambioVacio) ||
            (!$monedaEsBob && $tipoCambioVacio)
        ) {
            Alert::error('Error','No puedes realizar el registro, datos inconsistentes');
            return redirect()->route('gastos_extras.index');
        }
        $categoria = strtoupper(trim($request->categoria));
        if ($categoria === 'OTRO') {
            $categoria = strtoupper(trim($request->nueva_categoria));
        }
        $categoriaExiste = Parametro::where('tipo','categoria_gasto_extra')->where('descripcion', $categoria)->first();
        $categoriaParametro = Parametro::where('tipo','categoria_gasto_extra')->whereRaw('UPPER(TRIM(descripcion)) = ?',[$categoria])->first();

    if (!$categoriaParametro) {
        $categoriaParametro = new Parametro();
        $categoriaParametro->tipo = 'categoria_gasto_extra';
        $categoriaParametro->descripcion = $categoria;
        $categoriaParametro->save();
    }
    $categoria = $categoriaParametro->descripcion;
        $nombreComprobante = '';
        if ($request->hasFile('comprobante_pago')) {
            $path = public_path('comprobantes_pago');
            $doc = $request->file('comprobante_pago');
            $name = 'comprobante_' .$categoria . '_' .strtoupper($request->concepto) . '_' .Carbon::now()->format('YmdHis');
            $nombreComprobante =$name . '.' . $doc->extension();
            $doc->move($path, $nombreComprobante);
        }
        $montoBolivianos = $monedaEsBob ? $request->monto : $request->monto * $request->tipo_cambio;
        $gasto = new GastoExtra();
        $gasto->contrato_id = $request->contrato_id;
        $gasto->cuenta_bancaria_id = $request->cuenta_bancaria_id;
        $gasto->categoria = $categoria;
        $gasto->concepto = strtoupper(trim($request->concepto));
        $gasto->fecha = $request->fecha;
        $gasto->monto = $request->monto;
        $gasto->moneda = $request->moneda;
        $gasto->monto_bolivianos = $montoBolivianos;
        $gasto->tipo_cambio = $monedaEsBob ? null : $request->tipo_cambio;
        $gasto->comprobante_pago = $nombreComprobante;
        $gasto->metodo_pago = $request->metodo_pago;
        $gasto->nombre_titular = $request->nombre_titular; 
        $gasto->estado = $request->estado;
        $gasto->save();
        Alert::success('Registrado','Gasto Extra registrado con éxito');
        return redirect()->route('gastos_extras.index');
    }
    public function show($id)
    {
        return GastoExtra::with(['contrato', 'cuentaBancaria'])->findOrFail($id);
    }

    public function update(GastoExtraRequest $request, $uuid)
    {
         $gasto = GastoExtra::where('uuid', $uuid)->firstOrFail();
        $cuenta = CuentaBancaria::findOrFail($request->cuenta_bancaria_id);
        $monedaEsBob = $request->moneda === 'BOB';
        $tipoCambioVacio = empty($request->tipo_cambio);
        if (
            ($monedaEsBob && !$tipoCambioVacio) ||
            (!$monedaEsBob && $tipoCambioVacio)
        ) {
            Alert::error('Error','No puedes realizar la actualización, datos inconsistentes');
            return redirect()->route('gastos_extras.index');
        }
        $categoria = strtoupper(trim($request->categoria));
        if ($categoria === 'OTRO') {
            $categoria = strtoupper(trim($request->nueva_categoria));
        }
        $categoriaParametro = Parametro::where('tipo','categoria_gasto_extra')->whereRaw('UPPER(TRIM(descripcion)) = ?',[$categoria])->first();
        if (!$categoriaParametro) {
            $categoriaParametro = new Parametro();
            $categoriaParametro->tipo ='categoria_gasto_extra';
            $categoriaParametro->descripcion =$categoria;
            $categoriaParametro->save();
        }
        $categoria = $categoriaParametro->descripcion;
        $nombreComprobante = $gasto->comprobante_pago;
        if ($request->hasFile('comprobante_pago')) {
            if (!empty($gasto->comprobante_pago) && file_exists(public_path('comprobantes_pago/' .$gasto->comprobante_pago))) {
                unlink(
                    public_path('comprobantes_pago/' .$gasto->comprobante_pago));
            }

            $path = public_path('comprobantes_pago');
            $doc = $request->file('comprobante_pago');
            $name = 'comprobante_' .$categoria . '_' .strtoupper($request->concepto) . '_' .Carbon::now()->format('YmdHis');
            $nombreComprobante =$name . '.' . $doc->extension();
            $doc->move($path, $nombreComprobante);
        }
        $montoBolivianos = $monedaEsBob ? $request->monto : $request->monto * $request->tipo_cambio;
        $gasto->contrato_id =$request->contrato_id;
        $gasto->cuenta_bancaria_id =$request->cuenta_bancaria_id;
        $gasto->categoria = $categoria;
        $gasto->concepto = strtoupper(trim($request->concepto));
        $gasto->fecha = $request->fecha;
        $gasto->monto = $request->monto;
        $gasto->moneda = $request->moneda;
        $gasto->monto_bolivianos =$montoBolivianos;
        $gasto->tipo_cambio = $monedaEsBob ? null : $request->tipo_cambio;
        $gasto->estado = $request->estado;
        $gasto->metodo_pago = $request->metodo_pago;
        $gasto->comprobante_pago = $nombreComprobante;
        $gasto->nombre_titular = $request->nombre_titular;
        $gasto->save();
        Alert::success('Actualizado','Gasto Extra actualizado con éxito');
        return redirect()->route('gastos_extras.index');
    }

    public function destroy($uuid)
    {
        $gasto = GastoExtra::where('uuid',$uuid)->firstOrFail();
        if ($gasto->estado === 'PAGADO') {
             Alert::error('Error', 'No puedes eliminar un gasto pagado.');
            return redirect()->route('gastos_extras.index');
        }
        $gasto->delete();
        Alert::success('Eliminacion ', 'Gasto Extra Eliminado con exito!!!');
        return redirect()->route('gastos_extras.index');       
    }
}