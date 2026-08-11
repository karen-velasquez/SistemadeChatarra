@extends('layouts.app')
@section('titulo', 'Pagos a Proveedores')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PAGOS A PROVEEDORES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a>Proveedores</a></li>
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
                    {"element":"#filtro_proveedor","intro":"🔎 Filtra los contratos de <b>un proveedor</b>. Si el proveedor no tiene contratos por pagar, la tabla te lo dice en lugar de quedar vacía.","position":"bottom"},
                    {"element":"#filtro_desde","intro":"📅 Acota por <b>rango de fechas</b> según la fecha de inicio del contrato. La fecha <i>Hasta</i> no puede ser anterior a <i>Desde</i>.","position":"bottom"},
                    {"element":"#filtro_orden","intro":"↕️ Ordena la lista por <b>fecha</b> (recientes o antiguos) o por <b>saldo</b> (mayor o menor), para atacar primero lo que más urge.","position":"bottom"},
                    {"element":"#tabla_pagos_prov","intro":"📋 Cada fila es un contrato. Las filas en <b>verde</b> ya están totalmente pagadas (saldo 0).","position":"top"},
                    {"element":"#tabla_pagos_prov thead th:nth-child(4)","intro":"👤 <b>Registrado por</b>: el usuario que creó el contrato, útil para saber a quién consultar por sus condiciones.","position":"bottom"},
                    {"element":"#tabla_pagos_prov thead th:nth-child(5)","intro":"💰 Fíjate en estas columnas: <b>Total acordado</b>, <b>Pagado</b> y <b>Saldo</b>. El saldo en rojo es lo que aún se le debe al proveedor.","position":"bottom"},
                    {"element":"#tabla_pagos_prov thead th:nth-child(8)","intro":"📊 Esta columna muestra dos barras: el avance de <b>toneladas</b> (entregado/en ruta) y el avance del <b>pago</b> (% pagado).","position":"bottom"},
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

                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Registra y controla los pagos realizados a los proveedores por cada contrato.
                    Cada pago puede tener su propia moneda y tipo de cambio.
                </p>

                {{-- ===== SELECTOR DE PROVEEDOR ===== --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-box-seam"></i> Filtrar por proveedor</label>
                        <select class="form-select" id="filtro_proveedor" onchange="aplicarFiltrosPP()">
                            <option value="">— Todos los proveedores —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-calendar-event"></i> Desde</label>
                        <input type="date" class="form-control" id="filtro_desde" onchange="aplicarFiltrosPP()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1">Hasta</label>
                        {{-- min/max se sincronizan en JS para que no se pueda elegir un rango invertido --}}
                        <input type="date" class="form-control" id="filtro_hasta" onchange="aplicarFiltrosPP()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-sort-down"></i> Ordenar</label>
                        <select class="form-select" id="filtro_orden" onchange="aplicarFiltrosPP()">
                            <option value="fecha_desc">Fecha (recientes)</option>
                            <option value="fecha_asc">Fecha (antiguos)</option>
                            <option value="saldo_desc">Mayor saldo</option>
                            <option value="saldo_asc">Menor saldo</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosPP()">
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
                                <th style="white-space:nowrap; width:1%;">Contrato</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Registrado por</th>
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
                            <tr class="{{ $rowClass }}" data-proveedor-id="{{ $c->proveedor_id }}"
                                data-fecha="{{ $c->fecha_inicio?->format('Y-m-d') }}"
                                data-saldo="{{ $saldo }}">
                                <td style="white-space:nowrap;">
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
                                <td>
                                    <small>{{ $c->usuarioCreador->name ?? '—' }}</small>
                                </td>
                                <td class="text-end">{{ $mon }} {{ number_format($total, 2, ',', '.') }}</td>
                                <td class="text-end text-success">{{ $mon }} {{ number_format($pagado, 2, ',', '.') }}</td>
                                <td class="text-end {{ $saldo > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                    {{ $mon }} {{ number_format($saldo, 2, ',', '.') }}
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
                                                <span class="text-success fw-semibold">{{ number_format($tEntregadas, 2, ',', '.') }}t cliente</span>
                                                @if($tTransito > 0 || $tPendiente > 0) &nbsp;·&nbsp; @endif
                                            @endif
                                            @if($tTransito > 0)
                                                <span style="color:#0ea5e9;">{{ number_format($tTransito, 2, ',', '.') }}t en ruta</span>
                                                @if($tPendiente > 0) &nbsp;·&nbsp; @endif
                                            @endif
                                            @if($tPendiente > 0)
                                                <span class="text-muted">{{ number_format($tPendiente, 2, ',', '.') }}t pend.</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small d-block mb-2">— sin toneladas</span>
                                    @endif
                                    {{-- Barra pagos --}}
                                    <div class="small lh-1 mb-1">
                                        <span class="fw-semibold {{ $saldo <= 0 ? 'text-success' : '' }}">{{ number_format($pagado, 2, ',', '.') }} {{ $mon }}</span>
                                        <span class="text-muted">/ {{ number_format($total, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="progress mb-1" style="height:6px;" title="Pagado: {{ $pctPago }}%">
                                        <div class="progress-bar {{ $saldo <= 0 ? 'bg-success' : 'bg-primary' }}" style="width:{{ $pctPago }}%"></div>
                                    </div>
                                    <div class="small" style="font-size:.7rem;">
                                        <span class="{{ $saldo <= 0 ? 'text-success fw-semibold' : 'text-primary' }}">{{ $pctPago }}% pagado</span>
                                        @if($saldo > 0)
                                            &nbsp;·&nbsp;<span class="text-danger">{{ number_format($saldo, 2, ',', '.') }} pendiente</span>
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
                            {{-- Visible solo cuando los filtros no dejan ninguna fila --}}
                            <tr id="fila_sin_resultados" style="display:none;">
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-search" style="font-size:1.6rem;opacity:.35"></i>
                                    <div class="mt-2" id="txt_sin_resultados">No hay contratos que coincidan con el filtro.</div>
                                </td>
                            </tr>
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
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button"
                            class="btn btn-sm btn-iniciar-tour text-white rounded-circle d-flex align-items-center justify-content-center p-0"
                            style="width:28px;height:28px;background:#0a58ca"
                            title="Ayuda"
                            aria-label="Ayuda"
                            data-tour-modal="#modalPago"
                            data-steps='[
                                {"intro":"💳 Este formulario registra un <b>pago a un proveedor</b> por un contrato. Te explico cada parte. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#sec_seleccionar_contrato","intro":"📄 <b>Contrato</b>: elige a qué contrato corresponde el pago. Si abriste el modal desde el botón ➕ de una fila, ya viene seleccionado.","position":"bottom"},
                                {"element":"[name=\"tipo_pago\"]","intro":"🏷️ <b>Tipo de Pago</b>: <b>Adelanto</b> (a cuenta) o <b>Pago Final</b> (saldo del contrato).","position":"bottom"},
                                {"element":"#moneda_pago","intro":"💱 <b>Moneda</b> del pago. Si eliges una distinta de BOB, aparecerá el campo de <b>tipo de cambio</b> para convertir a bolivianos.","position":"bottom"},
                                {"element":"#inp_monto","intro":"🔢 <b>Monto</b> que se paga. Si la moneda no es BOB, abajo verás el equivalente en bolivianos calculado automáticamente.","position":"bottom"},
                                {"element":"[name=\"fecha_pago\"]","intro":"📅 <b>Fecha del pago</b>. Por defecto es hoy.","position":"top"},
                                {"element":"#metodo_pago","intro":"🏦 <b>Método de Pago</b>: transferencia bancaria o QR. Si es transferencia, se habilita un campo para el código/N° de referencia.","position":"top"},
                                {"element":"[name=\"cuenta_origen_id\"]","intro":"📤 <b>Cuenta Origen</b> (obligatoria): de qué cuenta de la empresa (tesorería) sale el dinero.","position":"top"},
                                {"element":"#sel_cuenta_destino","intro":"📥 <b>Cuenta Destino</b>: a qué cuenta del proveedor se le pagó. Se cargan según el proveedor del contrato.","position":"top"},
                                {"element":"[name=\"observaciones\"]","intro":"📝 <b>Observaciones</b> (opcional): cualquier nota sobre el pago.","position":"top"},
                                {"element":"[name=\"voucher\"]","intro":"📎 <b>Voucher / Comprobante</b> (opcional): sube una foto o PDF del comprobante del pago realizado.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formPagoProveedor" method="POST" action="{{ route('pagos.proveedores.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenPagoProveedor" value="{{ $idempotencyToken ?? '' }}">
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
                                        (Saldo: {{ $c->moneda }} {{ number_format($c->saldo_pendiente_proveedor, 2, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_pago" id="tipo_pago" required onchange="actualizarBtnPagoProveedor()">
                                <option value="">-- Seleccione --</option>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>

                        {{-- Bloque moneda / monto / tipo de cambio --}}
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-2 align-items-end">

                                    {{-- Si el contrato está en BOB el pago también: se oculta y se fija en BOB --}}
                                    <div class="col-md-3" id="sec_moneda_pago">
                                        <label class="form-label fw-semibold mb-1">Moneda <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="moneda_pago" id="moneda_pago" required onchange="toggleTipoCambio(this.value); actualizarBtnPagoProveedor()">
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
                                            <input type="text" inputmode="numeric" class="form-control"
                                                id="inp_monto_display" placeholder="0,00" autocomplete="off">
                                            <input type="hidden" name="monto" id="inp_monto" value="">
                                        </div>
                                    </div>

                                    <div class="col-md-5" id="sec_tipo_cambio" style="display:none;">
                                        <label class="form-label fw-semibold mb-1">
                                            Tipo de cambio <span class="text-danger">*</span>
                                            <small class="text-muted fw-normal">— 1 <span id="lbl_moneda_tc"></span> equivale a:</small>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" inputmode="numeric" class="form-control"
                                                id="inp_tipo_cambio_display" placeholder="0,0000" autocomplete="off" disabled>
                                            <input type="hidden" name="tipo_cambio" id="inp_tipo_cambio" value="">
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
                        <input type="hidden" id="inp_tipo_cambio_bob" value="1">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Pago <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_pago" id="fecha_pago" required value="{{ date('Y-m-d') }}" onchange="actualizarBtnPagoProveedor()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="metodo_pago" id="metodo_pago" required onchange="toggleCodigo(this.value); actualizarBtnPagoProveedor()">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="sec_codigo" style="display:none;">
                            <label class="form-label">Código de transferencia <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="codigo_seguimiento" id="codigo_seguimiento" maxlength="100"
                                placeholder="Ej: TRX-20260512-001" oninput="actualizarBtnPagoProveedor()">
                        </div>

                        {{-- Cuenta origen (tesorería empresa) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cuenta Origen (Tesorería) <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cuenta_origen_id" id="sel_cuenta_origen_pp" required onchange="actualizarBtnPagoProveedor()">
                                <option value="">-- Seleccione --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cta)
                                            <option value="{{ $cta->id }}" data-moneda="{{ $cta->moneda }}" data-saldo="{{ $cta->saldo_actual }}">
                                                {{ $cta->nombre_cuenta }}
                                                @if($cta->banco) — {{ $cta->banco->nombre }} @endif
                                                [{{ $cta->moneda }}]
                                                — Saldo: {{ number_format($cta->saldo_actual, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="alert alert-danger py-1 px-2 mt-1 small mb-0" id="aviso_saldo_insuficiente_pp" style="display:none"></div>
                        </div>

                        {{-- Cuenta destino (proveedor) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cuenta Destino (Proveedor) <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cuenta_destino_id" id="sel_cuenta_destino" required onchange="actualizarBtnPagoProveedor()">
                                <option value="">-- Seleccione --</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones <small class="text-muted">(opcional)</small></label>
                            <textarea class="form-control" name="observaciones" id="pp_observaciones" rows="2" maxlength="500"
                                placeholder="Notas del pago..."
                                oninput="document.getElementById('pp_obs_contador').textContent = this.value.length"></textarea>
                            <div class="form-text text-end">
                                <span id="pp_obs_contador">0</span>/500 caracteres
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Voucher / Comprobante <small class="text-muted">(opcional)</small></label>
                            <input type="file" class="form-control" name="voucher" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text">Imagen o PDF del comprobante de pago. Máximo 5 MB.</div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnPagoProveedor" disabled><i class="bi bi-save"></i> Registrar Pago</button>
                </div>
            </form>
            <script>
            document.getElementById('formPagoProveedor').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnPagoProveedor');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });

            // ── Cajero monto y tipo_cambio ──
            (function() {
                function _txt2num(v) {
                    return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0;
                }
                function _fmt2(n) {
                    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
                }
                function _fmt4(n) {
                    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 4, maximumFractionDigits: 4 }).format(n);
                }
                function _initCajero(displayId, hiddenId, decimals, onChangeCb) {
                    var disp   = document.getElementById(displayId);
                    var hidden = document.getElementById(hiddenId);
                    if (!disp || !hidden) return;
                    disp.addEventListener('input', function() {
                        var raw    = this.value.replace(/[^0-9,]/g, '');
                        var partes = raw.split(',');
                        if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
                        partes = raw.split(',');
                        if (partes[1] !== undefined) partes[1] = partes[1].slice(0, decimals);
                        var entF  = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        var nuevo = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
                        var diff  = nuevo.length - this.value.length;
                        var pos   = (this.selectionStart || 0) + diff;
                        this.value = nuevo;
                        try { this.setSelectionRange(pos, pos); } catch(_) {}
                        hidden.value = _txt2num(nuevo) || '';
                        if (onChangeCb) onChangeCb();
                    });
                    disp.addEventListener('blur', function() {
                        var n = _txt2num(this.value);
                        this.value   = n > 0 ? (decimals === 4 ? _fmt4(n) : _fmt2(n)) : '';
                        hidden.value = n > 0 ? n : '';
                        if (onChangeCb) onChangeCb();
                    });
                }
                // Mismo formateo de dinero para el modal de editar pago
                window._initCajero = _initCajero;
                window._fmt2       = _fmt2;

                _initCajero('inp_monto_display',      'inp_monto',         2, function() {
                    if (typeof calcEquivalente === 'function') calcEquivalente();
                    if (typeof actualizarBtnPagoProveedor === 'function') actualizarBtnPagoProveedor();
                });
                _initCajero('inp_tipo_cambio_display', 'inp_tipo_cambio',   4, function() {
                    if (typeof calcEquivalente === 'function') calcEquivalente();
                    if (typeof actualizarBtnPagoProveedor === 'function') actualizarBtnPagoProveedor();
                });
            })();
            </script>
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR PAGO ===== --}}
{{-- Se abre sobre el modal de detalle: Bootstrap no eleva el z-index del segundo modal --}}
<style>
    #modalEditarPagoProveedor { z-index: 1060; }
    .modal-backdrop.editar-pago-backdrop { z-index: 1055; }
</style>
<div class="modal fade" id="modalEditarPagoProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Pago a Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditarPagoProveedor" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- Contexto del pago (solo lectura): lo único editable es el monto --}}
                    <div class="alert alert-light border py-2 mb-3 small">
                        <div class="d-flex flex-wrap gap-3">
                            <div><span class="text-muted">Tipo:</span> <strong id="edit_pp_info_tipo">—</strong></div>
                            <div><span class="text-muted">Fecha:</span> <strong id="edit_pp_info_fecha">—</strong></div>
                            <div><span class="text-muted">Método:</span> <strong id="edit_pp_info_metodo">—</strong></div>
                            <div><span class="text-muted">Moneda:</span> <strong id="edit_pp_info_moneda">—</strong></div>
                        </div>
                    </div>
                    <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text fw-bold" id="edit_pp_monto_moneda">BOB</span>
                        <input type="text" inputmode="numeric" class="form-control"
                               id="edit_pp_monto_display" placeholder="0,00" autocomplete="off" required>
                        <input type="hidden" name="monto" id="edit_pp_monto">
                    </div>
                    <div class="form-text">
                        Al guardar se actualiza también el movimiento en tesorería y el saldo de la cuenta.
                    </div>

                    {{-- Voucher: opcional, para adjuntarlo si faltaba o reemplazar uno incorrecto --}}
                    <hr class="my-3">
                    <label class="form-label fw-semibold">
                        Voucher / comprobante <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <div id="edit_pp_voucher_actual" class="alert alert-light border py-2 small d-none">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span>
                                <i class="bi bi-paperclip me-1"></i>
                                Este pago ya tiene un comprobante adjunto.
                            </span>
                            <a href="#" id="edit_pp_voucher_link" target="_blank"
                               class="btn btn-sm btn-primary flex-shrink-0">
                                <i class="bi bi-eye me-1"></i>Ver actual
                            </a>
                        </div>
                        <div class="text-muted mt-1">Si subes uno nuevo, reemplazará al anterior.</div>
                    </div>
                    <input type="file" class="form-control" name="voucher" id="edit_pp_voucher"
                           accept=".jpg,.jpeg,.png,.pdf">
                    <div class="form-text">JPG, PNG o PDF. Máximo 5 MB.</div>
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

// Se mantiene por compatibilidad: otras vistas entran aquí con un proveedor ya elegido
function filtrarPorProveedor(proveedorId) {
    const sel = document.getElementById('filtro_proveedor');
    if (sel) sel.value = proveedorId;
    $('#filtro_proveedor').trigger('change.select2');
    aplicarFiltrosPP();
}

// ── Buscador en el select de Filtrar por proveedor ──
$('#filtro_proveedor').select2({
    placeholder: '— Todos los proveedores —',
    allowClear: true,
    width: '100%',
    language: {
        noResults: () => 'No se encontró ningún proveedor.',
        searching: () => 'Buscando...'
    }
});

function limpiarFiltrosPP() {
    document.getElementById('filtro_proveedor').value = '';
    $('#filtro_proveedor').trigger('change.select2');
    document.getElementById('filtro_desde').value     = '';
    document.getElementById('filtro_hasta').value     = '';
    document.getElementById('filtro_orden').value     = 'fecha_desc';
    // Soltar los límites, si no quedan pegados del rango anterior
    document.getElementById('filtro_desde').max = '';
    document.getElementById('filtro_hasta').min = '';
    aplicarFiltrosPP();
}

function aplicarFiltrosPP() {
    const tbody = document.querySelector('#tabla_pagos_prov tbody');
    if (!tbody) return;

    const provId = document.getElementById('filtro_proveedor').value;
    const inpDesde = document.getElementById('filtro_desde');
    const inpHasta = document.getElementById('filtro_hasta');
    const orden  = document.getElementById('filtro_orden').value;

    // El rango no puede quedar invertido: el calendario bloquea las fechas imposibles
    inpHasta.min = inpDesde.value || '';
    inpDesde.max = inpHasta.value || '';

    // Red de seguridad si el valor se escribió a mano en vez de elegirlo
    if (inpDesde.value && inpHasta.value && inpHasta.value < inpDesde.value) {
        inpHasta.value = inpDesde.value;
    }

    const desde = inpDesde.value;
    const hasta = inpHasta.value;

    const filaVacia = document.getElementById('fila_sin_resultados');
    const filas = Array.from(tbody.querySelectorAll('tr')).filter(f => f !== filaVacia);

    let visibles = 0;
    filas.forEach(fila => {
        const fecha = fila.dataset.fecha || '';
        // Las fechas vienen como YYYY-MM-DD: se comparan como texto sin parsear
        const okProv  = !provId || fila.dataset.proveedorId == provId;
        const okDesde = !desde  || (fecha && fecha >= desde);
        const okHasta = !hasta  || (fecha && fecha <= hasta);

        const mostrar = okProv && okDesde && okHasta;
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    // Ordenar solo las visibles, reinsertándolas en el tbody
    const visiblesArr = filas.filter(f => f.style.display !== 'none');
    visiblesArr.sort((a, b) => {
        switch (orden) {
            case 'fecha_asc':  return (a.dataset.fecha || '').localeCompare(b.dataset.fecha || '');
            case 'saldo_desc': return parseFloat(b.dataset.saldo || 0) - parseFloat(a.dataset.saldo || 0);
            case 'saldo_asc':  return parseFloat(a.dataset.saldo || 0) - parseFloat(b.dataset.saldo || 0);
            default:           return (b.dataset.fecha || '').localeCompare(a.dataset.fecha || '');
        }
    });
    visiblesArr.forEach(f => tbody.appendChild(f));
    if (filaVacia) tbody.appendChild(filaVacia);

    document.getElementById('lbl_count_prov').textContent = visibles;

    // Mensaje explícito en vez de una tabla vacía
    if (filaVacia) {
        filaVacia.style.display = visibles === 0 ? '' : 'none';
        if (visibles === 0) {
            const nombreProv = provId
                ? document.querySelector(`#filtro_proveedor option[value="${provId}"]`)?.textContent?.trim()
                : null;
            const hayFechas = desde || hasta;
            let msg;
            if (nombreProv && hayFechas) {
                msg = `${nombreProv} no tiene contratos con pagos pendientes en el rango de fechas seleccionado.`;
            } else if (nombreProv) {
                msg = `${nombreProv} no tiene contratos con pagos por realizar.`;
            } else {
                msg = 'No hay contratos en el rango de fechas seleccionado.';
            }
            document.getElementById('txt_sin_resultados').textContent = msg;
        }
    }
}

let _proveedorActual = null;
let _monedaPendiente = null;

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('filtro_orden')) aplicarFiltrosPP();

    const modalPago = document.getElementById('modalPago');
    modalPago.addEventListener('shown.bs.modal', function () {
        if (_monedaPendiente) {
            toggleTipoCambio(_monedaPendiente);
            _monedaPendiente = null;
        }
        actualizarBtnPagoProveedor();
    });
    modalPago.addEventListener('hidden.bs.modal', function () {
        document.getElementById('sec_seleccionar_contrato').style.display = 'block';
        document.getElementById('pago_info_contrato').style.display = 'none';
        document.getElementById('sec_moneda_pago').style.display = '';
    });
});

function toggleTipoCambio(moneda) {
    const secTC    = document.getElementById('sec_tipo_cambio');
    const secEquiv = document.getElementById('sec_equivalente');
    const inpTCDisp = document.getElementById('inp_tipo_cambio_display');
    const inpTC    = document.getElementById('inp_tipo_cambio');
    const inpBob   = document.getElementById('inp_tipo_cambio_bob');
    const lblMonto = document.getElementById('lbl_moneda_monto');
    const lblTC    = document.getElementById('lbl_moneda_tc');

    document.getElementById('moneda_pago').value = moneda;
    lblMonto.textContent = moneda;

    if (moneda === 'BOB') {
        secTC.style.display    = 'none';
        secEquiv.style.display = 'none';
        inpTCDisp.disabled = true;
        inpTCDisp.value    = '';
        inpTC.value        = '1';
        inpBob.value       = '1';
    } else {
        secTC.style.display    = 'block';
        inpTCDisp.disabled = false;
        lblTC.textContent  = moneda;
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
        lblEquiv.textContent = 'Bs ' + _fmtP(monto * tc);
        secEquiv.style.display = 'block';
    } else {
        secEquiv.style.display = 'none';
    }
}

function actualizarBtnPagoProveedor() {
    const btn   = document.getElementById('btnPagoProveedor');
    const aviso = document.getElementById('aviso_saldo_insuficiente_pp');

    // 1. Campos obligatorios del formulario
    const contratoId  = document.getElementById('pago_contrato_id').value;
    const tipoPago    = document.getElementById('tipo_pago').value;
    const monto       = parseFloat(document.getElementById('inp_monto').value) || 0;
    const monedaPago  = document.getElementById('moneda_pago').value;
    const fechaPago   = document.getElementById('fecha_pago').value;
    const metodoPago  = document.getElementById('metodo_pago').value;
    const tc          = parseFloat(document.getElementById('inp_tipo_cambio').value) || 0;

    const codigoVisible = document.getElementById('sec_codigo').style.display !== 'none';
    const codigo         = document.getElementById('codigo_seguimiento').value.trim();
    const codigoOk       = !codigoVisible || codigo !== '';

    const tipoCambioOk = monedaPago === 'BOB' || tc > 0;

    const cuentaOrigenId  = document.getElementById('sel_cuenta_origen_pp').value;
    const cuentaDestinoId = document.getElementById('sel_cuenta_destino').value;

    const camposOk = !!contratoId && !!tipoPago && monto > 0 && !!monedaPago
        && tipoCambioOk && !!fechaPago && !!metodoPago && codigoOk
        && !!cuentaOrigenId && !!cuentaDestinoId;

    // 2. Saldo disponible en la cuenta origen (si se seleccionó una)
    const sel = document.getElementById('sel_cuenta_origen_pp');
    const opt = sel.options[sel.selectedIndex];
    let insuficiente = false;

    if (opt && opt.value) {
        const saldo     = parseFloat(opt.dataset.saldo) || 0;
        const monedaCta = opt.dataset.moneda || '';

        // Convertimos el monto a pagar a la moneda de la cuenta origen para poder compararlo con el saldo
        let montoEnMonedaCuenta = null;
        if (monedaPago === monedaCta) {
            montoEnMonedaCuenta = monto;
        } else if (monedaCta === 'BOB') {
            montoEnMonedaCuenta = monto * (tc || 1);
        }

        if (montoEnMonedaCuenta !== null) {
            insuficiente = montoEnMonedaCuenta > saldo;
            if (insuficiente) {
                aviso.textContent = `Saldo insuficiente en la cuenta seleccionada. Disponible: ${monedaCta} ${_fmtP(saldo)}, requerido: ${monedaCta} ${_fmtP(montoEnMonedaCuenta)}.`;
            }
        }
    }
    aviso.style.display = insuficiente ? '' : 'none';

    btn.disabled = !camposOk || insuficiente;
}

function abrirModalPago(contratoId, label, saldo, moneda, proveedorId) {
    document.getElementById('pago_contrato_id').value        = contratoId;
    document.getElementById('sel_contrato').value            = contratoId;
    document.getElementById('pago_contrato_label').textContent = label;
    document.getElementById('pago_saldo_label').textContent  = _fmtP(saldo) + ' ' + (moneda || 'BOB');
    document.getElementById('pago_info_contrato').style.display = 'block';
    document.getElementById('sec_seleccionar_contrato').style.display = 'none';

    const mon = moneda || 'BOB';
    document.getElementById('moneda_pago').value = mon;
    // Contrato en BOB: el pago va en BOB, no hay moneda que elegir
    document.getElementById('sec_moneda_pago').style.display = mon === 'BOB' ? 'none' : '';

    _monedaPendiente  = mon;
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
    document.getElementById('pago_saldo_label').textContent    = _fmtP(opt.dataset.saldo) + ' ' + mon;
    document.getElementById('pago_info_contrato').style.display = 'block';

    document.getElementById('moneda_pago').value = mon;
    // Contrato en BOB: el pago va en BOB, no hay nada que elegir.
    // Se oculta el contenedor (no se deshabilita: el select debe seguir enviándose).
    document.getElementById('sec_moneda_pago').style.display = mon === 'BOB' ? 'none' : '';
    toggleTipoCambio(mon);

    _proveedorActual = opt.dataset.proveedorId || null;
    cargarCuentasProveedor(_proveedorActual);
    actualizarBtnPagoProveedor();
}

function cargarCuentasProveedor(proveedorId) {
    const sel = document.getElementById('sel_cuenta_destino');
    sel.innerHTML = '<option value="">-- Cargando... --</option>';
    if (!proveedorId) {
        sel.innerHTML = '<option value="">-- Seleccione --</option>';
        actualizarBtnPagoProveedor();
        return;
    }
    fetch(`${url_global}/api/pagos/cuentas-proveedor?proveedor_id=${proveedorId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- Seleccione --</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.label;
                sel.appendChild(opt);
            });
            actualizarBtnPagoProveedor();
        });
}

function toggleCodigo(metodo) {
    document.getElementById('sec_codigo').style.display =
        metodo === 'transferencia' ? 'block' : 'none';
}

function abrirEditarPagoProveedor(uuid, tipoLabel, monto, moneda, fecha, metodoLabel, tieneVoucher) {
    document.getElementById('formEditarPagoProveedor').action = url_global + '/pagos/proveedores/' + uuid;

    // Voucher: limpiar la selección previa y mostrar el actual si lo hay
    document.getElementById('edit_pp_voucher').value = '';
    const avisoVoucher = document.getElementById('edit_pp_voucher_actual');
    avisoVoucher.classList.toggle('d-none', !tieneVoucher);
    if (tieneVoucher) {
        document.getElementById('edit_pp_voucher_link').href =
            url_global + '/pagos/proveedores/' + uuid + '/voucher';
    }

    // Mismo formato de dinero del sistema: display con miles/decimales, hidden con el valor crudo
    document.getElementById('edit_pp_monto').value         = monto;
    document.getElementById('edit_pp_monto_display').value = _fmt2(parseFloat(monto) || 0);
    document.getElementById('edit_pp_monto_moneda').textContent = moneda;

    // Contexto solo lectura: no se edita, el backend conserva estos valores
    document.getElementById('edit_pp_info_tipo').textContent   = tipoLabel;
    document.getElementById('edit_pp_info_fecha').textContent  = fecha;
    document.getElementById('edit_pp_info_metodo').textContent = metodoLabel;
    document.getElementById('edit_pp_info_moneda').textContent = moneda;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarPagoProveedor')).show();
}

// Modal anidado sobre el de detalle: marcar su backdrop para elevarlo y
// devolver el scroll al modal de detalle cuando este se cierra encima.
document.addEventListener('DOMContentLoaded', function () {
    const modalEditar = document.getElementById('modalEditarPagoProveedor');
    if (!modalEditar) return;

    // Formateo de dinero del sistema (miles con ".", 2 decimales con ",")
    _initCajero('edit_pp_monto_display', 'edit_pp_monto', 2);

    modalEditar.addEventListener('shown.bs.modal', function () {
        const backdrops = document.querySelectorAll('.modal-backdrop:not(.editar-pago-backdrop)');
        if (backdrops.length > 1) {
            backdrops[backdrops.length - 1].classList.add('editar-pago-backdrop');
        }
    });

    modalEditar.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });

    // Guardar por AJAX: cierra solo el modal de edición y refresca el detalle
    // en su sitio, sin recargar ni perder el contrato que se estaba mirando.
    document.getElementById('formEditarPagoProveedor').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;

        // Lo que se envía es el hidden: validar sobre él, no sobre el display formateado
        if (!(parseFloat(document.getElementById('edit_pp_monto').value) > 0)) {
            alert('El monto debe ser mayor a cero.');
            document.getElementById('edit_pp_monto_display').focus();
            return;
        }

        const btn  = form.querySelector('button[type="submit"]');
        const btnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                // 422: Laravel manda los mensajes por campo en data.errors
                const detalle = data.errors ? Object.values(data.errors).flat().join('\n') : null;
                throw new Error(detalle || data.message || 'No se pudo actualizar el pago.');
            }
            return data;
        })
        .then(() => {
            bootstrap.Modal.getInstance(modalEditar).hide();
            if (contratoDetalleActual) verDetalle(contratoDetalleActual);
            // La fila de la tabla (pagado, saldo, progreso) se rearma en Blade:
            // se recarga al cerrar el detalle en vez de duplicar ese cálculo en JS.
            hayPagoEditado = true;
        })
        .catch(err => {
            alert(err.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = btnHtml;
        });
    });
});

function _fmtP(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}
function _fmtPtc(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 4, maximumFractionDigits: 4 }).format(parseFloat(n) || 0);
}
// Escapa texto libre del usuario antes de insertarlo como HTML
function _escP(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

// Contrato que se está viendo, para poder refrescar el detalle tras editar un pago
let contratoDetalleActual = null;
let hayPagoEditado = false;

// Al cerrar el detalle, refrescar la tabla si algún pago cambió
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('modalDetalle')?.addEventListener('hidden.bs.modal', function () {
        if (hayPagoEditado) location.reload();
    });
});

function verDetalle(contratoId) {
    contratoDetalleActual = contratoId;
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalle'));
    document.getElementById('det_body').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch(`${url_global}/api/pagos/proveedores/${contratoId}/detalle`)
        .then(r => r.json())
        .then(d => {
            const mon = d.moneda || 'BOB';
            document.getElementById('det_titulo').textContent = d.numero + ' — ' + d.proveedor;

            let html = `
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Total acordado</div>
                        <strong>${mon} ${_fmtP(d.monto_total||0)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Pagado</div>
                        <strong class="text-success">${mon} ${_fmtP(d.total_pagado||0)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-2 text-center bg-${parseFloat(d.saldo_pendiente)<=0?'success':'warning'} bg-opacity-10">
                        <div class="text-muted small">Saldo</div>
                        <strong class="${parseFloat(d.saldo_pendiente)<=0?'text-success':'text-danger'}">
                            ${mon} ${_fmtP(d.saldo_pendiente||0)}
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
                    const tcLine  = esBob ? '' : `<span class="text-muted ms-1" style="font-size:.7rem">TC: 1 ${p.moneda_pago} = ${_fmtPtc(p.tipo_cambio)} Bs</span>`;

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

                    // Observaciones del pago (texto del usuario: se escapa antes de insertarlo)
                    const obsLine = p.observaciones
                        ? `<div class="mt-2 pt-2 border-top small">
                               <i class="bi bi-sticky text-muted me-1"></i>${_escP(p.observaciones)}
                           </div>`
                        : '';

                    html += `
                    <div class="rounded-2 border px-3 py-2 bg-white">
                        <div class="d-flex align-items-start gap-3">
                            <div class="pt-1"><span class="badge ${badge}">${p.tipo}</span></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${flag} ${p.moneda_pago} ${_fmtP(p.monto)}${tcLine}</div>
                                <div class="text-muted small">${p.fecha} &nbsp;·&nbsp; ${p.metodo}${p.codigo ? ' &nbsp;·&nbsp; ' + p.codigo : ''}${origLine}</div>
                                ${destLine}
                            </div>
                            <div class="d-flex flex-column gap-1">
                                ${p.tiene_voucher ? `<a href="/pagos/proveedores/${p.uuid}/voucher" target="_blank"
                                    class="btn btn-sm btn-outline-secondary border-0" title="Ver voucher">
                                    <i class="bi bi-paperclip"></i>
                                </a>` : ''}
                                ${canEditPago ? `<button class="btn btn-sm btn-outline-primary border-0"
                                    onclick="abrirEditarPagoProveedor('${p.uuid}','${p.tipo}',${p.monto},'${p.moneda_pago}','${p.fecha}','${p.metodo}',${p.tiene_voucher})"
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
                        ${obsLine}
                    </div>`;
                });
                html += '</div>';
            }
            document.getElementById('det_body').innerHTML = html;
        });
}
</script>
@endsection
