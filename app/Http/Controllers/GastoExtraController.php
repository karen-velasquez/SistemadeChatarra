<?php

namespace App\Http\Controllers;

use App\Models\GastoExtra;
use App\Models\Contrato;
use App\Models\Proveedor;
use App\Http\Requests\GastoExtraRequest;
use App\Models\CuentaBancaria;
use App\Models\CuentaEmpresa;
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
        $proveedores = Proveedor::whereNull('deleted_at')->orderBy('nombre')->get();

        // Incluye también las cuentas ya usadas por algún gasto aunque estén
        // inactivas: si no, el <select> del modal de edición las pierde y el
        // formulario falla la validación "required" al guardar sin mostrarlo.
        $idsCuentasUsadas = GastoExtra::whereNull('deleted_at')->pluck('cuenta_empresa_id')->filter();
        $cuentasEmpresa = CuentaEmpresa::whereNull('deleted_at')
            ->where(fn($q) => $q->where('activo', true)->orWhereIn('id', $idsCuentasUsadas))
            ->orderBy('nombre_cuenta')->get();

        $contratosParaFiltro = Contrato::with('proveedor')->whereNull('deleted_at')
            ->whereHas('gastosExtras', fn($q) => $q->whereNull('deleted_at'))
            ->orderBy('numero_contrato')->get();

        $gastos = GastoExtra::with('contrato.proveedor')
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $gastosJson = $gastos->map(fn($g) => [
            'uuid'                => $g->uuid,
            'id'                  => $g->id,
            'contrato_id'         => $g->contrato_id,
            'contrato_numero'     => $g->contrato->numero_contrato ?? 'Sin contrato',
            'proveedor_nombre'    => $g->contrato?->proveedor->nombre ?? '-',
            'cuenta_empresa_id'   => $g->cuenta_empresa_id,
            'fecha'               => $g->fecha,
            'categoria'           => $g->categoria,
            'concepto'            => $g->concepto,
            'moneda'              => $g->moneda,
            'monto'               => $g->monto,
            'tipo_cambio'         => $g->tipo_cambio,
            'monto_bolivianos'    => $g->monto_bolivianos,
            'estado'              => $g->estado,
            'metodo_pago'         => $g->metodo_pago,
            'codigo_seguimiento'  => $g->codigo_seguimiento,
            'nombre_titular'      => $g->nombre_titular,
            'comprobante_pago'    => $g->comprobante_pago,
        ])->values();

        $total = GastoExtra::where('estado','PAGADO')->sum('monto_bolivianos');
        $pendientes = GastoExtra::where('estado','PENDIENTE')->sum('monto_bolivianos');
        $pagados = GastoExtra::where('estado','PAGADO')->sum('monto_bolivianos');
        $aduaneros = GastoExtra::where('estado','PAGADO')->where('categoria','ADUANERO')->sum('monto_bolivianos');
        $carga = GastoExtra::where('estado','PAGADO')->where('categoria','CARGUIO')->sum('monto_bolivianos');
        $otros = 0;
        $idempotencyToken = $this->generarToken('gasto_extra_store_token');
        return view('gastos_extras.index',compact('contratos','categorias','cuentasEmpresa','proveedores','contratosParaFiltro','gastos','gastosJson','total','pendientes','pagados','aduaneros','carga','otros','idempotencyToken'));
    }
    public function store(GastoExtraRequest $request)
    {
        if (!$this->tokenValido('gasto_extra_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('gastos_extras.index');
        }
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
        $categoriaParametro = Parametro::where('tipo','categoria_gasto_extra')->whereRaw('UPPER(TRIM(valor)) = ?',[$categoria])->first();

        if (!$categoriaParametro) {
            $categoriaParametro = new Parametro();
            $categoriaParametro->tipo = 'categoria_gasto_extra';
            $categoriaParametro->valor = $categoria;
            $categoriaParametro->save();
        }
        $categoria = $categoriaParametro->valor;
        $nombreComprobante = '';
        if ($request->hasFile('comprobante_pago')) {
            $path = public_path('storage/comprobantes_pago');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $doc = $request->file('comprobante_pago');
            $name = 'comprobante_' .$categoria . '_' .strtoupper($request->concepto) . '_' .Carbon::now()->format('YmdHis');
            $nombreComprobante =$name . '.' . $doc->extension();
            $doc->move($path, $nombreComprobante);
        }
        $montoBolivianos = $monedaEsBob ? $request->monto : $request->monto * $request->tipo_cambio;
        $cuentaEmpresa = CuentaEmpresa::find($request->cuenta_empresa_id);
        if ($cuentaEmpresa && $cuentaEmpresa->saldo_actual < $montoBolivianos) {
            Alert::error('Saldo insuficiente', 'La cuenta "' . $cuentaEmpresa->nombre_cuenta . '" no tiene saldo suficiente para este monto.');
            return redirect()->route('gastos_extras.index');
        }
        $gasto = new GastoExtra();
        $gasto->contrato_id = $request->contrato_id ?: null;
        $gasto->cuenta_empresa_id = $request->cuenta_empresa_id;
        $gasto->categoria = $categoria;
        $gasto->concepto = strtoupper(trim($request->concepto));
        $gasto->fecha = $request->fecha;
        $gasto->monto = $request->monto;
        $gasto->moneda = $request->moneda;
        $gasto->monto_bolivianos = $montoBolivianos;
        $gasto->tipo_cambio = $monedaEsBob ? null : $request->tipo_cambio;
        $gasto->comprobante_pago = $nombreComprobante;
        $gasto->metodo_pago = $request->metodo_pago;
        $gasto->codigo_seguimiento = $this->resolverCodigoSeguimiento($request->metodo_pago, $request->codigo_seguimiento);
        $gasto->nombre_titular = $request->nombre_titular;
        $gasto->estado = $request->estado;
        $gasto->save();
        Alert::success('Registrado','Gasto Extra registrado con éxito');
        return redirect()->route('gastos_extras.index');
    }
    public function show($id)
    {
        return GastoExtra::with(['contrato', 'cuentaEmpresa'])->findOrFail($id);
    }

    public function update(GastoExtraRequest $request, $uuid)
    {
         $gasto = GastoExtra::where('uuid', $uuid)->firstOrFail();
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
        $categoriaParametro = Parametro::where('tipo','categoria_gasto_extra')->whereRaw('UPPER(TRIM(valor)) = ?',[$categoria])->first();
        if (!$categoriaParametro) {
            $categoriaParametro = new Parametro();
            $categoriaParametro->tipo ='categoria_gasto_extra';
            $categoriaParametro->valor =$categoria;
            $categoriaParametro->save();
        }
        $categoria = $categoriaParametro->valor;
        $nombreComprobante = $gasto->comprobante_pago;
        if ($request->hasFile('comprobante_pago')) {
            if (!empty($gasto->comprobante_pago) && file_exists(public_path('storage/comprobantes_pago/' .$gasto->comprobante_pago))) {
                unlink(public_path('storage/comprobantes_pago/' .$gasto->comprobante_pago));
            }

            $path = public_path('storage/comprobantes_pago');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $doc = $request->file('comprobante_pago');
            $name = 'comprobante_' .$categoria . '_' .strtoupper($request->concepto) . '_' .Carbon::now()->format('YmdHis');
            $nombreComprobante =$name . '.' . $doc->extension();
            $doc->move($path, $nombreComprobante);
        }
        $montoBolivianos = $monedaEsBob ? $request->monto : $request->monto * $request->tipo_cambio;
        $cuentaEmpresa = CuentaEmpresa::find($request->cuenta_empresa_id);
        if ($cuentaEmpresa) {
            // Si el gasto ya estaba PAGADO con esta misma cuenta, su saldo actual
            // ya tiene descontado el monto anterior: se repone antes de comparar,
            // para no bloquear una edición que no cambia lo que realmente se debe.
            $saldoDisponible = $cuentaEmpresa->saldo_actual;
            if ($gasto->estado === 'PAGADO' && (int) $gasto->cuenta_empresa_id === (int) $request->cuenta_empresa_id) {
                $saldoDisponible += (float) $gasto->monto_bolivianos;
            }
            if ($saldoDisponible < $montoBolivianos) {
                Alert::error('Saldo insuficiente', 'La cuenta "' . $cuentaEmpresa->nombre_cuenta . '" no tiene saldo suficiente para este monto.');
                return redirect()->route('gastos_extras.index');
            }
        }
        $gasto->contrato_id = $request->contrato_id ?: null;
        $gasto->cuenta_empresa_id =$request->cuenta_empresa_id;
        $gasto->categoria = $categoria;
        $gasto->concepto = strtoupper(trim($request->concepto));
        $gasto->fecha = $request->fecha;
        $gasto->monto = $request->monto;
        $gasto->moneda = $request->moneda;
        $gasto->monto_bolivianos =$montoBolivianos;
        $gasto->tipo_cambio = $monedaEsBob ? null : $request->tipo_cambio;
        $gasto->estado = $request->estado;
        $gasto->metodo_pago = $request->metodo_pago;
        $codigoActualSiEraQr = $gasto->metodo_pago === 'QR' ? $gasto->codigo_seguimiento : null;
        $gasto->codigo_seguimiento = $this->resolverCodigoSeguimiento($request->metodo_pago, $request->codigo_seguimiento, $codigoActualSiEraQr);
        $gasto->comprobante_pago = $nombreComprobante;
        $gasto->nombre_titular = $request->nombre_titular;
        $gasto->save();
        Alert::success('Actualizado','Gasto Extra actualizado con éxito');
        return redirect()->route('gastos_extras.index');
    }

    public function destroy($uuid)
    {
        $gasto = GastoExtra::where('uuid',$uuid)->firstOrFail();
        if (!empty($gasto->comprobante_pago)) {
            $fullPath = public_path('storage/comprobantes_pago/' . $gasto->comprobante_pago);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        // El propio modelo revierte el movimiento en tesorería si el gasto estaba PAGADO.
        $gasto->delete();
        Alert::success('Eliminacion ', 'Gasto Extra eliminado y movimiento en tesorería revertido.');
        return redirect()->route('gastos_extras.index');
    }

    // QR: se genera un código interno único, el usuario no lo escribe.
    // Transferencia: se usa el código que el usuario ingresó (obligatorio, ya validado).
    private function resolverCodigoSeguimiento(?string $metodoPago, ?string $codigoIngresado, ?string $codigoActual = null): ?string
    {
        if ($metodoPago !== 'QR') {
            return $codigoIngresado ?: null;
        }

        if ($codigoActual) {
            return $codigoActual;
        }

        do {
            $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
        } while (GastoExtra::withTrashed()->where('codigo_seguimiento', $codigo)->exists());

        return $codigo;
    }
}