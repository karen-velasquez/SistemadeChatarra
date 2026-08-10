@extends('layouts.app')
@section('titulo', 'Pago Masivo de Fletes')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PAGO MASIVO DE FLETES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pagos.camiones.index') }}">Pagos Camiones</a></li>
                    <li class="breadcrumb-item active">Pago Masivo</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    @if($porProveedor->isEmpty())
                    data-steps='[
                        {"intro":"💸 El <b>Pago Masivo de Fletes</b> te permite pagar a varios transportistas a la vez.<br><br>📭 Por ahora <b>no hay fletes pendientes</b> con entregas realizadas, así que no hay nada que pagar."}
                    ]'
                    @else
                    data-steps='[
                        {"intro":"💸 El <b>Pago Masivo de Fletes</b> paga varios fletes de golpe. Se hace en <b>2 pasos</b>: primero eliges qué fletes pagar, luego a qué cuenta llega cada uno. Te guío por el Paso 1."},
                        {"element":"#pm_cuenta","intro":"🏦 <b>Cuenta de origen</b>: de qué cuenta de la empresa sale el dinero. Muestra el saldo y avisa si no alcanza.","position":"bottom"},
                        {"element":"#pm_fecha","intro":"📅 <b>Fecha de pago</b> de todo el lote.","position":"bottom"},
                        {"element":"#pm_metodo","intro":"💳 <b>Método de pago</b> (transferencia o QR). El código de seguimiento se genera automáticamente.","position":"bottom"},
                        {"element":"#zona-fletes","intro":"🚚 Los fletes pendientes <b>agrupados por proveedor</b>. Marca la casilla del proveedor para seleccionar todos sus fletes, o márcalos uno por uno.","position":"top"},
                        {"element":"#col-pct-flete","intro":"🔢 Al marcar un flete puedes dejarlo así para pagar el <b>saldo completo</b>, o indicar un <b>% del saldo</b> (o un monto) para registrar solo un <b>adelanto</b>. Ambos campos se calculan entre sí.","position":"bottom"},
                        {"element":"#zona-totales-flete","intro":"🧮 Aquí ves cuántos fletes seleccionaste y el <b>total a pagar</b>, que suma lo indicado en cada uno.","position":"top"},
                        {"element":"#btn_continuar","intro":"➡️ Cuando todo esté listo, el botón <b>Siguiente</b> (arriba) te lleva al Paso 2 para asignar las cuentas destino.","position":"left"}
                    ]'
                    @endif>
                <i class="bi bi-question-circle"></i>
            </button>
            <a href="{{ route('pagos.camiones.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<section class="section">

{{-- Resumen del último pago masivo --}}
@if(session('pago_masivo_camion_resumen'))
@php $r = session('pago_masivo_camion_resumen'); @endphp
<div class="alert alert-success border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <strong>Pago masivo registrado</strong>
        @if($r['codigo'])
            <span class="badge bg-light text-dark border ms-auto">
                <i class="bi bi-upc-scan me-1"></i>{{ strtoupper($r['metodo']) }}: {{ $r['codigo'] }}
            </span>
        @endif
    </div>
    <table class="table table-sm table-borderless mb-0 small">
        <thead class="table-success">
            <tr>
                <th>Proveedor</th>
                <th>Camión</th>
                <th>Contrato</th>
                <th>Cuenta destino</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($r['lineas'] as $l)
            <tr>
                <td>{{ $l['proveedor'] }}</td>
                <td>{{ $l['camion'] }}</td>
                <td>{{ $l['contrato'] }}</td>
                <td><small class="text-muted">{{ $l['cuenta_destino'] ?? '—' }}</small></td>
                <td class="text-end fw-semibold">{{ $l['monto'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($porProveedor->isEmpty())
    <div class="alert alert-info text-center py-4">
        <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
        No hay fletes pendientes de pago con entregas realizadas.
    </div>
@else

{{-- ================================================================
     PASO 1: Selección de fletes y datos del pago
     ================================================================ --}}
<div id="paso1">

<div class="card">
<div class="card-body">
    {{-- El botón de avanzar va arriba, como en el pago masivo a proveedores --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="card-title mb-0">Paso 1 — Seleccionar fletes y datos del pago</h5>
        <button type="button" id="btn_continuar" class="btn btn-primary" disabled onclick="irAPaso2()">
            Siguiente: Asignar cuentas destino <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
    <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Seleccione los fletes a pagar e indique cuánto abonar a cada uno: el <strong>saldo completo</strong>
        o un <strong>adelanto</strong> (un % del saldo o un monto). Luego asigne la cuenta destino de cada flete.
    </p>

    {{-- Datos globales del pago --}}
    <div class="row g-3 mb-4 p-3 bg-light rounded">
        <div class="col-md-4">
            <label class="form-label fw-semibold"><i class="bi bi-building"></i> Cuenta de origen (empresa) <span class="text-danger">*</span></label>
            <select id="pm_cuenta" class="form-select" required onchange="actualizarResumen()">
                <option value="" data-saldo="" data-moneda="">— Seleccione cuenta —</option>
                @foreach($empresas as $empresa)
                    @foreach($empresa->cuentas as $cta)
                    <option value="{{ $cta->id }}" data-saldo="{{ $cta->saldo_actual }}" data-moneda="{{ $cta->moneda }}">
                        {{ $empresa->nombre }} — {{ $cta->alias ?? $cta->numero_cuenta }} [{{ $cta->moneda }}] — Saldo: {{ number_format($cta->saldo_actual, 2, ',', '.') }}
                    </option>
                    @endforeach
                @endforeach
            </select>
            <div id="saldo_cuenta_info" style="display:none" class="mt-1">
                <small>Saldo disponible: <strong id="lbl_saldo_cuenta" class="text-success"></strong></small>
                <div id="aviso_saldo" class="alert alert-danger py-1 mt-1 small d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="aviso_saldo_texto"></span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold"><i class="bi bi-calendar3"></i> Fecha de pago <span class="text-danger">*</span></label>
            <input type="date" id="pm_fecha" class="form-control" value="{{ date('Y-m-d') }}" required onchange="actualizarResumen()">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold"><i class="bi bi-credit-card"></i> Método de pago <span class="text-danger">*</span></label>
            <select id="pm_metodo" class="form-select" required onchange="actualizarResumen()">
                <option value="">— Seleccione —</option>
                <option value="transferencia">Transferencia</option>
                <option value="qr">QR</option>
            </select>
        </div>
    </div>

    {{-- Lista de fletes --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-semibold">Fletes pendientes por proveedor</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodos(true)">
                <i class="bi bi-check-all"></i> Seleccionar todos
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodos(false)">
                <i class="bi bi-x"></i> Limpiar
            </button>
        </div>
    </div>

    <div id="zona-fletes">
    @foreach($porProveedor as $grupo)
    <div class="card border mb-3">
        <div class="card-header bg-light d-flex align-items-center gap-2 py-2">
            <i class="bi bi-person-badge text-primary"></i>
            <span class="fw-semibold">{{ $grupo['proveedor']->nombre }}</span>
            <span class="badge bg-secondary ms-auto">{{ $grupo['contratos']->count() }} flete(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center ps-3" style="width:40px">
                            <input type="checkbox" class="form-check-input chk-proveedor"
                                   onchange="seleccionarProveedor(this, '{{ $grupo['proveedor']->id }}')"
                                   title="Seleccionar todos de este proveedor">
                        </th>
                        <th>Contrato</th>
                        <th>Camión</th>
                        <th>Conductor</th>
                        <th>Cliente</th>
                        <th class="text-end">Monto total</th>
                        <th class="text-end text-warning">Total adelantado</th>
                        <th class="text-end text-danger">Saldo pendiente</th>
                        <th style="width:120px" @if($loop->first) id="col-pct-flete" @endif>% a pagar</th>
                        <th class="text-end" style="width:150px">Monto a pagar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo['contratos'] as $cc)
                    @php
                        $conductorId   = $cc->conductor_id;
                        $ctasConductor = $conductorId ? ($cuentasPorOperador[$conductorId] ?? collect()) : collect();
                        $totalCuentas  = $ctasConductor->count();
                        $clienteNombre = $cc->tramos
                            ->whereNotNull('cliente_id')
                            ->first()?->cliente?->nombre ?? '—';
                    @endphp
                    <tr>
                        <td class="text-center ps-3">
                            <input type="checkbox"
                                   class="form-check-input chk-camion"
                                   value="{{ $cc->id }}"
                                   data-cc-id="{{ $cc->id }}"
                                   data-proveedor="{{ $grupo['proveedor']->id }}"
                                   data-saldo="{{ round($cc->saldo_pendiente, 2) }}"
                                   data-moneda="{{ $cc->moneda_flete ?? 'BOB' }}"
                                   data-label="{{ $cc->camion->placa ?? '—' }} — {{ $cc->contrato->numero_contrato ?? '—' }}"
                                   data-total-cuentas="{{ $totalCuentas }}"
                                   onchange="onCheckFlete(this)">
                        </td>
                        <td><span class="fw-semibold small">{{ $cc->contrato->numero_contrato ?? '—' }}</span></td>
                        <td>
                            <span class="small fw-semibold">{{ $cc->camion->placa ?? '—' }}</span>
                            <div class="text-muted" style="font-size:.72rem">{{ $cc->camion->marca->valor ?? '' }}</div>
                            {{-- Se listan también los fletes en ruta (para adelantos): se distinguen aquí --}}
                            @php $entregado = $cc->tramos->contains('estado', 'Entregado'); @endphp
                            @if($entregado)
                                <span class="badge bg-success" style="font-size:.62rem">
                                    <i class="bi bi-check-circle me-1"></i>Entregado
                                </span>
                            @else
                                <span class="badge bg-info text-dark" style="font-size:.62rem"
                                      title="Aún no entrega: solo conviene pagarle un adelanto">
                                    <i class="bi bi-truck me-1"></i>En ruta
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($cc->conductor)
                                <small>{{ $cc->conductor->nombre_completo }}</small>
                                @if($ctasConductor->isNotEmpty())
                                    <div class="text-muted" style="font-size:.68rem"><i class="bi bi-bank me-1"></i>{{ $ctasConductor->count() }} cuenta(s)</div>
                                @else
                                    <div class="text-warning" style="font-size:.68rem"><i class="bi bi-exclamation-triangle me-1"></i>Sin cuentas</div>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td><small>{{ $clienteNombre }}</small></td>
                        <td class="text-end small">
                            {{ $cc->moneda_flete ?? 'BOB' }} {{ number_format($cc->monto_neto, 2, ',', '.') }}
                        </td>
                        <td class="text-end small text-warning fw-semibold">
                            {{ $cc->moneda_flete ?? 'BOB' }} {{ number_format($cc->total_pagado, 2, ',', '.') }}
                        </td>
                        <td class="text-end fw-bold text-danger small">
                            {{ $cc->moneda_flete ?? 'BOB' }} {{ number_format($cc->saldo_pendiente, 2, ',', '.') }}
                        </td>
                        {{-- % y monto: por defecto 100% (saldo completo); menos = adelanto --}}
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control pct_input_flete" id="pct_f_{{ $cc->id }}"
                                       min="0" max="100" step="0.01" placeholder="100" disabled
                                       oninput="clampPctFlete(this); calcularMontoFlete({{ $cc->id }})">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="invalid-feedback d-block small" id="err_pct_f_{{ $cc->id }}" style="display:none!important">
                                <i class="bi bi-exclamation-circle me-1"></i>Máximo 100%
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text text-muted" style="font-size:.75rem">{{ $cc->moneda_flete ?? 'BOB' }}</span>
                                <input type="text" inputmode="numeric" class="form-control monto_input_flete text-end"
                                       id="monto_f_{{ $cc->id }}" placeholder="0,00" disabled autocomplete="off"
                                       oninput="formatearMontoFlete(this); calcularPctFlete({{ $cc->id }})">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
    </div>{{-- /zona-fletes --}}

    <div class="d-flex justify-content-between align-items-center mt-3" id="zona-totales-flete">
        <div class="text-muted small">
            Fletes seleccionados: <strong id="res_cant">0</strong>
            &nbsp;|&nbsp; Total a pagar: <strong id="res_total" class="text-danger">BOB 0,00</strong>
        </div>
    </div>

</div>
</div>
</div>{{-- /paso1 --}}

{{-- ================================================================
     PASO 2: Asignación de cuenta destino por flete
     ================================================================ --}}
<div id="paso2" style="display:none;">

<div class="card">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
        <h5 class="card-title">Paso 2 — ¿A qué cuenta llega el dinero de cada flete?</h5>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                data-steps='[
                    {"intro":"📍 Estás en el <b>Paso 2</b>. Aquí defines a qué <b>cuenta bancaria</b> llega el pago de cada flete que seleccionaste antes."},
                    {"element":"#p2_resumen","intro":"📋 Este resumen recuerda la cuenta de origen, la fecha, el método y el total del lote.","position":"bottom"},
                    {"element":"#paso2_contenido","intro":"🏦 Los fletes aparecen agrupados por proveedor. Para cada uno, elige la <b>cuenta del conductor o propietario</b> donde se depositará el pago.","position":"top"},
                    {"element":"#btn_pm_volver","intro":"↩️ Si necesitas cambiar montos o la selección, con <b>Volver al paso 1</b> regresas sin perder nada.","position":"right"},
                    {"element":"#btn_registrar","intro":"✅ Cuando todos los fletes tengan cuenta asignada, este botón se activa y muestra un resumen final (exportable a Excel) antes de registrar los pagos.","position":"left"}
                ]'>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
    <p class="text-muted small mb-3">
        <i class="bi bi-bank me-1"></i>
        Para cada flete seleccione <strong>una cuenta bancaria</strong> a la que llegará el pago.
    </p>

    <form method="POST" action="{{ route('pagos.camiones.pago_masivo.store') }}" id="formPaso2">
        @csrf
        <input type="hidden" name="cuenta_origen_id"   id="h_cuenta_origen">
        <input type="hidden" name="fecha_pago"          id="h_fecha_pago">
        <input type="hidden" name="metodo_pago"         id="h_metodo_pago">
        <input type="hidden" name="codigo_seguimiento"  id="h_codigo">
        <input type="hidden" name="observaciones"       id="h_obs">
        <div id="h_contrato_ids_container"></div>

        {{-- Resumen del pago (se rellena por JS) --}}
        <div id="p2_resumen" class="row g-2 mb-4 p-3 bg-light rounded" style="display:none;">
            <div class="col-auto"><small><i class="bi bi-building me-1"></i>Cuenta: <strong id="p2_res_cuenta">—</strong></small></div>
            <div class="col-auto"><small><i class="bi bi-calendar me-1"></i>Fecha: <strong id="p2_res_fecha">—</strong></small></div>
            <div class="col-auto"><small><i class="bi bi-credit-card me-1"></i>Método: <strong id="p2_res_metodo">—</strong></small></div>
            <div class="col-auto ms-auto"><small class="text-danger fw-semibold">Total: <strong id="p2_res_total">—</strong></small></div>
        </div>

        {{-- Cards por proveedor generadas por JS --}}
        <div id="paso2_contenido"></div>

        <div class="d-flex gap-3 mt-4">
            <button type="button" id="btn_pm_volver" class="btn btn-outline-secondary" onclick="volverAPaso1()">
                <i class="bi bi-arrow-left me-1"></i> Volver al paso 1
            </button>
            <button type="button" id="btn_registrar" class="btn btn-success ms-auto" disabled onclick="abrirConfirmacion()">
                <i class="bi bi-check-circle me-1"></i> Revisar y confirmar
            </button>
        </div>
    </form>
</div>
</div>
</div>{{-- /paso2 --}}

{{-- ================================================================
     MODAL CONFIRMACIÓN + VISTA PREVIA EXCEL
     ================================================================ --}}
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-table me-2"></i>Confirmar Pago Masivo — Vista Previa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Revisa los datos antes de confirmar. Este proceso no se puede deshacer fácilmente.
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-light">
                    <span class="small text-muted"><span id="conf_cant">0</span> flete(s) — Total: <strong id="conf_total" class="text-danger">—</strong></span>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="abrirEnVentana()">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Abrir en nueva ventana
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="descargarExcel()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="tabla_preview" class="table table-bordered table-sm mb-0 align-middle"
                           style="font-size:.78rem; min-width:1400px;">
                        <thead>
                            <tr style="background:#1d7a3a; color:#fff; white-space:nowrap;">
                                <th class="text-center px-2">NRO. DE ORDEN</th>
                                <th class="text-center px-2">CODIGO DE CLIENTE</th>
                                <th class="text-center px-2">NRO. DE CUENTA (*)</th>
                                <th class="text-center px-2">NOMBRE DEL CLIENTE (*)</th>
                                <th class="text-center px-2">DOC. DE IDENTIDAD</th>
                                <th class="text-center px-2">IMPORTE (*)</th>
                                <th class="text-center px-2">FECHA DE PAGO (*)</th>
                                <th class="text-center px-2">FORMA DE PAGO</th>
                                <th class="text-center px-2">MONEDA DESTINO</th>
                                <th class="text-center px-2">ENTIDAD DESTINO</th>
                                <th class="text-center px-2">SUCURSAL DESTINO</th>
                                <th class="text-center px-2">GLOSA</th>
                                <th class="text-center px-2">CODIGO UNICO</th>
                                <th class="text-center px-2">EMAIL NOTIFICACION</th>
                                <th class="text-center px-2">NRO_DOC_TERCERO</th>
                                <th class="text-center px-2">NOMBRE TERCERO</th>
                            </tr>
                        </thead>
                        <tbody id="conf_tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" onclick="confirmarYEnviar()">
                    <i class="bi bi-check-circle me-1"></i>Sí, registrar pago
                </button>
            </div>
        </div>
    </div>
</div>

@endif
</section>

{{-- Datos de cuentas por operador (para JS) --}}
@php
$_sucMap  = \App\Models\Parametro::where('tipo','sucursal_cuenta')->whereNull('deleted_at')->pluck('valor','descripcion');
$cuentasJs = [];
foreach($cuentasPorOperador as $opId => $cuentas) {
    $cuentasJs[$opId] = $cuentas->map(fn($c) => [
        'id'            => $c->id,
        'banco'         => $c->banco->nombre ?? '—',
        'codigo_banco'  => $c->banco->codigo_banco ?? '',
        'numero'        => $c->numero_cuenta,
        'moneda'        => $c->moneda,
        'alias'         => $c->alias ?? '',
        'titular'       => $c->nombre_titular_excel ?? '',
        'tipo_relacion' => $c->tipo_relacion ?? '',
        'nro_documento'  => $c->nro_documento ?? '',
        'sigla_sucursal'     => $_sucMap[$c->sucursal_departamento] ?? '',
        'email_notificacion' => $c->email_notificacion ?? '',
    ])->values()->toArray();
}

$contratosJs = [];
foreach($porProveedor as $grupo) {
    foreach($grupo['contratos'] as $cc) {
        $conductorId  = $cc->conductor_id;
        $clienteNombre = $cc->tramos->whereNotNull('cliente_id')->first()?->cliente?->nombre ?? null;
        $contratosJs[$cc->id] = [
            'label'             => ($cc->camion->placa ?? '—') . ' — ' . ($cc->contrato->numero_contrato ?? '—'),
            'conductor_id'      => $conductorId,
            'conductor_nombre'  => $cc->conductor
                ? trim(($cc->conductor->apellido_paterno ?? '') . ' ' . ($cc->conductor->apellido_materno ?? '') . ' ' . ($cc->conductor->nombre ?? ''))
                : null,
            'cliente_nombre'    => $clienteNombre,
            'saldo'             => round($cc->saldo_pendiente, 2),
            'moneda'            => $cc->moneda_flete ?? 'BOB',
        ];
    }
}
@endphp

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
const _cuentasPorOperador = @json($cuentasJs);
const _contratosData      = @json($contratosJs);

function _fmtMonto(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

// ===== Paso 1 =====

function seleccionarTodos(estado) {
    document.querySelectorAll('.chk-camion').forEach(cb => { cb.checked = estado; onCheckFlete(cb); });
    document.querySelectorAll('.chk-proveedor').forEach(cb => cb.checked = estado);
    actualizarResumen();
}

function seleccionarProveedor(chkAll, proveedorId) {
    document.querySelectorAll(`.chk-camion[data-proveedor="${proveedorId}"]`)
        .forEach(cb => { cb.checked = chkAll.checked; onCheckFlete(cb); });
    actualizarResumen();
}

function actualizarResumen() {
    const checks      = document.querySelectorAll('.chk-camion:checked');
    const cuentaSel   = document.getElementById('pm_cuenta');
    const optSel      = cuentaSel.options[cuentaSel.selectedIndex];
    const moneda      = optSel?.dataset.moneda || 'BOB';
    const saldoCuenta = optSel?.dataset.saldo ? parseFloat(optSel.dataset.saldo) : null;
    let total = 0;

    // Suma lo que se va a pagar de cada flete (el saldo completo o el adelanto indicado)
    checks.forEach(cb => { total += montoAPagarFlete(cb.value); });

    document.getElementById('res_cant').textContent  = checks.length;
    document.getElementById('res_total').textContent = moneda + ' ' + _fmtMonto(total);

    // Saldo info
    const infoEl = document.getElementById('saldo_cuenta_info');
    const avisoEl = document.getElementById('aviso_saldo');
    const lblSaldo = document.getElementById('lbl_saldo_cuenta');
    let saldoInsuf = false;

    if (optSel?.value) {
        infoEl.style.display = '';
        lblSaldo.textContent = moneda + ' ' + _fmtMonto(saldoCuenta ?? 0);
        if (checks.length > 0 && saldoCuenta !== null && total > saldoCuenta) {
            saldoInsuf = true;
            document.getElementById('aviso_saldo_texto').textContent =
                'Saldo insuficiente: disponible ' + moneda + ' ' + _fmtMonto(saldoCuenta) +
                ', requerido ' + moneda + ' ' + _fmtMonto(total) + '.';
            avisoEl.classList.remove('d-none');
            lblSaldo.className = 'text-danger';
        } else {
            avisoEl.classList.add('d-none');
            lblSaldo.className = 'text-success';
        }
    } else {
        infoEl.style.display = 'none';
    }

    const metodo   = document.getElementById('pm_metodo').value;
    const fecha    = document.getElementById('pm_fecha').value;
    // Ningún flete puede quedar en 0 ni exceder su saldo
    const montosOk = Array.from(checks).every(cb => {
        const m = montoAPagarFlete(cb.value);
        return m > 0 && m <= parseFloat(cb.dataset.saldo ?? 0) + 0.005;
    });
    const sinErrores = document.querySelectorAll('.monto_input_flete.is-invalid, .pct_input_flete.is-invalid').length === 0;

    const ok = checks.length > 0 && cuentaSel.value !== '' && metodo !== '' && fecha !== ''
               && !saldoInsuf && montosOk && sinErrores;
    document.getElementById('btn_continuar').disabled = !ok;
}

// Los campos de % y monto solo se editan si el flete está marcado
function onCheckFlete(chk) {
    const ccId = chk.value;
    const pctIn = document.getElementById('pct_f_' + ccId);
    const monIn = document.getElementById('monto_f_' + ccId);
    if (pctIn) pctIn.disabled = !chk.checked;
    if (monIn) monIn.disabled = !chk.checked;
    if (!chk.checked) {
        // Al desmarcar se limpia para no arrastrar un adelanto de antes
        if (pctIn) { pctIn.value = ''; pctIn.classList.remove('is-invalid'); }
        if (monIn) { monIn.value = ''; monIn.classList.remove('is-invalid'); }
        const errEl = document.getElementById('err_pct_f_' + ccId);
        if (errEl) errEl.style.display = 'none';
    }
    actualizarResumen();
}

// ── Monto a pagar por flete: lo escrito, o el saldo completo si no se indicó nada ──
function montoAPagarFlete(ccId) {
    const chk = document.querySelector(`.chk-camion[value="${ccId}"]`);
    if (!chk) return 0;
    const saldo = parseFloat(chk.dataset.saldo ?? 0);
    const inp   = document.getElementById('monto_f_' + ccId);
    if (!inp || inp.value.trim() === '') return saldo;
    return _txt2numFlete(inp.value);
}

function _txt2numFlete(v) {
    return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0;
}

// Formatea mientras se escribe: miles con ".", 2 decimales con ","
function formatearMontoFlete(inp) {
    let raw = inp.value.replace(/[^0-9,]/g, '');
    let partes = raw.split(',');
    if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
    partes = raw.split(',');
    if (partes[1] !== undefined) partes[1] = partes[1].slice(0, 2);
    const entF  = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const nuevo = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
    const diff  = nuevo.length - inp.value.length;
    const pos   = (inp.selectionStart || 0) + diff;
    inp.value = nuevo;
    try { inp.setSelectionRange(pos, pos); } catch (_) {}
}

function clampPctFlete(input) {
    const v = parseFloat(input.value);
    if (!isNaN(v) && v < 0) input.value = 0;
}

// Escribir el % calcula el monto
function calcularMontoFlete(ccId) {
    const chk = document.querySelector(`.chk-camion[value="${ccId}"]`);
    if (!chk) return;
    const saldo = parseFloat(chk.dataset.saldo ?? 0);
    const pctIn = document.getElementById('pct_f_' + ccId);
    const monIn = document.getElementById('monto_f_' + ccId);
    const errEl = document.getElementById('err_pct_f_' + ccId);

    const pct = parseFloat(pctIn.value);
    const invalido = !isNaN(pct) && pct > 100;
    pctIn.classList.toggle('is-invalid', invalido);
    if (errEl) errEl.style.display = invalido ? 'block' : 'none';

    if (isNaN(pct) || pct <= 0 || invalido) {
        monIn.value = '';
    } else {
        monIn.value = _fmtMonto(Math.round(saldo * pct) / 100);
        monIn.classList.remove('is-invalid');
    }
    actualizarResumen();
}

// Escribir el monto calcula el %
function calcularPctFlete(ccId) {
    const chk = document.querySelector(`.chk-camion[value="${ccId}"]`);
    if (!chk) return;
    const saldo = parseFloat(chk.dataset.saldo ?? 0);
    const pctIn = document.getElementById('pct_f_' + ccId);
    const monIn = document.getElementById('monto_f_' + ccId);

    const monto = _txt2numFlete(monIn.value);
    // Un céntimo de tolerancia por el redondeo al calcular desde el %
    const invalido = monto > saldo + 0.005;
    monIn.classList.toggle('is-invalid', invalido);

    pctIn.value = (monto > 0 && !invalido) ? (Math.round(monto / saldo * 10000) / 100).toFixed(2) : '';
    if (!invalido) pctIn.classList.remove('is-invalid');
    actualizarResumen();
}

// ===== Paso 2 =====

function irAPaso2() {
    const checks    = document.querySelectorAll('.chk-camion:checked');
    const cuentaSel = document.getElementById('pm_cuenta');
    const metodo    = document.getElementById('pm_metodo').value;
    const fecha     = document.getElementById('pm_fecha').value;

    document.getElementById('h_cuenta_origen').value = cuentaSel.value;
    document.getElementById('h_fecha_pago').value    = fecha;
    document.getElementById('h_metodo_pago').value   = metodo;
    document.getElementById('h_codigo').value        = '';
    // Las observaciones las arma el backend con los datos del lote

    const cont = document.getElementById('h_contrato_ids_container');
    cont.innerHTML = '';
    checks.forEach(cb => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'contrato_camion_ids[]';
        inp.value = cb.dataset.ccId;
        cont.appendChild(inp);

        // Monto a pagar de este flete: el saldo completo o el adelanto indicado
        const monto = document.createElement('input');
        monto.type  = 'hidden';
        monto.name  = 'montos[' + cb.dataset.ccId + ']';
        monto.value = montoAPagarFlete(cb.value).toFixed(2);
        cont.appendChild(monto);
    });

    // Rellenar resumen del pago
    const cuentaTexto = cuentaSel.options[cuentaSel.selectedIndex]?.text?.split('—')[0]?.trim() || '—';
    document.getElementById('p2_res_cuenta').textContent = cuentaTexto;
    document.getElementById('p2_res_fecha').textContent  = fecha;
    document.getElementById('p2_res_metodo').textContent = metodo === 'qr' ? 'QR (auto)' : 'Transferencia (auto)';
    let totalGeneral = 0, monedaGeneral = 'BOB';
    checks.forEach(cb => {
        const info = _contratosData[cb.dataset.ccId];
        // Lo que se va a pagar, no la deuda total: puede ser un adelanto
        if (info) { totalGeneral += montoAPagarFlete(cb.value); monedaGeneral = info.moneda; }
    });
    document.getElementById('p2_res_total').textContent = monedaGeneral + ' ' + _fmtMonto(totalGeneral);
    document.getElementById('p2_resumen').style.display = '';

    // Construir cards por proveedor
    const contenido = document.getElementById('paso2_contenido');
    contenido.innerHTML = '';

    const grupos = {};
    checks.forEach(cb => {
        const ccId     = cb.dataset.ccId;
        const provId   = cb.dataset.proveedor;
        const provNombre = cb.closest('table')?.closest('.card')?.querySelector('.fw-semibold')?.textContent?.trim() || '—';
        if (!grupos[provId]) grupos[provId] = { nombre: provNombre, contratos: [] };
        grupos[provId].contratos.push({ ccId, info: _contratosData[ccId] });
    });

    Object.entries(grupos).forEach(([provId, grupo]) => {
        const provCard = document.createElement('div');
        provCard.className = 'card border mb-3';

        const header = document.createElement('div');
        header.className = 'card-header bg-light d-flex align-items-center gap-2 py-2';
        header.innerHTML = `<i class="bi bi-person-badge text-primary"></i>
            <span class="fw-semibold">${grupo.nombre}</span>
            <span class="badge bg-secondary ms-auto">${grupo.contratos.length} flete(s)</span>`;
        provCard.appendChild(header);

        const cardBody = document.createElement('div');
        cardBody.className = 'card-body p-0';

        grupo.contratos.forEach(({ ccId, info }) => {
            if (!info) return;

            const cuentasDisp = [];
            if (info.conductor_id) {
                const ctas = _cuentasPorOperador[info.conductor_id] || [];
                ctas.forEach(c => cuentasDisp.push({ ...c, rol: 'Conductor', rolNombre: info.conductor_nombre }));
            }

            const fletem = document.createElement('div');
            fletem.className = 'border-bottom';
            fletem.dataset.ccId = ccId;

            const fleteHeader = document.createElement('div');
            fleteHeader.className = 'd-flex align-items-center gap-3 px-3 py-2 bg-white';
            fleteHeader.style.borderLeft = '4px solid #dee2e6';
            fleteHeader.innerHTML = `
                <i class="bi bi-truck text-secondary"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold small">${info.label}</span>
                    ${info.conductor_nombre ? `<span class="text-muted small ms-2">Cond: ${info.conductor_nombre}</span>` : ''}
                    ${info.cliente_nombre   ? `<span class="text-muted small ms-2"><i class="bi bi-person me-1"></i>${info.cliente_nombre}</span>` : ''}
                </div>
                <span class="badge bg-danger" title="Monto a pagar de este flete">${info.moneda} ${_fmtMonto(montoAPagarFlete(ccId))}</span>
                ${montoAPagarFlete(ccId) < info.saldo
                    ? `<span class="badge bg-warning text-dark ms-1" title="No cubre el saldo: se registra como adelanto">Adelanto de ${info.moneda} ${_fmtMonto(info.saldo)}</span>`
                    : ''}
                <span id="badge_ok_${ccId}" class="badge bg-success ms-1" style="display:none;">
                    <i class="bi bi-check-circle me-1"></i>Asignada
                </span>`;
            fletem.appendChild(fleteHeader);

            if (cuentasDisp.length === 0) {
                const aviso = document.createElement('div');
                aviso.className = 'px-3 py-2 bg-warning bg-opacity-10 small text-warning-emphasis';
                aviso.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Sin cuentas bancarias registradas para el conductor.
                    El pago se registrará sin cuenta destino.
                    <input type="hidden" name="cuenta_destino[${ccId}]" value="">`;
                fletem.appendChild(aviso);
            } else {
                const tbl = document.createElement('table');
                tbl.className = 'table table-sm table-hover mb-0 align-middle';
                tbl.innerHTML = `<thead class="table-light"><tr>
                    <th style="width:44px"></th>
                    <th>Rol</th><th>Titular / Cuenta</th>
                    <th>Banco</th><th>N° Cuenta</th>
                    <th class="text-center">Moneda</th>
                    </tr></thead>`;

                const tbody = document.createElement('tbody');
                cuentasDisp.forEach(c => {
                    const tr = document.createElement('tr');
                    tr.style.cursor = 'pointer';
                    tr.dataset.ccId     = ccId;
                    tr.dataset.cuentaId = c.id;

                    const badgeRel   = c.tipo_relacion ? `<span class="badge bg-secondary ms-1" style="font-size:.65rem">${c.tipo_relacion}</span>` : '';
                    const titularCell = c.titular
                        ? `<span class="fw-semibold">👤 ${c.titular}</span>${badgeRel}<br><span class="text-muted" style="font-size:.75rem">${c.rolNombre}</span>`
                        : `<span class="text-muted">${c.rolNombre}</span>`;

                    tr.innerHTML = `
                        <td class="text-center">
                            <input type="radio" name="cuenta_destino[${ccId}]" value="${c.id}"
                                   class="form-check-input cuenta-radio" data-cc="${ccId}"
                                   style="width:1.1rem;height:1.1rem;">
                        </td>
                        <td><span class="badge bg-info text-dark">${c.rol}</span></td>
                        <td class="small">${titularCell}</td>
                        <td class="small fw-semibold">${c.banco}</td>
                        <td class="small"><code>${c.numero}</code>${c.alias ? `<em class="text-muted ms-1">(${c.alias})</em>` : ''}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${c.moneda}</span></td>`;

                    tr.addEventListener('click', function(e) {
                        if (e.target.type === 'radio') return;
                        const radio = this.querySelector('input[type=radio]');
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    });

                    const radio = tr.querySelector('input[type=radio]');
                    radio.addEventListener('change', function() {
                        tbody.querySelectorAll('tr').forEach(r => {
                            r.classList.remove('table-primary');
                            r.style.borderLeft = '';
                        });
                        tr.classList.add('table-primary');
                        tr.style.borderLeft = '3px solid #0d6efd';
                        const badge = document.getElementById('badge_ok_' + ccId);
                        if (badge) badge.style.display = 'inline-block';
                        const fHeader = document.querySelector(`[data-cc-id="${ccId}"] div[style*="border-left"]`);
                        if (fHeader) fHeader.style.borderLeftColor = '#198754';
                        validarPaso2();
                    });

                    tbody.appendChild(tr);
                });
                tbl.appendChild(tbody);
                fletem.appendChild(tbl);
            }

            cardBody.appendChild(fletem);
        });

        provCard.appendChild(cardBody);
        contenido.appendChild(provCard);
    });

    document.getElementById('paso1').style.display = 'none';
    document.getElementById('paso2').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    validarPaso2();
}

function volverAPaso1() {
    document.getElementById('paso2').style.display = 'none';
    document.getElementById('paso1').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validarPaso2() {
    const radiosGroups = {};
    document.querySelectorAll('#paso2_contenido .cuenta-radio').forEach(r => {
        if (!radiosGroups[r.dataset.cc]) radiosGroups[r.dataset.cc] = [];
        radiosGroups[r.dataset.cc].push(r);
    });
    const todosOk = Object.values(radiosGroups).every(radios => radios.some(r => r.checked));
    document.getElementById('btn_registrar').disabled = !todosOk;
}

// ===== Confirmación + Vista Previa =====

let _previewRows = [];

const COLS = [
    'NRO. DE ORDEN', 'CODIGO DE CLIENTE', 'NRO. DE CUENTA', 'NOMBRE DEL CLIENTE',
    'DOC. DE IDENTIDAD', 'IMPORTE', 'FECHA DE PAGO', 'FORMA DE PAGO',
    'MONEDA DESTINO', 'ENTIDAD DESTINO', 'SUCURSAL DESTINO', 'GLOSA',
    'CODIGO UNICO', 'EMAIL NOTIFICACION', 'NRO_DOC_TERCERO', 'NOMBRE TERCERO',
];

function _buildRows() {
    const rows   = [];
    const fecha  = document.getElementById('h_fecha_pago').value || new Date().toISOString().slice(0,10);
    let orden = 1;

    document.querySelectorAll('#paso2_contenido div.border-bottom[data-cc-id]').forEach(fletem => {
        const ccId   = fletem.dataset.ccId;
        const info   = _contratosData[ccId];
        if (!info) return;

        const radioChecked = fletem.querySelector('.cuenta-radio:checked');
        let nroCuenta = '', nombreCliente = '', docIdentidad = '', monedaCuenta = '', codigoBanco = '', siglaSucursal = '', emailNotificacion = '', esGanadero = false;

        if (radioChecked) {
            const cuentaId = parseInt(radioChecked.value);
            Object.entries(_cuentasPorOperador).forEach(([opId, ctas]) => {
                ctas.forEach(c => {
                    if (c.id === cuentaId) {
                        nroCuenta         = c.numero;
                        nombreCliente     = c.titular || info.conductor_nombre || '';
                        docIdentidad      = c.nro_documento || '';
                        monedaCuenta      = c.moneda || '';
                        codigoBanco       = c.codigo_banco || '';
                        siglaSucursal     = c.sigla_sucursal || '';
                        emailNotificacion = c.email_notificacion || '';
                        esGanadero        = c.banco === 'Banco Ganadero';
                    }
                });
            });
        }

        const [y, m, d] = fecha.split('-');
        const fechaFmt  = d ? `${d}/${m}/${y}` : fecha;

        rows.push({
            orden, codigo_cliente: 0, nro_cuenta: nroCuenta, nombre_cliente: nombreCliente,
            doc_identidad: docIdentidad, importe: montoAPagarFlete(ccId).toFixed(2).replace('.', ','), fecha_pago: fechaFmt,
            forma_pago: esGanadero ? 1 : 3,
            moneda_destino:  esGanadero ? 0 : (monedaCuenta === 'USD' ? 2 : 1),
            entidad_destino: esGanadero ? 0 : codigoBanco,
            sucursal:        esGanadero ? 0 : siglaSucursal,
            glosa: '', codigo_unico: '', email: emailNotificacion, nro_doc_tercero: '', nombre_tercero: '',
            _moneda: info.moneda, _saldo: montoAPagarFlete(ccId),
        });
        orden++;
    });
    return rows;
}

function abrirConfirmacion() {
    _previewRows = _buildRows();
    const total  = _previewRows.reduce((s, r) => s + r._saldo, 0);
    const moneda = _previewRows[0]?._moneda || 'BOB';
    document.getElementById('conf_cant').textContent  = _previewRows.length;
    document.getElementById('conf_total').textContent = moneda + ' ' + _fmtMonto(total);

    const tbody = document.getElementById('conf_tbody');
    tbody.innerHTML = '';
    _previewRows.forEach(r => {
        const tr    = document.createElement('tr');
        const cells = [
            r.orden, r.codigo_cliente, r.nro_cuenta, r.nombre_cliente,
            r.doc_identidad, r.importe, r.fecha_pago, r.forma_pago,
            r.moneda_destino, r.entidad_destino, r.sucursal, r.glosa,
            r.codigo_unico, r.email, r.nro_doc_tercero, r.nombre_tercero,
        ];
        const conDatos = [0, 2, 3, 5, 6];
        cells.forEach((val, i) => {
            const td = document.createElement('td');
            td.style.whiteSpace = 'nowrap';
            if (conDatos.includes(i) && val !== '') {
                td.style.background = '#f0fff4';
                td.style.fontWeight = '500';
                td.textContent = val;
            } else if (val === '') {
                td.style.color = '#bbb';
                td.textContent = '—';
            } else {
                td.textContent = val;
            }
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmacion')).show();
}

function confirmarYEnviar() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmacion')).hide();
    document.getElementById('formPaso2').submit();
}

function descargarExcel() {
    if (!_previewRows.length) return;
    const dataRows = _previewRows.map(r => [
        r.orden, r.codigo_cliente, r.nro_cuenta, r.nombre_cliente, r.doc_identidad,
        r.importe, r.fecha_pago, r.forma_pago, r.moneda_destino, r.entidad_destino,
        r.sucursal, r.glosa, r.codigo_unico, r.email, r.nro_doc_tercero, r.nombre_tercero,
    ]);
    _exportarXlsx(COLS, dataRows, 'Hoja 1', 'pago_masivo_' + (document.getElementById('h_fecha_pago').value || 'hoy') + '.xlsx');
}

// ---- Generador XLSX con cabecera estilizada (fondo verde oscuro + texto blanco + negrita) ----
function _exportarXlsx(headers, rows, sheetName, filename) {
    const esc = v => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const styleXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A6B2F"/></patternFill></fill>
  </fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>`;
    const colLetter = i => { let s='', n=i+1; while(n>0){s=String.fromCharCode(65+(n-1)%26)+s;n=Math.floor((n-1)/26);} return s; };
    const colWidths = [20.6, 23.6, 23.9, 40.4, 25.6, 20.6, 22.6, 22.4, 24.1, 24.6, 25.7, 20.3, 19.0, 25.9, 25.9, 25.9];
    let colsXml = '<cols>';
    colWidths.forEach((w, ci) => { colsXml += `<col min="${ci+1}" max="${ci+1}" width="${w}" customWidth="1"/>`; });
    colsXml += '</cols>';
    let sheetData = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">${colsXml}<sheetData><row r="1">`;
    headers.forEach((h, ci) => { sheetData += `<c r="${colLetter(ci)}1" t="inlineStr" s="1"><is><t>${esc(h)}</t></is></c>`; });
    sheetData += `</row>`;
    rows.forEach((row, ri) => {
        sheetData += `<row r="${ri+2}">`;
        row.forEach((val, ci) => { sheetData += `<c r="${colLetter(ci)}${ri+2}" t="inlineStr"><is><t>${esc(val)}</t></is></c>`; });
        sheetData += `</row>`;
    });
    sheetData += `</sheetData></worksheet>`;
    const wb = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="${esc(sheetName)}" sheetId="1" r:id="rId1"/></sheets>
</workbook>`;
    const rels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`;
    const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>`;
    const rootRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>`;
    const zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    const xl = zip.folder('xl');
    xl.file('workbook.xml', wb);
    xl.file('styles.xml', styleXml);
    xl.folder('_rels').file('workbook.xml.rels', rels);
    xl.folder('worksheets').file('sheet1.xml', sheetData);
    zip.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
       .then(blob => { const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href=url; a.download=filename; a.click(); URL.revokeObjectURL(url); });
}

function abrirEnVentana() {
    if (!_previewRows.length) return;
    const headerBg = '#1d7a3a';
    const ths = COLS.map(c => `<th style="background:${headerBg};color:#fff;white-space:nowrap;padding:6px 10px;border:1px solid #ccc;">${c}</th>`).join('');
    const trs = _previewRows.map(r => {
        const cells = [r.orden, r.codigo_cliente, r.nro_cuenta, r.nombre_cliente, r.doc_identidad,
            r.importe, r.fecha_pago, r.forma_pago, r.moneda_destino, r.entidad_destino,
            r.sucursal, r.glosa, r.codigo_unico, r.email, r.nro_doc_tercero, r.nombre_tercero];
        const conDatos = [0, 2, 3, 5, 6];
        const tds = cells.map((v, i) => {
            const bg = conDatos.includes(i) && v !== '' ? '#f0fff4' : '#fff';
            const clr = v === '' ? '#bbb' : '#000';
            const fw  = conDatos.includes(i) && v !== '' ? '600' : 'normal';
            return `<td style="background:${bg};color:${clr};font-weight:${fw};padding:5px 10px;border:1px solid #ddd;white-space:nowrap;">${v !== '' ? v : '—'}</td>`;
        }).join('');
        return `<tr>${tds}</tr>`;
    }).join('');
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Vista previa — Pago Masivo</title>
        <style>body{font-family:Arial,sans-serif;font-size:12px;padding:16px;}h2{color:#1d7a3a;}table{border-collapse:collapse;}</style></head>
        <body><h2>Vista previa — Pago Masivo de Fletes</h2>
        <p style="color:#555;">${_previewRows.length} flete(s) — Fecha: ${document.getElementById('h_fecha_pago').value}</p>
        <table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`;
    const win = window.open('', '_blank'); win.document.write(html); win.document.close();
}
</script>
@endsection
