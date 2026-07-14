<?php

namespace App\Http\Controllers;

use App\Models\Adquisicion;
use App\Models\AdquisicionCuota;
use App\Models\AdquisicionFactura;
use App\Models\Camion;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class AdquisicionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function validarAdquisicion(Request $request)
    {
        $data = $request->validate([
            'descripcion'           => 'required|string|max:255',
            'tipo_bien_id'          => 'nullable|exists:parametros,id',
            'camion_id'             => 'nullable|exists:camiones,id',
            'origen'                => 'required|in:NACIONAL,EXTERIOR',
            'pais_origen_id'        => 'nullable|exists:parametros,id',
            'vendedor'              => 'nullable|string|max:255',
            'financiamiento'        => 'required|in:CREDITO,CAPITAL',
            'entidad_financiera'    => 'nullable|string|max:150',
            'moneda'                => 'required|string|max:5',
            'monto_total'           => 'required|numeric|min:0.01',
            'tipo_interes'          => 'nullable|in:FIJO,VARIABLE',
            'tasa_interes'          => 'nullable|numeric|min:0|max:100',
            'fecha_adquisicion'     => 'required|date',
            'fecha_contrato_inicio' => 'nullable|date',
            'fecha_contrato_fin'    => 'nullable|date|after_or_equal:fecha_contrato_inicio',
            'observaciones'         => 'nullable|string',
        ]);

        // El camión vinculado solo es válido cuando el tipo de bien es CAMIONES
        $esCamiones = $data['tipo_bien_id']
            && Parametro::where('id', $data['tipo_bien_id'])->where('valor', 'CAMIONES')->exists();
        if (!$esCamiones) {
            $data['camion_id'] = null;
        }

        // El país de origen solo aplica cuando el origen es EXTERIOR
        if ($data['origen'] !== 'EXTERIOR') {
            $data['pais_origen_id'] = null;
        }

        return $data;
    }

    public function index()
    {
        $adquisiciones = Adquisicion::with(['cuotas', 'camion', 'tipoBien', 'paisOrigen'])
            ->whereNull('deleted_at')
            ->orderByDesc('fecha_adquisicion')
            ->get();

        $camiones = Camion::where('es_propio', true)->whereNull('deleted_at')->orderBy('placa')->get();
        $tiposBien = Parametro::where('tipo', 'bien_tipo')->orderBy('valor')->get();
        $paisesOrigen = Parametro::where('tipo', 'pais_exportacion')->orderBy('valor')->get();

        return view('adquisiciones.index', compact('adquisiciones', 'camiones', 'tiposBien', 'paisesOrigen'));
    }

    public function show($uuid)
    {
        $adquisicion = Adquisicion::with(['cuotas', 'facturas', 'camion', 'tipoBien', 'paisOrigen'])
            ->where('uuid', $uuid)->firstOrFail();

        $camiones = Camion::where('es_propio', true)->whereNull('deleted_at')->orderBy('placa')->get();
        $tiposBien = Parametro::where('tipo', 'bien_tipo')->orderBy('valor')->get();
        $paisesOrigen = Parametro::where('tipo', 'pais_exportacion')->orderBy('valor')->get();

        return view('adquisiciones.show', compact('adquisicion', 'camiones', 'tiposBien', 'paisesOrigen'));
    }

    public function store(Request $request)
    {
        $data = $this->validarAdquisicion($request);
        $adquisicion = Adquisicion::create($data);
        Alert::success('Adquisiciones', 'Adquisición registrada. Ahora genere el plan de pagos.');
        return redirect()->route('adquisiciones.show', $adquisicion->uuid);
    }

    public function update(Request $request, $uuid)
    {
        $adquisicion = Adquisicion::where('uuid', $uuid)->firstOrFail();
        $adquisicion->update($this->validarAdquisicion($request));
        Alert::success('Adquisiciones', 'Datos actualizados.');
        return back();
    }

    public function destroy($uuid)
    {
        $adquisicion = Adquisicion::with(['cuotas', 'facturas'])->where('uuid', $uuid)->firstOrFail();
        if ($adquisicion->cuotas->whereNotNull('fecha_pago')->isNotEmpty()) {
            Alert::error('Adquisiciones', 'No se puede eliminar: ya tiene pagos registrados.');
            return back();
        }
        foreach ($adquisicion->facturas as $f) {
            if ($f->archivo) Storage::disk('public')->delete($f->archivo);
        }
        $adquisicion->delete();
        Alert::success('Adquisiciones', 'Adquisición eliminada.');
        return redirect()->route('adquisiciones.index');
    }

    // ===== Plan de pagos =====

    // Genera N cuotas mensuales a partir de la primera fecha
    public function generarPlan(Request $request, $uuid)
    {
        $adquisicion = Adquisicion::with('cuotas')->where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'nro_cuotas'    => 'required|integer|min:1|max:360',
            'monto_cuota'   => 'required|numeric|min:0.01',
            'primera_fecha' => 'required|date',
        ]);

        $nro = ($adquisicion->cuotas->max('nro') ?? 0);
        $fecha = \Carbon\Carbon::parse($data['primera_fecha']);
        for ($i = 0; $i < $data['nro_cuotas']; $i++) {
            AdquisicionCuota::create([
                'adquisicion_id'   => $adquisicion->id,
                'nro'              => ++$nro,
                'fecha_programada' => $fecha->copy()->addMonthsNoOverflow($i),
                'monto'            => $data['monto_cuota'],
            ]);
        }

        Alert::success('Plan de Pagos', "Se generaron {$data['nro_cuotas']} cuotas mensuales.");
        return back();
    }

    // Agregar o editar una cuota individual (monto, fecha, tasa para interés variable)
    public function storeCuota(Request $request, $uuid)
    {
        $adquisicion = Adquisicion::with('cuotas')->where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'fecha_programada' => 'required|date',
            'monto'            => 'required|numeric|min:0.01',
            'tasa_aplicada'    => 'nullable|numeric|min:0|max:100',
            'observaciones'    => 'nullable|string|max:255',
        ]);
        $data['adquisicion_id'] = $adquisicion->id;
        $data['nro'] = ($adquisicion->cuotas->max('nro') ?? 0) + 1;
        AdquisicionCuota::create($data);
        Alert::success('Plan de Pagos', 'Cuota agregada al plan.');
        return back();
    }

    public function updateCuota(Request $request, $uuid)
    {
        $cuota = AdquisicionCuota::where('uuid', $uuid)->firstOrFail();
        if ($cuota->fecha_pago) {
            Alert::error('Plan de Pagos', 'No se puede modificar una cuota ya pagada.');
            return back();
        }
        $data = $request->validate([
            'fecha_programada' => 'required|date',
            'monto'            => 'required|numeric|min:0.01',
            'tasa_aplicada'    => 'nullable|numeric|min:0|max:100',
            'observaciones'    => 'nullable|string|max:255',
        ]);
        $cuota->update($data);
        Alert::success('Plan de Pagos', 'Cuota actualizada.');
        return back();
    }

    public function destroyCuota($uuid)
    {
        $cuota = AdquisicionCuota::where('uuid', $uuid)->firstOrFail();
        if ($cuota->fecha_pago) {
            Alert::error('Plan de Pagos', 'No se puede eliminar una cuota ya pagada. Anule el pago primero.');
            return back();
        }
        $cuota->delete();
        Alert::success('Plan de Pagos', 'Cuota eliminada.');
        return back();
    }

    // Registrar el pago de una cuota (en Bs, al tipo de cambio del día)
    public function pagarCuota(Request $request, $uuid)
    {
        $cuota = AdquisicionCuota::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'fecha_pago'    => 'required|date',
            'monto_pagado'  => 'required|numeric|min:0.01', // en moneda de la adquisición
            'tipo_cambio'   => 'required|numeric|min:0.0001', // Bs por unidad
            'comprobante'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'observaciones' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $request->file('comprobante')->store('adquisiciones/comprobantes', 'public');
        }
        $data['monto_pagado_bob'] = round($data['monto_pagado'] * $data['tipo_cambio'], 2);
        $cuota->update($data);

        Alert::success('Plan de Pagos', 'Pago registrado: ' . number_format($data['monto_pagado_bob'], 2) . ' Bs.');
        return back();
    }

    public function anularPago($uuid)
    {
        $cuota = AdquisicionCuota::where('uuid', $uuid)->firstOrFail();
        if ($cuota->comprobante) {
            Storage::disk('public')->delete($cuota->comprobante);
        }
        $cuota->update([
            'fecha_pago'       => null,
            'monto_pagado'     => null,
            'tipo_cambio'      => null,
            'monto_pagado_bob' => null,
            'comprobante'      => null,
        ]);
        Alert::success('Plan de Pagos', 'Pago anulado, la cuota vuelve a estar pendiente.');
        return back();
    }

    public function verComprobante($uuid)
    {
        $cuota = AdquisicionCuota::where('uuid', $uuid)->firstOrFail();
        abort_if(!$cuota->comprobante, 404, 'Esta cuota no tiene comprobante.');
        $path = Storage::disk('public')->path($cuota->comprobante);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        return response()->file($path);
    }

    // ===== Facturas de compra =====
    public function storeFactura(Request $request, $uuid)
    {
        $adquisicion = Adquisicion::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'numero'        => 'nullable|string|max:50',
            'fecha'         => 'required|date',
            'emisor'        => 'nullable|string|max:150',
            'monto'         => 'required|numeric|min:0.01',
            'moneda'        => 'required|string|max:5',
            'archivo'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'observaciones' => 'nullable|string|max:255',
        ]);
        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('adquisiciones/facturas', 'public');
        }
        $data['adquisicion_id'] = $adquisicion->id;
        AdquisicionFactura::create($data);
        Alert::success('Facturas', 'Factura registrada.');
        return back();
    }

    public function verFactura($uuid)
    {
        $factura = AdquisicionFactura::where('uuid', $uuid)->firstOrFail();
        abort_if(!$factura->archivo, 404, 'Esta factura no tiene archivo cargado.');
        $path = Storage::disk('public')->path($factura->archivo);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        return response()->file($path);
    }

    public function destroyFactura($uuid)
    {
        $factura = AdquisicionFactura::where('uuid', $uuid)->firstOrFail();
        if ($factura->archivo) {
            Storage::disk('public')->delete($factura->archivo);
        }
        $factura->delete();
        Alert::success('Facturas', 'Factura eliminada.');
        return back();
    }
}
