<?php

namespace App\Http\Controllers;

use App\Models\Tramo;
use App\Models\ContratoCamion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use RealRashid\SweetAlert\Facades\Alert;

class TramoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    // Registrar llegada de un tramo y decidir qué pasa
    public function registrarLlegada(Request $request, $uuid)
    {
        $tramo = Tramo::where('uuid', $uuid)->firstOrFail();

        $desdeSegimiento = $request->input('origen') === 'seguimiento';
        $rutaRetorno = $desdeSegimiento
            ? redirect()->route('seguimiento.index')
            : redirect()->route('contratos.camiones', $tramo->contratoCamion->contrato->uuid);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'peso_llegada'            => 'required|numeric|min:0.001',
            'fecha_llegada'           => 'required|date|after_or_equal:' . $tramo->fecha_salida->format('Y-m-d'),
            'accion'                  => 'required|in:entregado,frontera,transbordo,div_carga',
            'cliente_id'              => 'required_if:accion,entregado,div_carga|nullable|exists:clientes,id',
            'empresa_facturadora_id'  => 'required_if:accion,entregado,div_carga|nullable|exists:empresas,id',
            'precio_por_tonelada'     => 'nullable|numeric|min:0',
            'moneda_venta'            => 'nullable|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'descuento_porcentaje'    => 'nullable|numeric|min:0|max:60',
            'observaciones_llegada'   => 'nullable|string|max:500',
            'tn_parcial'              => 'required_if:accion,div_carga|nullable|numeric|min:0.001',
            'destino_nuevo_tramo'     => 'required_if:accion,div_carga|nullable|string|max:150',
            'camion_nuevo_id'         => 'nullable|exists:camiones,id',
            'conductor_nuevo_id'      => 'nullable|exists:operadores_transporte,id',
            'fecha_salida_nuevo_tramo'=> 'nullable|date',
            'tipo_tramo_nuevo'        => 'nullable|in:Internacional,Nacional',
        ], [
            'peso_llegada.required'               => 'Debe ingresar el peso que llegó al destino.',
            'peso_llegada.min'                    => 'El peso debe ser mayor a 0.',
            'fecha_llegada.required'              => 'Debe ingresar la fecha en que llegó la carga.',
            'fecha_llegada.after_or_equal'        => 'La fecha de llegada no puede ser anterior a la fecha de salida (' . $tramo->fecha_salida->format('d/m/Y') . ').',
            'accion.required'                     => 'Debe indicar qué ocurrió cuando llegó la carga.',
            'cliente_id.required_if'              => 'Debe seleccionar el cliente al que se entregó la carga.',
            'empresa_facturadora_id.required_if'  => 'Debe seleccionar la empresa que facturará esta entrega.',
            'tn_parcial.required_if'              => 'Debe indicar las toneladas entregadas al primer cliente.',
            'tn_parcial.min'                      => 'Las toneladas entregadas deben ser mayores a 0.',
            'destino_nuevo_tramo.required_if'     => 'Debe indicar el destino del nuevo tramo.',
            'descuento_porcentaje.min'            => 'El descuento no puede ser negativo.',
            'descuento_porcentaje.max'            => 'El descuento no puede superar el 60%.',
        ]);

        if ($validator->fails()) {
            return $rutaRetorno
                ->withErrors($validator, 'llegada')
                ->withInput()
                ->with('abrirModalLlegada', $uuid);
        }

        $esDivision = $request->accion === 'div_carga';

        $nuevoEstado = match($request->accion) {
            'entregado'  => 'Entregado',
            'div_carga'  => 'Div. Carga',
            'frontera'   => 'Transbordando',
            'transbordo' => 'Transbordando',
        };

        $contratoUuidLlegada = $tramo->contratoCamion->contrato->uuid;
        $desdeSegimiento = $request->input('origen') === 'seguimiento';

        // Validar división de carga
        if ($esDivision) {
            $tnParcial   = (float) $request->tn_parcial;
            $pesoLlegada = (float) $request->peso_llegada;
            if ($tnParcial <= 0 || $tnParcial >= $pesoLlegada) {
                Alert::error('Error', 'Las TN entregadas al cliente 1 deben ser mayor a 0 y menores al total que llegó (' . $pesoLlegada . ' t).');
                return $desdeSegimiento
                    ? redirect()->route('seguimiento.index')
                    : redirect()->route('contratos.camiones', $contratoUuidLlegada);
            }
        }

        // El padre registra el peso total que llegó y queda como Div. Carga (nodo de distribución)
        // o como Entregado/Transbordando según la acción
        $tramo->update([
            'peso_llegada'           => (float) $request->peso_llegada,
            'fecha_llegada'          => $request->fecha_llegada,
            'estado'                 => $nuevoEstado,
            'cliente_id'             => in_array($request->accion, ['entregado', 'div_carga']) ? $request->cliente_id : null,
            'direccion_entrega'      => in_array($request->accion, ['entregado', 'div_carga']) ? ($request->direccion_entrega ?: null) : null,
            'empresa_facturadora_id' => in_array($request->accion, ['entregado', 'div_carga']) ? ($request->empresa_facturadora_id ?: null) : null,
            'precio_por_tonelada'    => $nuevoEstado === 'Entregado' ? ($request->precio_por_tonelada ?: null) : null,
            'moneda_venta'           => $nuevoEstado === 'Entregado' ? ($request->moneda_venta ?: 'BOB') : null,
            'descuento_porcentaje'   => $request->descuento_porcentaje ?: null,
            'observaciones_llegada'  => $request->observaciones_llegada,
        ]);

        // División de carga: generar dos hijos automáticamente
        if ($esDivision) {
            $tnCliente1 = (float) $request->tn_parcial;
            $tnCliente2 = round((float) $request->peso_llegada - $tnCliente1, 3);
            $camionNuevo    = $request->camion_nuevo_id ?: $tramo->camion_id;
            $conductorNuevo = $request->conductor_nuevo_id ?: $tramo->conductor_id;
            $monedaFlete    = $tramo->contratoCamion->moneda_flete ?? 'BOB';
            $fechaSalida    = $request->fecha_salida_nuevo_tramo ?? $request->fecha_llegada;

            $cc = $tramo->contratoCamion;

            // Hijo 1 — hereda el CC del padre (incluye el flete acordado)
            Tramo::create([
                'contrato_camion_id'     => $tramo->contrato_camion_id,
                'tramo_padre_id'         => $tramo->id,
                'camion_id'              => $tramo->camion_id,
                'conductor_id'           => $tramo->conductor_id,
                'origen'                 => $tramo->origen,
                'destino'                => $tramo->destino,
                'tipo_tramo'             => $tramo->tipo_tramo,
                'peso_salida'            => $tnCliente1,
                'peso_llegada'           => $tnCliente1,
                'fecha_salida'           => $tramo->fecha_salida,
                'fecha_llegada'          => $request->fecha_llegada,
                'estado'                 => 'Entregado',
                'cliente_id'             => $request->cliente_id,
                'direccion_entrega'      => $request->direccion_entrega ?: null,
                'empresa_facturadora_id' => $request->empresa_facturadora_id ?: null,
                'precio_por_tonelada'    => $request->precio_por_tonelada ?: null,
                'moneda_venta'           => $request->moneda_venta ?: 'BOB',
                'descuento_porcentaje'   => $request->descuento_porcentaje ?: null,
                'observaciones_llegada'  => 'División de carga — Entrega cliente 1',
                'created_by'             => auth()->id(),
                'updated_by'             => auth()->id(),
            ]);

            // Hijo 2 — nuevo CC propio con flete vacío (el usuario lo confirmará después)
            $cc2 = ContratoCamion::create([
                'contrato_id'      => $cc->contrato_id,
                'camion_id'        => $camionNuevo,
                'conductor_id'     => $conductorNuevo,
                'toneladas'        => $tnCliente2,
                'monto_acordado'   => null,
                'moneda_flete'     => $monedaFlete,
                'fecha_asignacion' => $fechaSalida,
                'estado_entrega'   => 'Pendiente',
                'activo'           => true,
                'created_by'       => auth()->id(),
                'updated_by'       => auth()->id(),
            ]);

            Tramo::create([
                'contrato_camion_id' => $cc2->id,
                'tramo_padre_id'     => $tramo->id,
                'camion_id'          => $camionNuevo,
                'conductor_id'       => $conductorNuevo,
                'origen'             => $tramo->destino,
                'destino'            => $request->destino_nuevo_tramo,
                'tipo_tramo'         => $request->tipo_tramo_nuevo ?? $tramo->tipo_tramo,
                'peso_salida'        => $tnCliente2,
                'fecha_salida'       => $fechaSalida,
                'estado'             => 'En ruta',
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // Recalcular el padre por si ambos hijos ya estuvieran entregados
            $this->recalcularEstadoPadre($tramo->id);

            // Actualizar estado_entrega del ContratoCamion padre
            // Refrescar la relación para incluir los tramos recién creados
            $cc->refresh();

            // Verificar si todos los tramos hojas están entregados (excluyendo "Div. Carga")
            $todosEntregados = $cc->tramos()
                ->whereDoesntHave('tramosHijos')
                ->where('estado', '!=', 'Div. Carga')
                ->whereNotIn('estado', ['Entregado', 'Desactivado'])
                ->doesntExist();

            if ($todosEntregados) {
                $cc->update(['estado_entrega' => 'Entregado']);
            }

            Alert::success('División de Carga', "Se generaron 2 tramos: {$tnCliente1} t entregadas al cliente 1 y {$tnCliente2} t en ruta al destino siguiente.");
            return $desdeSegimiento
                ? redirect()->route('seguimiento.index')
                : redirect()->route('contratos.camiones', $contratoUuidLlegada);
        }

        // Si fue entregado, recalcular hacia arriba
        if ($nuevoEstado === 'Entregado') {
            $this->recalcularEstadoPadre($tramo->tramo_padre_id);

            $cc = $tramo->contratoCamion;
            // Verificar si todos los tramos hojas (sin hijos) están entregados
            // Excluir tramos con "Div. Carga" porque son nodos de distribución, no entregas finales
            $todosEntregados = $cc->tramos()
                ->whereDoesntHave('tramosHijos')
                ->where('estado', '!=', 'Div. Carga')
                ->whereNotIn('estado', ['Entregado', 'Desactivado'])
                ->doesntExist();

            if ($todosEntregados) {
                $cc->update(['estado_entrega' => 'Entregado']);
            }

            Alert::success('Entregado', 'Carga entregada al cliente. Peso final: ' . $request->peso_llegada . ' t');
        } elseif ($nuevoEstado === 'Transbordando') {
            Alert::success('Transbordando', 'Llegada registrada. Agrega los camiones de transbordo.');
        }

        return $desdeSegimiento
            ? redirect()->route('seguimiento.index')
            : redirect()->route('contratos.camiones', $contratoUuidLlegada);
    }

    // Crear tramo hijo (transbordo desde un tramo en frontera)
    public function store(Request $request)
    {
        if (!$this->tokenValido('tramo_transbordo_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->back();
        }

        $tramoPadre = Tramo::findOrFail($request->tramo_padre_id);

        $request->validate([
            'tramo_padre_id' => 'required|exists:tramos,id',
            'camion_id'      => 'required|exists:camiones,id',
            'conductor_id'   => 'required|exists:operadores_transporte,id',
            'destino'        => 'required|string|max:150',
            'tipo_tramo'     => 'required|in:Internacional,Nacional',
            'peso_salida'    => 'required|numeric|min:0.001',
            'fecha_salida'   => 'required|date|after_or_equal:' . $tramoPadre->fecha_llegada->format('Y-m-d'),
            'observaciones'  => 'nullable|string|max:500',
        ], [
            'tramo_padre_id.required'     => 'El tramo padre es obligatorio para un transbordo.',
            'camion_id.required'          => 'Debe seleccionar un camión.',
            'conductor_id.required'       => 'Debe seleccionar un conductor.',
            'destino.required'            => 'El destino es obligatorio.',
            'peso_salida.required'        => 'El peso de salida es obligatorio.',
            'fecha_salida.required'       => 'La fecha de salida es obligatoria.',
            'fecha_salida.after_or_equal' => 'La fecha de salida del transbordo no puede ser anterior a la fecha en que llegó el camión anterior (' . $tramoPadre->fecha_llegada->format('d/m/Y') . ').',
        ]);

        $desdeSegimiento = $request->input('origen') === 'seguimiento';

        if ($tramoPadre->estado !== 'Transbordando') {
            Alert::error('No permitido', 'Solo se puede agregar transbordo a un tramo que está transbordando.');
            return $desdeSegimiento
                ? redirect()->route('seguimiento.index')
                : redirect()->route('contratos.camiones', $tramoPadre->contratoCamion->contrato->uuid);
        }

        // Verificar que el total de transbordos activos no supere el peso que llegó al padre
        $yaAsignado   = (float) $tramoPadre->tramosHijos()->where('activo', true)->sum('peso_salida');
        $disponible   = (float) $tramoPadre->peso_llegada - $yaAsignado;
        $pesoSolicitado = (float) $request->peso_salida;

        if ($pesoSolicitado > $disponible) {
            Alert::error(
                'Peso excedido',
                "Solo quedan {$disponible} t disponibles para transbordo (llegaron {$tramoPadre->peso_llegada} t, ya asignadas {$yaAsignado} t). No puedes asignar {$pesoSolicitado} t a este camión."
            );
            return $desdeSegimiento
                ? redirect()->route('seguimiento.index')
                : redirect()->route('contratos.camiones', $tramoPadre->contratoCamion->contrato->uuid);
        }

        // Cada camión de transbordo tiene su propio flete independiente
        $ccPadre = $tramoPadre->contratoCamion;
        $ccHijo  = ContratoCamion::create([
            'contrato_id'      => $ccPadre->contrato_id,
            'camion_id'        => $request->camion_id,
            'conductor_id'     => $request->conductor_id,
            'toneladas'        => $request->peso_salida,
            'monto_acordado'   => null,
            'moneda_flete'     => $ccPadre->moneda_flete ?? 'BOB',
            'fecha_asignacion' => $request->fecha_salida,
            'estado_entrega'   => 'Pendiente',
            'activo'           => true,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        Tramo::create([
            'contrato_camion_id' => $ccHijo->id,
            'tramo_padre_id'     => $request->tramo_padre_id,
            'camion_id'          => $request->camion_id,
            'conductor_id'       => $request->conductor_id,
            'origen'             => $tramoPadre->destino,
            'destino'            => $request->destino,
            'tipo_tramo'         => $request->tipo_tramo,
            'peso_salida'        => $request->peso_salida,
            'fecha_salida'       => $request->fecha_salida,
            'estado'             => 'En ruta',
            'observaciones'      => $request->observaciones,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        // Recalcular estado del padre y ancestros
        $this->recalcularEstadoPadre($tramoPadre->id);

        Alert::success('Éxito', 'Tramo de transbordo registrado.');
        return $desdeSegimiento
            ? redirect()->route('seguimiento.index')
            : redirect()->route('contratos.camiones', $tramoPadre->contratoCamion->contrato->uuid);
    }

    public function notaEntrega($uuid)
    {
        $tramo = Tramo::with(['camion', 'conductor', 'contratoCamion.contrato.cliente', 'contratoCamion.contrato.proveedor', 'tramosHijos', 'cliente'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_if(!in_array($tramo->estado, ['Entregado', 'Transbordado', 'Div. Carga']), 403, 'El tramo aún no ha sido completado.');

        $pdf = Pdf::loadView('contratos.partials.nota-entrega-pdf', compact('tramo'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('nota-entrega-' . $tramo->camion->placa . '-' . $tramo->fecha_llegada->format('Y-m-d') . '.pdf');
    }

    public function toggleActivo($uuid)
    {
        $tramo        = Tramo::where('uuid', $uuid)->firstOrFail();
        $contratoUuid = $tramo->contratoCamion->contrato->uuid;

        // Solo se puede desactivar si está en ruta y no tiene hijos activos; reactivar siempre está permitido
        if ($tramo->activo) {
            if ($tramo->estado !== 'En ruta') {
                Alert::error('No permitido', 'Solo se puede desactivar un tramo que está en ruta.');
                return redirect()->route('contratos.camiones', $contratoUuid);
            }
            if ($tramo->tramosHijos()->where('activo', true)->exists()) {
                Alert::error('No permitido', 'No se puede desactivar un tramo que tiene camiones de transbordo activos.');
                return redirect()->route('contratos.camiones', $contratoUuid);
            }
        }

        $desactivando = $tramo->activo;
        $estadoAnterior = $tramo->estado;

        $tramo->update([
            'activo'     => !$tramo->activo,
            'estado'     => $desactivando ? 'Desactivado' : 'En ruta',
            'updated_by' => auth()->id(),
        ]);

        // Recalcular estado hacia arriba en toda la cadena
        $this->recalcularEstadoPadre($tramo->tramo_padre_id);

        $msg = $desactivando ? 'Tramo desactivado. El registro se conserva en el historial.' : 'Tramo reactivado.';
        Alert::success('Listo', $msg);
        return redirect()->route('contratos.camiones', $contratoUuid);
    }

    // Recalcula recursivamente el estado de un tramo y sus ancestros
    private function recalcularEstadoPadre(?int $tramoPadreId): void
    {
        if (!$tramoPadreId) return;

        $padre = Tramo::find($tramoPadreId);
        if (!$padre) return;

        // Si el padre es "Div. Carga", mantener ese estado sin importar los hijos
        // Los tramos con división de carga permanecen en ese estado como nodo de distribución
        if ($padre->estado === 'Div. Carga') {
            // No hacer nada, mantener el estado "Div. Carga"
            return;
        }

        // Para otros estados (Transbordando, Transbordado), recalcular según los hijos
        $hijosActivos = $padre->tramosHijos()->where('activo', true)->count();

        if ($hijosActivos === 0) {
            $padre->update(['estado' => 'Transbordando']);
        } else {
            $disponible = round((float) $padre->peso_llegada - (float) $padre->tramosHijos()->where('activo', true)->sum('peso_salida'), 3);
            $padre->update(['estado' => $disponible <= 0 ? 'Transbordado' : 'Transbordando']);
        }

        // Subir al siguiente nivel
        $this->recalcularEstadoPadre($padre->tramo_padre_id);
    }
}
