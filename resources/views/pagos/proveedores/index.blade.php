@extends('layouts.app')
@section('titulo', 'Pagos a Proveedores')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PAGOS A PROVEEDORES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Pagos Proveedores</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                @if($contratos->isEmpty())
                data-steps='[
                    {"intro":"💵 Esta es la pantalla de <b>Pagos a Proveedores</b>. Aquí verás cada contrato con su total, lo pagado y el saldo, y podrás registrar pagos.<br><br>📭 Por ahora <b>no hay contratos con proveedor</b> registrados, así que no hay nada que mostrar todavía."}
                ]'
                @else
                data-steps='[
                    {"intro":"💵 Esta es la pantalla de <b>Pagos a Proveedores</b>. Controla, contrato por contrato, cuánto se le ha pagado a cada proveedor y cuánto falta. Te muestro cómo se usa."},
                    {"element":"#filtro_proveedor","intro":"🔎 Usa este filtro para ver solo los contratos de <b>un proveedor</b>. El contador de la derecha te dice cuántos se muestran.","position":"bottom"},
                    {"element":"#tabla_pagos_prov","intro":"📋 Cada fila es un contrato. Las filas en <b>verde</b> ya están totalmente pagadas (saldo 0).","position":"top"},
                    {"element":"#tabla_pagos_prov thead th:nth-child(6)","intro":"💰 Fíjate en estas columnas: <b>Total acordado</b>, <b>Pagado</b> y <b>Saldo</b>. El saldo en rojo es lo que aún se le debe al proveedor.","position":"bottom"},
                    {"element":"#tabla_pagos_prov thead th:nth-child(7)","intro":"📊 Esta columna muestra dos barras: el avance de <b>toneladas</b> (entregado/en ruta) y el avance del <b>pago</b> (% pagado).","position":"bottom"},
                    {"element":"#col-acciones-pp","intro":"⚙️ En <b>Acciones</b>: el botón 👁 muestra el <b>detalle de pagos</b> del contrato, y el botón ➕ (verde) abre el formulario para <b>registrar un pago</b> nuevo.","position":"left"}
                ]'
                @endif>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Pagos por Contrato</h5>
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Registra y controla los pagos realizados a los proveedores por cada contrato.
                    Cada pago puede tener su propia moneda y tipo de cambio.
                </p>

                {{-- ===== SELECTOR DE PROVEEDOR ===== --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-box-seam"></i> Filtrar por proveedor</label>
                        <select class="form-select" id="filtro_proveedor" onchange="filtrarPorProveedor(this.value)">
                            <option value="">— Todos los proveedores —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="filtrarPorProveedor('')">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <small class="text-muted">Mostrando <span id="lbl_count_prov">{{ $contratos->count() }}</span> contrato(s)</small>
                    </div>
                </div>

                @if($contratos->isEmpty())
                    <div class="alert alert-info py-2">
                        <small><i class="bi bi-info-circle"></i> No hay contratos con proveedor registrados.</small>
                    </div>
                @else
                <div class="table-responsive">
                    <table id="tabla_pagos_prov" class="table table-hover table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Contrato</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th class="text-end">Total acordado</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">Saldo</th>
                                <th>Toneladas / Pagado</th>
                                <th id="col-acciones-pp">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contratos as $c)
                            @php
                                $mon     = $c->moneda ?? 'BOB';
                                $pagado  = $c->total_pagado_proveedor;
                                $saldo   = $c->saldo_pendiente_proveedor;
                                $total   = (float) $c->monto_total;
                                $pct     = $total > 0 ? min(100, round($pagado / $total * 100)) : 0;
                                $rowClass = $saldo <= 0 ? 'table-success' : '';
                            @endphp
                            <tr class="{{ $rowClass }}" data-proveedor-id="{{ $c->proveedor_id }}">
                                <td>
                                    <a href="{{ route('contratos.camiones', $c->uuid) }}" class="text-decoration-none fw-semibold">
                                        {{ $c->numero_contrato }}
                                    </a>
                                    <small class="text-muted d-block">{{ $c->fecha_inicio?->format('d/m/Y') ?? '—' }}</small>
                                </td>
                                <td>{{ $c->proveedor->nombre ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $c->tipo_contrato === 'Internacional' ? 'info text-dark' : 'secondary' }}">
                                        {{ $c->tipo_contrato }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $mon }} {{ number_format($total, 2) }}</td>
                                <td class="text-end text-success">{{ $mon }} {{ number_format($pagado, 2) }}</td>
                                <td class="text-end {{ $saldo > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                    {{ $mon }} {{ number_format($saldo, 2) }}
                                </td>
                                <td style="min-width:160px;">
                                    @php
                                        $tContrato   = $c->toneladas_contrato ?? 0;
                                        $tEntregadas = $c->toneladas_entregadas;
                                        $tTransito   = $c->toneladas_en_transito;
                                        $pctEnt      = $tContrato > 0 ? min(100, round($tEntregadas / $tContrato * 100, 1)) : 0;
                                        $pctTra      = $tContrato > 0 ? min(100 - $pctEnt, round($tTransito / $tContrato * 100, 1)) : 0;
                                        $pctPago     = $total > 0 ? min(100, round($pagado / $total * 100, 1)) : 0;
                                        $tPendiente  = max(0, $tContrato - $tEntregadas - $tTransito);
                                    @endphp
                                    {{-- Barra toneladas: entregado + en ruta + pendiente --}}
                                    @if($tContrato > 0)
                                        <div class="progress mb-1" style="height:6px;">
                                            @if($pctEnt > 0)
                                                <div class="progress-bar bg-success" style="width:{{ $pctEnt }}%"></div>
                                            @endif
                                            @if($pctTra > 0)
                                                <div class="progress-bar" style="width:{{ $pctTra }}%; background:#38bdf8;"></div>
                                            @endif
                                        </div>
                                        <div class="small text-muted mb-2" style="font-size:.7rem;">
                                            @if($tEntregadas > 0)
                                                <span class="text-success fw-semibold">{{ number_format($tEntregadas, 2) }}t cliente</span>
                                                @if($tTransito > 0 || $tPendiente > 0) &nbsp;·&nbsp; @endif
                                            @endif
                                            @if($tTransito > 0)
                                                <span style="color:#0ea5e9;">{{ number_format($tTransito, 2) }}t en ruta</span>
                                                @if($tPendiente > 0) &nbsp;·&nbsp; @endif
                                            @endif
                                            @if($tPendiente > 0)
                                                <span class="text-muted">{{ number_format($tPendiente, 2) }}t pend.</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small d-block mb-2">— sin toneladas</span>
                                    @endif
                                    {{-- Barra pagos --}}
                                    <div class="small lh-1 mb-1">
                                        <span class="fw-semibold {{ $saldo <= 0 ? 'text-success' : '' }}">{{ number_format($pagado, 2) }} {{ $mon }}</span>
                                        <span class="text-muted">/ {{ number_format($total, 2) }}</span>
                                    </div>
                                    <div class="progress mb-1" style="height:6px;" title="Pagado: {{ $pctPago }}%">
                                        <div class="progress-bar {{ $saldo <= 0 ? 'bg-success' : 'bg-primary' }}" style="width:{{ $pctPago }}%"></div>
                                    </div>
                                    <div class="small" style="font-size:.7rem;">
                                        <span class="{{ $saldo <= 0 ? 'text-success fw-semibold' : 'text-primary' }}">{{ $pctPago }}% pagado</span>
                                        @if($saldo > 0)
                                            &nbsp;·&nbsp;<span class="text-danger">{{ number_format($saldo, 2) }} pendiente</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @can('pagos_proveedores.index')
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="verDetalle({{ $c->id }})" title="Ver pagos">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @endcan
                                    @can('pagos_proveedores.create')
                                    <button class="btn btn-sm btn-outline-success"
                                        onclick="abrirModalPago({{ $c->id }}, '{{ addslashes($c->numero_contrato) }} — {{ addslashes($c->proveedor->nombre ?? '') }}', {{ $saldo }}, '{{ $mon }}', {{ $c->proveedor_id ?? 'null' }})"
                                        title="Registrar pago">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</section>

{{-- ===== MODAL REGISTRAR PAGO ===== --}}
<div class="modal fade" id="modalPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Registrar Pago a Proveedor</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-light btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalPago"
                            data-steps='[
                                {"intro":"💳 Este formulario registra un <b>pago a un proveedor</b> por un contrato. Te explico cada parte. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#sec_seleccionar_contrato","intro":"📄 <b>Contrato</b>: elige a qué contrato corresponde el pago. Si abriste el modal desde el botón ➕ de una fila, ya viene seleccionado.","position":"bottom"},
                                {"element":"[name=\"tipo_pago\"]","intro":"🏷️ <b>Tipo de Pago</b>: <b>Adelanto</b> (a cuenta) o <b>Pago Final</b> (saldo del contrato).","position":"bottom"},
                                {"element":"#moneda_pago","intro":"💱 <b>Moneda</b> del pago. Si eliges una distinta de BOB, aparecerá el campo de <b>tipo de cambio</b> para convertir a bolivianos.","position":"bottom"},
                                {"element":"#inp_monto","intro":"🔢 <b>Monto</b> que se paga. Si la moneda no es BOB, abajo verás el equivalente en bolivianos calculado automáticamente.","position":"bottom"},
                                {"element":"[name=\"fecha_pago\"]","intro":"📅 <b>Fecha del pago</b>. Por defecto es hoy.","position":"top"},
                                {"element":"#metodo_pago","intro":"🏦 <b>Método de Pago</b>: transferencia, QR o cheque. Si es transferencia, se habilita un campo para el código/N° de referencia.","position":"top"},
                                {"element":"[name=\"cuenta_origen_id\"]","intro":"📤 <b>Cuenta Origen</b>: de qué cuenta de la empresa (tesorería) sale el dinero. Si fue en efectivo, déjala vacía.","position":"top"},
                                {"element":"#sel_cuenta_destino","intro":"📥 <b>Cuenta Destino</b>: a qué cuenta del proveedor se le pagó. Se cargan según el proveedor del contrato.","position":"top"},
                                {"element":"[name=\"observaciones\"]","intro":"📝 <b>Observaciones</b> (opcional): cualquier nota sobre el pago.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i> Ayuda
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form method="POST" action="{{ route('pagos.proveedores.store') }}">
                @csrf
                <input type="hidden" name="contrato_id" id="pago_contrato_id">
                <div class="modal-body">

                    {{-- Info del contrato --}}
                    <div id="pago_info_contrato" class="alert alert-light border mb-3 py-2" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong id="pago_contrato_label"></strong>
                            <div class="text-end">
                                <small class="text-muted">Saldo pendiente:</small>
                                <span class="badge bg-danger ms-1" id="pago_saldo_label"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">

                        {{-- Selector de contrato (cuando se abre sin contrato preseleccionado) --}}
                        <div class="col-12" id="sec_seleccionar_contrato">
                            <label class="form-label fw-semibold">Contrato <span class="text-danger">(*)</span></label>
                            <select class="form-select" id="sel_contrato" onchange="cambiarContrato(this.value)">
                                <option value="">-- Seleccione --</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}"
                                        data-label="{{ $c->numero_contrato }} — {{ $c->proveedor->nombre ?? '' }}"
                                        data-saldo="{{ $c->saldo_pendiente_proveedor }}"
                                        data-moneda="{{ $c->moneda ?? 'BOB' }}"
                                        data-proveedor-id="{{ $c->proveedor_id }}">
                                        {{ $c->numero_contrato }} — {{ $c->proveedor->nombre ?? '—' }}
                                        (Saldo: {{ $c->moneda }} {{ number_format($c->saldo_pendiente_proveedor, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_pago" required>
                                <option value="">-- Seleccione --</option>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>

                        {{-- Bloque moneda / monto / tipo de cambio --}}
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-2 align-items-end">

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold mb-1">Moneda <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="moneda_pago" id="moneda_pago" required onchange="toggleTipoCambio(this.value)">
                                            <option value="BOB">🇧🇴 BOB</option>
                                            <option value="USD">🇺🇸 USD</option>
                                            <option value="BRL">🇧🇷 BRL</option>
                                            <option value="ARS">🇦🇷 ARS</option>
                                            <option value="EUR">🇪🇺 EUR</option>
                                            <option value="PEN">🇵🇪 PEN</option>
                                            <option value="CLP">🇨🇱 CLP</option>
                                            <option value="PYG">🇵🇾 PYG</option>
                                            <option value="COP">🇨🇴 COP</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold mb-1">Monto <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold" id="lbl_moneda_monto">BOB</span>
                                            <input type="number" step="0.01" min="0.01" class="form-control"
                                                name="monto" id="inp_monto" required placeholder="0.00"
                                                oninput="calcEquivalente()">
                                        </div>
                                    </div>

                                    <div class="col-md-5" id="sec_tipo_cambio" style="display:none;">
                                        <label class="form-label fw-semibold mb-1">
                                            Tipo de cambio <span class="text-danger">*</span>
                                            <small class="text-muted fw-normal">— 1 <span id="lbl_moneda_tc"></span> equivale a:</small>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.0001" min="0.0001" class="form-control"
                                                name="tipo_cambio" id="inp_tipo_cambio"
                                                placeholder="0.0000" oninput="calcEquivalente()" disabled>
                                            <span class="input-group-text">BOB</span>
                                        </div>
                                    </div>

                                </div>

                                <div id="sec_equivalente" style="display:none;" class="mt-2">
                                    <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2" style="background:#e8f4fd;border:1px solid #b8d9f5;">
                                        <i class="bi bi-arrow-left-right text-primary"></i>
                                        <span class="text-muted small">Equivalente en bolivianos:</span>
                                        <strong class="text-primary fs-6" id="lbl_equivalente">—</strong>
                                        <span class="text-muted small">BOB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="tipo_cambio" id="inp_tipo_cambio_bob" value="1">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Pago <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_pago" required value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="metodo_pago" id="metodo_pago" required onchange="toggleCodigo(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="sec_codigo" style="display:none;">
                            <label class="form-label">Código / N° Cheque</label>
                            <input type="text" class="form-control" name="codigo_seguimiento" maxlength="100"
                                placeholder="Ej: TRX-20260512-001">
                        </div>

                        {{-- Cuenta origen (tesorería empresa) --}}
                        <div class="col-md-6">
                            <label class="form-label">Cuenta Origen (Tesorería)</label>
                            <select class="form-select" name="cuenta_origen_id">
                                <option value="">-- Efectivo / Sin cuenta --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cta)
                                            <option value="{{ $cta->id }}">
                                                {{ $cta->nombre_cuenta }}
                                                @if($cta->banco) — {{ $cta->banco->nombre }} @endif
                                                [{{ $cta->moneda }}]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        {{-- Cuenta destino (proveedor) --}}
                        <div class="col-md-6">
                            <label class="form-label">Cuenta Destino (Proveedor)</label>
                            <select class="form-select" name="cuenta_destino_id" id="sel_cuenta_destino">
                                <option value="">-- Efectivo / Sin cuenta --</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500"
                                placeholder="Notas del pago..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR PAGO ===== --}}
<div class="modal fade" id="modalEditarPagoProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Pago a Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditarPagoProveedor">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_pago" id="edit_pp_tipo" required>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_pago" id="edit_pp_fecha" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Moneda <span class="text-danger">*</span></label>
                            <select class="form-select" name="moneda_pago" id="edit_pp_moneda" required onchange="editPpToggleTc(this.value)">
                                <option value="BOB">🇧🇴 BOB</option>
                                <option value="USD">🇺🇸 USD</option>
                                <option value="BRL">🇧🇷 BRL</option>
                                <option value="ARS">🇦🇷 ARS</option>
                                <option value="EUR">🇪🇺 EUR</option>
                                <option value="PEN">🇵🇪 PEN</option>
                                <option value="CLP">🇨🇱 CLP</option>
                                <option value="PYG">🇵🇾 PYG</option>
                                <option value="COP">🇨🇴 COP</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="monto" id="edit_pp_monto" required>
                        </div>
                        <div class="col-md-4" id="edit_pp_sec_tc">
                            <label class="form-label fw-semibold">Tipo de cambio <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" class="form-control" name="tipo_cambio" id="edit_pp_tc">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="edit_pp_metodo" required>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código / N° referencia</label>
                            <input type="text" class="form-control" name="codigo_seguimiento" id="edit_pp_codigo" maxlength="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL DETALLE ===== --}}
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-list-ul"></i> Detalle de Pagos — <span id="det_titulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="det_body">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
// Permisos del usuario
const canEditPago = {{ auth()->user()->can('pagos_proveedores.edit') ? 'true' : 'false' }};
const canDeletePago = {{ auth()->user()->can('pagos_proveedores.destroy') ? 'true' : 'false' }};

function filtrarPorProveedor(proveedorId) {
    const filas = document.querySelectorAll('#tabla_pagos_prov tbody tr');
    let visibles = 0;
    filas.forEach(function(fila) {
        const mostrar = !proveedorId || fila.dataset.proveedorId == proveedorId;
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    document.getElementById('lbl_count_prov').textContent = visibles;
    const sel = document.getElementById('filtro_proveedor');
    if (sel) sel.value = proveedorId;
}

let _proveedorActual = null;
let _monedaPendiente = null;

document.addEventListener('DOMContentLoaded', function () {
    const modalPago = document.getElementById('modalPago');
    modalPago.addEventListener('shown.bs.modal', function () {
        if (_monedaPendiente) {
            toggleTipoCambio(_monedaPendiente);
            _monedaPendiente = null;
        }
    });
    modalPago.addEventListener('hidden.bs.modal', function () {
        document.getElementById('sec_seleccionar_contrato').style.display = 'block';
        document.getElementById('pago_info_contrato').style.display = 'none';
    });
});

function toggleTipoCambio(moneda) {
    const secTC    = document.getElementById('sec_tipo_cambio');
    const secEquiv = document.getElementById('sec_equivalente');
    const inpTC    = document.getElementById('inp_tipo_cambio');
    const inpBob   = document.getElementById('inp_tipo_cambio_bob');
    const lblMonto = document.getElementById('lbl_moneda_monto');
    const lblTC    = document.getElementById('lbl_moneda_tc');

    document.getElementById('moneda_pago').value = moneda;
    lblMonto.textContent = moneda;

    if (moneda === 'BOB') {
        secTC.style.display    = 'none';
        secEquiv.style.display = 'none';
        inpTC.disabled  = true;
        inpTC.value     = '';
        inpBob.disabled = false;
        inpBob.value    = '1';
    } else {
        secTC.style.display = 'block';
        inpTC.disabled  = false;
        inpBob.disabled = true;
        lblTC.textContent = moneda;
        calcEquivalente();
    }
}

function calcEquivalente() {
    const moneda = document.getElementById('moneda_pago').value;
    if (moneda === 'BOB') return;
    const monto    = parseFloat(document.getElementById('inp_monto').value) || 0;
    const tc       = parseFloat(document.getElementById('inp_tipo_cambio').value) || 0;
    const secEquiv = document.getElementById('sec_equivalente');
    const lblEquiv = document.getElementById('lbl_equivalente');
    if (monto > 0 && tc > 0) {
        lblEquiv.textContent = 'Bs ' + (monto * tc).toFixed(2);
        secEquiv.style.display = 'block';
    } else {
        secEquiv.style.display = 'none';
    }
}

function abrirModalPago(contratoId, label, saldo, moneda, proveedorId) {
    document.getElementById('pago_contrato_id').value        = contratoId;
    document.getElementById('sel_contrato').value            = contratoId;
    document.getElementById('pago_contrato_label').textContent = label;
    document.getElementById('pago_saldo_label').textContent  = parseFloat(saldo).toFixed(2) + ' ' + (moneda || 'BOB');
    document.getElementById('pago_info_contrato').style.display = 'block';
    document.getElementById('sec_seleccionar_contrato').style.display = 'none';

    _monedaPendiente  = moneda || 'BOB';
    _proveedorActual  = proveedorId;

    cargarCuentasProveedor(proveedorId);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPago')).show();
}

function cambiarContrato(contratoId) {
    if (!contratoId) return;
    const opt = document.querySelector(`#sel_contrato option[value="${contratoId}"]`);
    if (!opt) return;

    document.getElementById('pago_contrato_id').value          = contratoId;
    document.getElementById('pago_contrato_label').textContent = opt.dataset.label;
    const mon = opt.dataset.moneda || 'BOB';
    document.getElementById('pago_saldo_label').textContent    = parseFloat(opt.dataset.saldo).toFixed(2) + ' ' + mon;
    document.getElementById('pago_info_contrato').style.display = 'block';

    document.getElementById('moneda_pago').value = mon;
    toggleTipoCambio(mon);

    _proveedorActual = opt.dataset.proveedorId || null;
    cargarCuentasProveedor(_proveedorActual);
}

function cargarCuentasProveedor(proveedorId) {
    const sel = document.getElementById('sel_cuenta_destino');
    sel.innerHTML = '<option value="">-- Cargando... --</option>';
    if (!proveedorId) {
        sel.innerHTML = '<option value="">-- Efectivo / Sin cuenta --</option>';
        return;
    }
    fetch(`/api/pagos/cuentas-proveedor?proveedor_id=${proveedorId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Efectivo / Sin cuenta --</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.label;
                sel.appendChild(opt);
            });
        });
}

function toggleCodigo(metodo) {
    document.getElementById('sec_codigo').style.display =
        metodo === 'transferencia' ? 'block' : 'none';
}

function abrirEditarPagoProveedor(uuid, tipo, monto, moneda, tc, fecha, metodo, codigo) {
    document.getElementById('formEditarPagoProveedor').action = '/pagos/proveedores/' + uuid;
    document.getElementById('edit_pp_tipo').value    = tipo;
    document.getElementById('edit_pp_fecha').value   = fecha;
    document.getElementById('edit_pp_moneda').value  = moneda;
    document.getElementById('edit_pp_monto').value   = monto;
    document.getElementById('edit_pp_tc').value      = tc;
    document.getElementById('edit_pp_metodo').value  = metodo;
    document.getElementById('edit_pp_codigo').value  = codigo || '';
    editPpToggleTc(moneda);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarPagoProveedor')).show();
}

function editPpToggleTc(moneda) {
    const sec = document.getElementById('edit_pp_sec_tc');
    const inp = document.getElementById('edit_pp_tc');
    if (moneda === 'BOB') {
        sec.style.display = 'none';
        inp.disabled = true;
        inp.value = '1';
    } else {
        sec.style.display = 'block';
        inp.disabled = false;
    }
}

function verDetalle(contratoId) {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalle'));
    document.getElementById('det_body').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch(`/api/pagos/proveedores/${contratoId}/detalle`)
        .then(r => r.json())
        .then(d => {
            const mon = d.moneda || 'BOB';
            document.getElementById('det_titulo').textContent = d.numero + ' — ' + d.proveedor;

            let html = `
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Total acordado</div>
                        <strong>${mon} ${parseFloat(d.monto_total||0).toFixed(2)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Pagado</div>
                        <strong class="text-success">${mon} ${parseFloat(d.total_pagado||0).toFixed(2)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center bg-${parseFloat(d.saldo_pendiente)<=0?'success':'warning'} bg-opacity-10">
                        <div class="text-muted small">Saldo</div>
                        <strong class="${parseFloat(d.saldo_pendiente)<=0?'text-success':'text-danger'}">
                            ${mon} ${parseFloat(d.saldo_pendiente||0).toFixed(2)}
                        </strong>
                    </div>
                </div>
            </div>`;

            if (!d.pagos || d.pagos.length === 0) {
                html += '<div class="alert alert-info">No hay pagos registrados aún.</div>';
            } else {
                const monedaFlag = { BOB:'🇧🇴', USD:'🇺🇸', BRL:'🇧🇷', ARS:'🇦🇷', EUR:'🇪🇺', PEN:'🇵🇪', CLP:'🇨🇱', PYG:'🇵🇾', COP:'🇨🇴' };
                const badgeTipo  = { 'Adelanto':'bg-warning text-dark', 'Parcial':'bg-info text-dark', 'Pago Final':'bg-success' };

                html += `<div class="d-flex flex-column gap-2">`;
                d.pagos.forEach(p => {
                    const esBob   = p.moneda_pago === 'BOB';
                    const flag    = monedaFlag[p.moneda_pago] || '';
                    const badge   = badgeTipo[p.tipo] || 'bg-secondary';
                    const tcLine  = esBob ? '' : `<span class="text-muted ms-1" style="font-size:.7rem">TC: 1 ${p.moneda_pago} = ${parseFloat(p.tipo_cambio).toFixed(4)} Bs</span>`;

                    // Cuenta destino
                    let destLine = '';
                    if (p.cuenta_destino) {
                        const rel     = p.cuenta_destino.tipo_relacion ? ` <span class="badge bg-secondary" style="font-size:.65rem">${p.cuenta_destino.tipo_relacion}</span>` : '';
                        const titular = p.cuenta_destino.titular_cuenta ? `<span class="fw-semibold text-warning-emphasis">👤 ${p.cuenta_destino.titular_cuenta}</span>${rel} — ` : '';
                        destLine = `<div class="small mt-1">🏦 ${titular}${p.cuenta_destino.banco} <code>${p.cuenta_destino.numero}</code> [${p.cuenta_destino.moneda}]${p.cuenta_destino.alias ? ' <span class="text-muted">('+p.cuenta_destino.alias+')</span>' : ''}</div>`;
                    }

                    // Cuenta origen
                    let origLine = '';
                    if (p.cuenta_origen) {
                        const alias = p.cuenta_origen.alias ? ` (${p.cuenta_origen.alias})` : '';
                        origLine = `<span class="text-muted ms-2" style="font-size:.75rem">· Pagó: ${p.cuenta_origen.titular}${alias}</span>`;
                    }

                    html += `
                    <div class="rounded-2 border px-3 py-2 bg-white">
                        <div class="d-flex align-items-start gap-3">
                            <div class="pt-1"><span class="badge ${badge}">${p.tipo}</span></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${flag} ${p.moneda_pago} ${parseFloat(p.monto).toFixed(2)}${tcLine}</div>
                                <div class="text-muted small">${p.fecha} &nbsp;·&nbsp; ${p.metodo}${p.codigo ? ' &nbsp;·&nbsp; ' + p.codigo : ''}${origLine}</div>
                                ${destLine}
                            </div>
                            <div class="d-flex flex-column gap-1">
                                ${canEditPago ? `<button class="btn btn-sm btn-outline-primary border-0"
                                    onclick="abrirEditarPagoProveedor('${p.uuid}','${p.tipo_raw}',${p.monto},'${p.moneda_pago}',${p.tipo_cambio},'${p.fecha_raw}','${p.metodo_raw}','${p.codigo||''}')"
                                    title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>` : ''}
                                ${canDeletePago ? `<a href="/pagos/proveedores/${p.uuid}/destroy"
                                   class="btn btn-sm btn-outline-danger border-0"
                                   onclick="return confirm('¿Eliminar este pago?')" title="Eliminar">
                                   <i class="bi bi-trash"></i>
                                </a>` : ''}
                            </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }
            document.getElementById('det_body').innerHTML = html;
        });
}
</script>
@endsection
