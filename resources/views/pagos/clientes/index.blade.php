@extends('layouts.app')
@section('titulo', 'Cobros a Clientes')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>COBROS A CLIENTES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Cobros Clientes</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<section class="section">

{{-- ===== RESUMEN COBRO MASIVO (flash) ===== --}}
@if(session('cobro_masivo_resumen'))
@php $r = session('cobro_masivo_resumen'); @endphp
<div class="alert alert-success border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <strong>Cobro masivo registrado — {{ $r['cliente'] }}</strong>
        @if($r['codigo'])
            <span class="badge bg-light text-dark border ms-auto">
                <i class="bi bi-upc-scan me-1"></i>{{ strtoupper($r['metodo']) }}: {{ $r['codigo'] }}
            </span>
        @endif
    </div>
    <table class="table table-sm table-borderless mb-0 small">
        <thead class="table-success">
            <tr>
                <th>Tipo</th>
                <th>Contrato</th>
                <th>Camión</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($r['lineas'] as $l)
            <tr class="{{ str_contains($l['tipo'], 'Anticipo') ? 'fw-semibold text-warning' : '' }}">
                <td>
                    @if(str_contains($l['tipo'], 'Anticipo'))
                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>{{ $l['tipo'] }}</span>
                    @else
                        <span class="badge bg-success">{{ $l['tipo'] }}</span>
                    @endif
                </td>
                <td>{{ $l['contrato'] }}</td>
                <td>{{ $l['camion'] }}</td>
                <td class="text-end fw-semibold">{{ $l['monto'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Cobros por Entrega</h5>
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Lista de todos los camiones entregados a clientes. Si un camión no tiene precio/t registrado,
                    usa <strong>Opciones → Registrar precio/t</strong> para ingresarlo y habilitar el cobro.
                </p>

                {{-- ===== SELECTOR DE CLIENTE ===== --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-people"></i> Filtrar por cliente</label>
                        <select class="form-select" id="filtro_cliente" onchange="filtrarPorCliente(this.value)">
                            <option value="">— Todos los clientes —</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="filtrarPorCliente('')">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                    @can('pagos_clientes.create')
                    <div class="col-auto">
                        <button class="btn btn-success btn-sm" onclick="abrirCobroMasivo()">
                            <i class="bi bi-cash-stack"></i> Cobro Masivo
                        </button>
                    </div>
                    @endcan
                    <div class="col-auto ms-auto">
                        <small class="text-muted">Mostrando <span id="lbl_count_visible">{{ $tramos->count() }}</span> entrega(s)</small>
                    </div>
                </div>

                @if($tramos->isEmpty())
                    <div class="alert alert-info py-2">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            No hay entregas con precio registrado aún. Para registrar cobros, primero registre
                            la llegada de un tramo indicando el precio por tonelada al cliente.
                        </small>
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm align-middle" id="tabla_cobros">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Contrato</th>
                                <th>Camión</th>
                                <th>Fecha entrega</th>
                                <th class="text-end">Peso (t)</th>
                                <th class="text-end">Precio / t</th>
                                <th class="text-end">Total deuda</th>
                                <th class="text-end">Cobrado</th>
                                <th class="text-end">Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tramos as $t)
                            @php
                                $tienePrecio = !is_null($t->precio_por_tonelada);
                                $mon         = $t->moneda_venta ?? 'BOB';
                                $deuda       = $tienePrecio ? $t->monto_deuda_cliente : 0;
                                $cobrado     = $tienePrecio ? $t->total_cobrado_cliente : 0;
                                $saldo       = $tienePrecio ? $t->saldo_cliente : null;
                                $pct         = $deuda > 0 ? min(100, round($cobrado / $deuda * 100)) : 0;
                                $rowClass    = $tienePrecio && $saldo <= 0 ? 'table-success' : ($tienePrecio ? '' : 'table-warning');
                            @endphp
                            <tr class="{{ $rowClass }}" data-cliente-id="{{ $t->cliente_id }}">
                                <td><strong>{{ $t->cliente->nombre ?? '—' }}</strong></td>
                                <td>
                                    <a href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid ?? '') }}"
                                        class="text-decoration-none fw-semibold">
                                        {{ $t->contratoCamion->contrato->numero_contrato ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $t->contratoCamion->camion->placa ?? '—' }}</strong>
                                    <small class="text-muted d-block">{{ $t->contratoCamion->camion->marca->valor ?? '' }}</small>
                                </td>
                                <td>
                                    <small>{{ $t->fecha_llegada?->format('d/m/Y') ?? '—' }}</small>
                                    <small class="text-muted d-block">{{ $t->destino }}</small>
                                </td>
                                <td class="text-end">{{ number_format($t->peso_llegada, 3) }}</td>
                                <td class="text-end">
                                    @if($tienePrecio)
                                        <small class="text-muted">{{ $mon }}</small>
                                        <strong>{{ number_format($t->precio_por_tonelada, 4) }}</strong>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Sin precio</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $tienePrecio ? $mon.' '.number_format($deuda,2) : '—' }}</td>
                                <td class="text-end text-success fw-semibold">{{ $tienePrecio ? $mon.' '.number_format($cobrado,2) : '—' }}</td>
                                <td class="text-end {{ $tienePrecio && $saldo > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                    {{ $tienePrecio ? $mon.' '.number_format($saldo,2) : '—' }}
                                </td>
                                <td>
                                    @if(!$tienePrecio)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-tag"></i> Sin precio</span>
                                    @elseif($saldo <= 0)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Cobrado</span>
                                    @elseif($cobrado > 0)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> {{ $pct }}% cobrado</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-clock"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-list-ul"></i> Opciones
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('pagos_clientes.create')
                                            @if(!$tienePrecio)
                                            <li>
                                                <button class="dropdown-item" onclick="abrirModalPrecio({{ $t->id }}, '{{ addslashes($t->cliente->nombre ?? '') }} — {{ addslashes($t->contratoCamion->camion->placa ?? '') }}', '{{ addslashes($t->contratoCamion->contrato->numero_contrato ?? '') }}')">
                                                    <i class="bi bi-tag text-warning me-2"></i> Registrar precio/t
                                                </button>
                                            </li>
                                            @else
                                            @if($saldo > 0)
                                            <li>
                                                <button class="dropdown-item" onclick="abrirModalCobro({{ $t->id }}, '{{ addslashes($t->cliente->nombre ?? '') }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato ?? '') }}', {{ $saldo }}, '{{ $t->moneda_venta ?? 'BOB' }}', {{ $t->cliente_id ?? 'null' }})">
                                                    <i class="bi bi-plus-circle text-success me-2"></i> Registrar cobro
                                                </button>
                                            </li>
                                            @endif
                                            @if($cobrado == 0)
                                            <li>
                                                <button class="dropdown-item" onclick="abrirModalPrecio({{ $t->id }}, '{{ addslashes($t->cliente->nombre ?? '') }} — {{ addslashes($t->contratoCamion->camion->placa ?? '') }}', '{{ addslashes($t->contratoCamion->contrato->numero_contrato ?? '') }}')">
                                                    <i class="bi bi-pencil text-secondary me-2"></i> Editar precio/t
                                                </button>
                                            </li>
                                            @endif
                                            @endif
                                            @endcan
                                            @can('pagos_clientes.index')
                                            <li>
                                                <button class="dropdown-item" onclick="verDetalle({{ $t->id }})">
                                                    <i class="bi bi-eye text-primary me-2"></i> Ver cobros
                                                </button>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
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

{{-- ===== MODAL REGISTRAR PRECIO/TONELADA ===== --}}
<div class="modal fade" id="modalPrecio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-tag"></i> Registrar Precio por Tonelada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formPrecio" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-light border py-2 mb-3">
                        <strong id="precio_label"></strong>
                        <div class="text-muted small" id="precio_contrato"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Moneda <span class="text-danger">*</span></label>
                            <select class="form-select" name="moneda_venta" required>
                                @php
                                    $flags = [
                                        'BOB' => '🇧🇴', 'USD' => '🇺🇸', 'BRL' => '🇧🇷', 'ARS' => '🇦🇷',
                                        'EUR' => '🇪🇺', 'PEN' => '🇵🇪', 'CLP' => '🇨🇱', 'PYG' => '🇵🇾',
                                        'COP' => '🇨🇴', 'UYU' => '🇺🇾'
                                    ];
                                @endphp
                                @foreach($monedas as $moneda)
                                    <option value="{{ $moneda->valor }}">{{ $flags[$moneda->valor] ?? '' }} {{ $moneda->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Precio por tonelada <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" class="form-control"
                                    name="precio_por_tonelada" placeholder="0.0000" required>
                                <span class="input-group-text">/ t</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL REGISTRAR COBRO ===== --}}
<div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Registrar Cobro al Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pagos.clientes.store') }}">
                @csrf
                <input type="hidden" name="tramo_id" id="cobro_tramo_id">
                <input type="hidden" name="moneda_pago" id="cobro_moneda_pago">
                <input type="hidden" name="tipo_cambio" id="cobro_inp_tipo_cambio_bob" value="1">
                <div class="modal-body">

                    {{-- Info de la entrega seleccionada --}}
                    <div id="cobro_info_tramo" class="alert alert-light border mb-3 py-2" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong id="cobro_tramo_label"></strong>
                            <div class="text-end">
                                <small class="text-muted">Saldo pendiente:</small>
                                <span class="badge bg-danger ms-1" id="cobro_saldo_label"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Aviso sin cuenta --}}
                    <div id="cobro_aviso_sin_cuenta" class="alert alert-warning py-2 small mb-3" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Este cliente no tiene cuentas bancarias registradas. Regístrala en el módulo de clientes para continuar.
                    </div>

                    <div class="row g-3">

                        {{-- Fila 1: Cuenta Origen + Tipo de Cobro --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cuenta Origen (Cliente) <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cuenta_origen_id" id="sel_cuenta_origen" required
                                    onchange="validarBobroCobro()">
                                <option value="">-- Seleccione un cliente primero --</option>
                            </select>
                            <div class="form-text text-muted" id="cobro_hint_cuenta_origen">Se carga al abrir el formulario.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Cobro <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_pago" id="cobro_tipo_pago" required disabled
                                    onchange="validarBobroCobro()">
                                <option value="">-- Seleccione --</option>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>

                        {{-- Fila 2: Cuenta Destino + Método --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cuenta Destino (Empresa) <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cuenta_destino_id" id="cobro_cuenta_destino" required disabled
                                    onchange="validarBobroCobro()">
                                <option value="">-- Seleccione cuenta --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cta)
                                            <option value="{{ $cta->id }}">
                                                {{ $cta->nombre_cuenta }}
                                                @if($cta->banco) — {{ $cta->banco }} @endif
                                                [{{ $cta->moneda }}]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="metodo_pago" id="cobro_metodo_pago" required disabled
                                    onchange="toggleCodigo(this.value); validarBobroCobro();">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>

                        {{-- Código solo para transferencia --}}
                        <div class="col-md-6" id="cobro_sec_codigo" style="display:none;">
                            <label class="form-label fw-semibold">Código de Transferencia <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="codigo_seguimiento" id="cobro_codigo"
                                   maxlength="100" placeholder="Ej: TRX-20260512-001"
                                   oninput="validarBobroCobro()">
                        </div>

                        {{-- Fila 3: Monto + Fecha --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">(*)</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold" id="cobro_lbl_moneda_monto">BOB</span>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    name="monto" id="cobro_inp_monto" required disabled placeholder="0.00"
                                    oninput="calcEquivalente(); validarBobroCobro();">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Cobro <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_pago" id="cobro_fecha" required disabled
                                   value="{{ date('Y-m-d') }}" onchange="validarBobroCobro()">
                        </div>

                        {{-- Tipo de cambio (solo si moneda != BOB) --}}
                        <div class="col-md-6" id="cobro_sec_tipo_cambio" style="display:none;">
                            <label class="form-label fw-semibold mb-1">
                                Tipo de cambio <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">— 1 <span id="cobro_lbl_moneda_tc"></span> = BOB</small>
                            </label>
                            <input type="number" step="0.0001" min="0.0001" class="form-control"
                                name="tipo_cambio" id="cobro_inp_tipo_cambio"
                                placeholder="0.0000" oninput="calcEquivalente(); validarBobroCobro();" disabled>
                        </div>

                        <div id="cobro_sec_equivalente" style="display:none;" class="col-12">
                            <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2"
                                style="background:#e8f4fd;border:1px solid #b8d9f5;">
                                <i class="bi bi-arrow-left-right text-primary"></i>
                                <span class="text-muted small">Equivalente en bolivianos:</span>
                                <strong class="text-primary fs-6" id="cobro_lbl_equivalente">—</strong>
                                <span class="text-muted small">BOB</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" id="cobro_obs" rows="2"
                                maxlength="500" placeholder="Notas del cobro..." disabled></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="cobro_btn_guardar" class="btn btn-success" disabled>
                        <i class="bi bi-save"></i> Registrar Cobro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL DETALLE DE COBROS ===== --}}
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-list-ul"></i> Detalle de Cobros — <span id="det_titulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="det_body">
                <div class="text-center py-4"><div class="spinner-border text-success"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- ===== MODAL COBRO MASIVO ===== --}}
<div class="modal fade" id="modalCobroMasivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Cobro Masivo por Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pagos.clientes.cobro_masivo') }}">
                @csrf
                <div class="modal-body">

                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Selecciona el cliente y el monto total recibido. Luego marca las entregas que cubre ese pago.
                        El sistema distribuirá el monto entre las cargas seleccionadas según el saldo pendiente de cada una.
                    </p>

                    <div class="row g-3 mb-3">

                        {{-- Fila 1: Cliente + Cuenta Origen --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">(*)</span></label>
                            <select name="cliente_id" id="cm_cliente" class="form-select" required onchange="cargarTramasMasivo()">
                                <option value="">-- Seleccione cliente --</option>
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}">{{ $cli->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cuenta Origen (Cliente) <span class="text-danger">(*)</span></label>
                            <select name="cuenta_origen_id" id="cm_cuenta_origen" class="form-select" disabled required onchange="actualizarResumenMasivo()">
                                <option value="">-- Seleccione un cliente primero --</option>
                            </select>
                            <div class="form-text text-muted" id="cm_hint_cuenta_origen">Se carga al seleccionar el cliente.</div>
                        </div>

                        {{-- Fila 2: Cuenta Destino + Método + Fecha --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cuenta Destino (Empresa) <span class="text-danger">(*)</span></label>
                            <select name="cuenta_destino_id" id="cm_cuenta_destino" class="form-select" disabled required onchange="actualizarMonedaPorCuenta(); actualizarResumenMasivo();">
                                <option value="" data-moneda="">-- Seleccione cuenta --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cta)
                                            <option value="{{ $cta->id }}" data-moneda="{{ $cta->moneda }}">
                                                {{ $cta->nombre_cuenta }}@if($cta->banco) — {{ $cta->banco }}@endif [{{ $cta->moneda }}]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">(*)</span></label>
                            <select name="metodo_pago" id="cm_metodo" class="form-select" required disabled onchange="toggleCodigoMasivo(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha_pago" id="cm_fecha" class="form-control" value="{{ date('Y-m-d') }}" required disabled>
                        </div>

                        {{-- Fila 3: Monto + Tipo Cambio (si aplica) + Código --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto Total Recibido <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto_total" id="cm_monto"
                                   class="form-control" required placeholder="0.00" disabled oninput="actualizarResumenMasivo()">
                        </div>
                        <div class="col-md-3" id="cm_sec_tc" style="display:none;">
                            <label class="form-label fw-semibold">Tipo de Cambio <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="tipo_cambio" id="cm_tc"
                                   class="form-control" placeholder="0.0000">
                        </div>
                        <div class="col-md-3" id="cm_sec_codigo" style="display:none;">
                            <label class="form-label fw-semibold">Código de Referencia</label>
                            <input type="text" name="codigo_seguimiento" id="cm_codigo" class="form-control"
                                   maxlength="100" placeholder="Ej: TRX-20260519-001">
                            <div class="form-text text-muted" id="cm_hint_codigo"></div>
                        </div>

                        {{-- Observaciones --}}
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="cm_obs" class="form-control" rows="2" maxlength="500" placeholder="Notas del cobro..." disabled></textarea>
                        </div>

                    </div>

                    {{-- Tabla de entregas pendientes --}}
                    <div id="cm_contenedor_tramos" style="display:none;">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Entregas pendientes del cliente</span>
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodosMasivo(true)">
                                    <i class="bi bi-check-all"></i> Seleccionar todas
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodosMasivo(false)">
                                    <i class="bi bi-x"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="cm_tabla_tramos">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:40px">
                                            <input type="checkbox" id="cm_check_all" onchange="seleccionarTodosMasivo(this.checked)">
                                        </th>
                                        <th>Contrato</th>
                                        <th>Camión</th>
                                        <th>Fecha entrega</th>
                                        <th class="text-end">Deuda total</th>
                                        <th class="text-end">Ya cobrado</th>
                                        <th class="text-end">Saldo pendiente</th>
                                    </tr>
                                </thead>
                                <tbody id="cm_tbody"></tbody>
                            </table>
                        </div>
                        {{-- Resumen --}}
                        <div class="alert alert-secondary py-2 mt-2 small" id="cm_resumen" style="display:none;">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <span>Entregas seleccionadas: <strong id="cm_res_cant">0</strong></span>
                                <span>Saldo total seleccionado: <strong id="cm_res_saldo">0.00</strong></span>
                                <span>Monto ingresado: <strong id="cm_res_monto">0.00</strong></span>
                                <span id="cm_res_estado"></span>
                            </div>
                        </div>
                    </div>

                    <div id="cm_sin_cliente" class="text-center text-muted py-3" style="display:none;">
                        <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                        Este cliente no tiene entregas pendientes de cobro.
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="cm_btn_guardar" class="btn btn-success" disabled>
                        <i class="bi bi-save me-1"></i>Registrar Cobro Masivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR COBRO ===== --}}
<div class="modal fade" id="modalEditarCobro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="ec_tipo">
                            <option value="adelanto">Adelanto</option>
                            <option value="pago_final">Pago Final</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="ec_monto">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="ec_fecha">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Método <span class="text-danger">*</span></label>
                        <select class="form-select" id="ec_metodo">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="qr">QR</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Código de referencia</label>
                        <input type="text" class="form-control" id="ec_codigo" maxlength="100">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" id="ec_obs" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-warning small py-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Si el monto cambia, se registrará un movimiento de ajuste en tesorería para mantener la trazabilidad.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm" onclick="guardarEditarCobro()">
                    <i class="bi bi-save me-1"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
// Permisos del usuario
const canEditCobro = {{ auth()->user()->can('pagos_clientes.edit') ? 'true' : 'false' }};
const canDeleteCobro = {{ auth()->user()->can('pagos_clientes.destroy') ? 'true' : 'false' }};

let _clienteActual = null;

// ===== Filtro por cliente en la tabla =====
function filtrarPorCliente(clienteId) {
    document.getElementById('filtro_cliente').value = clienteId;
    const filas = document.querySelectorAll('#tabla_cobros tbody tr');
    let count = 0;
    filas.forEach(function (fila) {
        const visible = !clienteId || fila.dataset.clienteId === clienteId;
        fila.style.display = visible ? '' : 'none';
        if (visible) count++;
    });
    document.getElementById('lbl_count_visible').textContent = count;
}

// ===== Modal precio/tonelada =====
function abrirModalPrecio(tramoId, label, contrato) {
    document.getElementById('precio_label').textContent    = label;
    document.getElementById('precio_contrato').textContent = 'Contrato: ' + contrato;
    document.getElementById('formPrecio').action = '/pagos/clientes/' + tramoId + '/precio';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPrecio')).show();
}

// ===== Modal cobro =====
function _setCamposCobro(habilitado) {
    ['cobro_tipo_pago','cobro_cuenta_destino','cobro_metodo_pago',
     'cobro_inp_monto','cobro_fecha','cobro_obs'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !habilitado;
    });
}

function abrirModalCobro(tramoId, label, saldo, moneda, clienteId) {
    moneda = moneda || 'BOB';
    document.getElementById('cobro_tramo_id').value           = tramoId;
    document.getElementById('cobro_tramo_label').textContent  = label;
    document.getElementById('cobro_saldo_label').textContent  = parseFloat(saldo).toFixed(2) + ' ' + moneda;
    document.getElementById('cobro_info_tramo').style.display = 'block';
    document.getElementById('cobro_inp_monto').value          = '';
    document.getElementById('cobro_aviso_sin_cuenta').style.display = 'none';
    document.getElementById('cobro_sec_codigo').style.display       = 'none';
    document.getElementById('cobro_btn_guardar').disabled           = true;

    // Resetear selects dependientes
    document.getElementById('cobro_tipo_pago').value      = '';
    document.getElementById('cobro_cuenta_destino').value = '';
    document.getElementById('cobro_metodo_pago').value    = '';
    document.getElementById('cobro_obs').value            = '';

    // Todos los campos (excepto cuenta origen) desactivados hasta cargar cuentas
    _setCamposCobro(false);

    _clienteActual = clienteId;
    toggleTipoCambio(moneda);

    cargarCuentasCliente(clienteId);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCobro')).show();
}

function cargarCuentasCliente(clienteId) {
    const sel   = document.getElementById('sel_cuenta_origen');
    const aviso = document.getElementById('cobro_aviso_sin_cuenta');
    const hint  = document.getElementById('cobro_hint_cuenta_origen');
    sel.innerHTML = '<option value="">-- Cargando... --</option>';

    if (!clienteId) {
        sel.innerHTML = '<option value="">-- Seleccione cliente --</option>';
        _setCamposCobro(false);
        return;
    }

    fetch(`/api/pagos/cuentas-cliente?cliente_id=${clienteId}`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                sel.innerHTML = '<option value="">-- Sin cuentas registradas --</option>';
                aviso.style.display = 'block';
                hint.textContent    = '';
                _setCamposCobro(false);
            } else {
                aviso.style.display = 'none';
                sel.innerHTML = '<option value="">-- Seleccione cuenta --</option>';
                data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value       = c.id;
                    opt.textContent = c.label;
                    sel.appendChild(opt);
                });
                hint.textContent = data.length + ' cuenta(s) del cliente.';
                _setCamposCobro(true);
            }
        });
}

// ===== Moneda / tipo de cambio =====
function toggleTipoCambio(moneda) {
    const secTC    = document.getElementById('cobro_sec_tipo_cambio');
    const secEquiv = document.getElementById('cobro_sec_equivalente');
    const inpTC    = document.getElementById('cobro_inp_tipo_cambio');
    const inpBob   = document.getElementById('cobro_inp_tipo_cambio_bob');
    const lblMonto = document.getElementById('cobro_lbl_moneda_monto');
    const lblTC    = document.getElementById('cobro_lbl_moneda_tc');

    document.getElementById('cobro_moneda_pago').value = moneda;
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
    const moneda = document.getElementById('cobro_moneda_pago').value;
    if (moneda === 'BOB') return;
    const monto    = parseFloat(document.getElementById('cobro_inp_monto').value) || 0;
    const tc       = parseFloat(document.getElementById('cobro_inp_tipo_cambio').value) || 0;
    const secEquiv = document.getElementById('cobro_sec_equivalente');
    const lblEquiv = document.getElementById('cobro_lbl_equivalente');
    if (monto > 0 && tc > 0) {
        lblEquiv.textContent   = 'Bs ' + (monto * tc).toFixed(2);
        secEquiv.style.display = 'block';
    } else {
        secEquiv.style.display = 'none';
    }
}

function toggleCodigo(metodo) {
    const sec   = document.getElementById('cobro_sec_codigo');
    const input = document.getElementById('cobro_codigo');
    if (metodo === 'transferencia') {
        sec.style.display = 'block';
        input.required    = true;
    } else {
        sec.style.display = 'none';
        input.required    = false;
        input.value       = '';
    }
}

function validarBobroCobro() {
    const cuentaOrigen  = document.getElementById('sel_cuenta_origen').value;
    const tipo          = document.getElementById('cobro_tipo_pago').value;
    const cuentaDest    = document.getElementById('cobro_cuenta_destino').value;
    const metodo        = document.getElementById('cobro_metodo_pago').value;
    const monto         = parseFloat(document.getElementById('cobro_inp_monto').value) || 0;
    const fecha         = document.getElementById('cobro_fecha').value;
    const codigoVisible = document.getElementById('cobro_sec_codigo').style.display !== 'none';
    const codigo        = document.getElementById('cobro_codigo').value.trim();

    const codigoOk = !codigoVisible || codigo !== '';

    const ok = cuentaOrigen !== '' && tipo !== '' && cuentaDest !== '' &&
               metodo !== '' && monto > 0 && fecha !== '' && codigoOk;

    document.getElementById('cobro_btn_guardar').disabled = !ok;
}

// ===== Modal detalle =====
let _detalleTramoId = null;

function verDetalle(tramoId) {
    _detalleTramoId = tramoId;
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalle'));
    document.getElementById('det_body').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-success"></div></div>';
    modal.show();

    fetch(`/api/pagos/clientes/${tramoId}/detalle`)
        .then(r => r.json())
        .then(d => {
            const mon = d.moneda_venta || 'BOB';
            document.getElementById('det_titulo').textContent =
                d.cliente + ' — ' + d.contrato + ' (' + (d.fecha_llegada || '—') + ')';

            let html = `
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Peso llegada</div>
                        <strong>${parseFloat(d.peso_llegada||0).toFixed(3)} t</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Precio / t</div>
                        <strong>${mon} ${parseFloat(d.precio_por_tonelada||0).toFixed(4)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted small">Total deuda</div>
                        <strong>${mon} ${parseFloat(d.monto_deuda||0).toFixed(2)}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center bg-${parseFloat(d.saldo)<=0?'success':'warning'} bg-opacity-10">
                        <div class="text-muted small">Saldo</div>
                        <strong class="${parseFloat(d.saldo)<=0?'text-success':'text-danger'}">
                            ${mon} ${parseFloat(d.saldo||0).toFixed(2)}
                        </strong>
                    </div>
                </div>
            </div>`;

            if (!d.pagos || d.pagos.length === 0) {
                html += '<div class="alert alert-info">No hay cobros registrados aún.</div>';
            } else {
                const monedaFlag = { BOB:'🇧🇴', USD:'🇺🇸', BRL:'🇧🇷', ARS:'🇦🇷', EUR:'🇪🇺', PEN:'🇵🇪', CLP:'🇨🇱', PYG:'🇵🇾', COP:'🇨🇴' };
                const badgeTipo  = { 'Adelanto':'bg-warning text-dark', 'Parcial':'bg-info text-dark', 'Pago Final':'bg-success' };

                html += `<div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th class="text-end">Monto cobrado</th>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Cuenta del cliente</th>
                            <th>Recibió</th>
                            <th>Código</th>
                            <th></th>
                        </tr>
                    </thead><tbody>`;

                d.pagos.forEach(p => {
                    const esBob  = p.moneda_pago === 'BOB';
                    const flag   = monedaFlag[p.moneda_pago] || '';
                    const badge  = p.anulado ? 'bg-secondary text-decoration-line-through' : (badgeTipo[p.tipo] || 'bg-secondary');
                    const tcLine = esBob ? '' :
                        `<br><span class="badge bg-light text-secondary border" style="font-size:.7rem;">
                            TC: 1 ${p.moneda_pago} = ${parseFloat(p.tipo_cambio).toFixed(4)} Bs
                        </span>`;

                    // Cuenta origen (cliente)
                    let celOrigen = '—';
                    if (p.cuenta_origen) {
                        const rel     = p.cuenta_origen.tipo_relacion
                            ? ` <span class="badge bg-secondary" style="font-size:.65rem">${p.cuenta_origen.tipo_relacion}</span>`
                            : '';
                        const titular = p.cuenta_origen.titular_cuenta
                            ? `<span class="fw-semibold text-warning-emphasis">👤 ${p.cuenta_origen.titular_cuenta}</span>${rel}<br>`
                            : '';
                        celOrigen = `${titular}<small class="text-muted">🏦 ${p.cuenta_origen.banco} <code>${p.cuenta_origen.numero}</code> [${p.cuenta_origen.moneda}]${p.cuenta_origen.alias ? ' (' + p.cuenta_origen.alias + ')' : ''}</small>`;
                    }

                    // Cuenta destino (empresa que recibe)
                    let celDestino = '—';
                    if (p.cuenta_destino) {
                        celDestino = `<small>${p.cuenta_destino.titular || '—'}${p.cuenta_destino.alias ? ' (' + p.cuenta_destino.alias + ')' : ''}</small>`;
                    }

                    const rowClass = p.anulado ? 'table-secondary text-muted' : '';
                    const anuloBadge = p.anulado
                        ? `<span class="badge bg-danger ms-1" style="font-size:.65rem">ANULADO</span>`
                        : '';
                    const montoStyle = p.anulado ? 'text-decoration:line-through;opacity:.6' : '';
                    const acciones = p.anulado
                        ? `<span class="text-muted small"><i class="bi bi-slash-circle me-1"></i>Anulado</span>`
                        : (canDeleteCobro
                            ? `<a href="/pagos/clientes/${p.uuid}/destroy"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Anular este cobro? Se revertirá el movimiento en tesorería.')">
                                   <i class="bi bi-trash"></i>
                                </a>`
                            : '');

                    html += `<tr class="${rowClass}">
                        <td><span class="badge ${badge}">${p.tipo}</span>${anuloBadge}</td>
                        <td class="text-end"><span class="fw-semibold" style="${montoStyle}">${flag} ${p.moneda_pago} ${parseFloat(p.monto).toFixed(2)}</span>${tcLine}</td>
                        <td><small>${p.fecha}</small></td>
                        <td><small>${p.metodo}</small></td>
                        <td>${celOrigen}</td>
                        <td>${celDestino}</td>
                        <td><small class="text-muted">${p.codigo||'—'}</small></td>
                        <td class="text-nowrap">${acciones}</td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
            }
            document.getElementById('det_body').innerHTML = html;

            // Event listeners con data attributes — evita problemas con caracteres especiales
            document.querySelectorAll('.btn-editar-cobro').forEach(btn => {
                btn.addEventListener('click', () => {
                    abrirEditarCobro(
                        btn.dataset.uuid,
                        btn.dataset.tipo,
                        btn.dataset.monto,
                        btn.dataset.fecha,
                        btn.dataset.metodo,
                        btn.dataset.codigo,
                        btn.dataset.obs
                    );
                });
            });

            document.querySelectorAll('.btn-anular-cobro').forEach(btn => {
                btn.addEventListener('click', () => anularCobro(btn.dataset.uuid));
            });
        });
}

// ===== COBRO MASIVO — toggle código =====
function toggleCodigoMasivo(metodo) {
    const sec   = document.getElementById('cm_sec_codigo');
    const input = document.getElementById('cm_codigo');
    if (metodo === 'transferencia') {
        sec.style.display = 'block';
        input.required    = true;
    } else {
        sec.style.display = 'none';
        input.required    = false;
        input.value       = '';
    }
}

function actualizarMonedaPorCuenta() {
    const sel    = document.getElementById('cm_cuenta_destino');
    const opt    = sel.options[sel.selectedIndex];
    const moneda = opt.dataset.moneda || 'BOB';
    const secTc  = document.getElementById('cm_sec_tc');
    const tcInput = document.getElementById('cm_tc');

    if (moneda === 'BOB') {
        secTc.style.display = 'none';
        tcInput.value       = '';
        tcInput.required    = false;
    } else {
        secTc.style.display = 'block';
        tcInput.value       = '';
        tcInput.required    = true;
    }
    actualizarResumenMasivo();
}

// ===== COBRO MASIVO =====
const _tramosData = @json($tramosMasivoData);

function _setCamposMasivo(habilitado) {
    ['cm_cuenta_origen','cm_cuenta_destino','cm_fecha','cm_metodo','cm_monto','cm_obs'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !habilitado;
    });
}

function abrirCobroMasivo() {
    document.getElementById('cm_cliente').value           = '';
    document.getElementById('cm_monto').value             = '';
    document.getElementById('cm_tbody').innerHTML         = '';
    document.getElementById('cm_contenedor_tramos').style.display = 'none';
    document.getElementById('cm_sin_cliente').style.display       = 'none';
    document.getElementById('cm_resumen').style.display           = 'none';
    document.getElementById('cm_btn_guardar').disabled            = true;
    document.getElementById('cm_sec_codigo').style.display        = 'none';
    document.getElementById('cm_sec_tc').style.display            = 'none';
    document.getElementById('cm_hint_cuenta_origen').textContent  = 'Se carga al seleccionar el cliente.';
    _setCamposMasivo(false);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCobroMasivo')).show();
}

function cargarCuentasClienteMasivo(clienteId) {
    const sel  = document.getElementById('cm_cuenta_origen');
    const hint = document.getElementById('cm_hint_cuenta_origen');
    sel.innerHTML = '<option value="">-- Cargando... --</option>';
    sel.disabled  = true;

    if (!clienteId) {
        sel.innerHTML    = '<option value="">-- Seleccione un cliente primero --</option>';
        hint.textContent = 'Se carga al seleccionar el cliente.';
        hint.className   = 'form-text text-muted';
        _setCamposMasivo(false);
        return;
    }

    fetch(`/api/pagos/cuentas-cliente?cliente_id=${clienteId}`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                sel.innerHTML    = '<option value="">-- Sin cuentas registradas --</option>';
                hint.textContent = 'Este cliente no tiene cuentas bancarias registradas. Regístrala en el módulo de clientes para continuar.';
                hint.className   = 'form-text text-danger fw-semibold';
                _setCamposMasivo(false);
            } else {
                sel.innerHTML = '<option value="">-- Efectivo / Sin cuenta --</option>';
                data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value       = c.id;
                    opt.textContent = c.label;
                    sel.appendChild(opt);
                });
                hint.textContent = data.length + ' cuenta(s) encontrada(s).';
                hint.className   = 'form-text text-success';
                _setCamposMasivo(true);
            }
        });
}

function cargarTramasMasivo() {
    const clienteId  = document.getElementById('cm_cliente').value;
    const tbody      = document.getElementById('cm_tbody');
    tbody.innerHTML  = '';
    cargarCuentasClienteMasivo(clienteId);

    const pendientes = _tramosData.filter(t => String(t.cliente_id) === String(clienteId));

    if (!clienteId || pendientes.length === 0) {
        document.getElementById('cm_contenedor_tramos').style.display = 'none';
        document.getElementById('cm_sin_cliente').style.display       = clienteId ? 'block' : 'none';
        document.getElementById('cm_resumen').style.display           = 'none';
        document.getElementById('cm_btn_guardar').disabled            = true;
        return;
    }

    document.getElementById('cm_sin_cliente').style.display       = 'none';
    document.getElementById('cm_contenedor_tramos').style.display = 'block';
    document.getElementById('cm_check_all').checked               = false;

    pendientes.forEach(t => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">
                <input type="checkbox" name="tramo_ids[]" value="${t.id}" onchange="actualizarResumenMasivo()">
            </td>
            <td><small class="fw-semibold">${t.contrato}</small></td>
            <td><small>${t.camion}</small></td>
            <td><small>${t.fecha}</small></td>
            <td class="text-end"><small>${t.moneda} ${t.deuda.toFixed(2)}</small></td>
            <td class="text-end text-success"><small>${t.moneda} ${t.cobrado.toFixed(2)}</small></td>
            <td class="text-end fw-semibold text-danger" data-saldo="${t.saldo}" data-moneda="${t.moneda}">
                <small>${t.moneda} ${t.saldo.toFixed(2)}</small>
            </td>`;
        tbody.appendChild(tr);
    });

    actualizarResumenMasivo();
}

function seleccionarTodosMasivo(estado) {
    document.querySelectorAll('#cm_tbody input[type=checkbox]').forEach(cb => cb.checked = estado);
    document.getElementById('cm_check_all').checked = estado;
    actualizarResumenMasivo();
}

function actualizarResumenMasivo() {
    const checks   = document.querySelectorAll('#cm_tbody input[type=checkbox]:checked');
    const monto    = parseFloat(document.getElementById('cm_monto').value) || 0;
    const selCta   = document.getElementById('cm_cuenta_destino');
    const optCta   = selCta ? selCta.options[selCta.selectedIndex] : null;
    const moneda   = (optCta && optCta.dataset.moneda) ? optCta.dataset.moneda : 'BOB';
    let saldoTotal = 0;

    checks.forEach(cb => {
        const td = cb.closest('tr').querySelector('[data-saldo]');
        saldoTotal += parseFloat(td?.dataset.saldo ?? 0);
    });

    const resumen = document.getElementById('cm_resumen');
    resumen.style.display = checks.length > 0 ? 'block' : 'none';

    document.getElementById('cm_res_cant').textContent  = checks.length;
    document.getElementById('cm_res_saldo').textContent = moneda + ' ' + saldoTotal.toFixed(2);
    document.getElementById('cm_res_monto').textContent = moneda + ' ' + monto.toFixed(2);

    const estadoEl     = document.getElementById('cm_res_estado');
    const cuentaOrigen  = document.getElementById('cm_cuenta_origen').value;
    const cuentaDestino = document.getElementById('cm_cuenta_destino').value;
    const habil         = checks.length > 0 && monto > 0 && cuentaOrigen !== '' && cuentaDestino !== '';

    if (checks.length > 0 && monto > 0 && !cuentaOrigen) {
        estadoEl.innerHTML = '<span class="text-danger fw-semibold">⚠ Debe seleccionar la cuenta bancaria del cliente</span>';
    } else if (checks.length > 0 && monto > 0 && !cuentaDestino) {
        estadoEl.innerHTML = '<span class="text-danger fw-semibold">⚠ Debe seleccionar la cuenta destino de la empresa</span>';
    } else if (habil && monto >= saldoTotal) {
        estadoEl.innerHTML = '<span class="text-success fw-semibold">✓ El monto cubre todas las entregas seleccionadas</span>';
    } else if (habil) {
        estadoEl.innerHTML = '<span class="text-warning fw-semibold">⚠ El monto cubre parcialmente las entregas seleccionadas</span>';
    } else {
        estadoEl.innerHTML = '';
    }

    document.getElementById('cm_btn_guardar').disabled = !habil;
}

let _editarCobroUuid = null;

function abrirEditarCobro(uuid, tipo, monto, fecha, metodo, codigo, obs) {
    _editarCobroUuid = uuid;
    document.getElementById('ec_tipo').value   = tipo;
    document.getElementById('ec_monto').value  = monto;
    document.getElementById('ec_fecha').value  = fecha;
    document.getElementById('ec_metodo').value = metodo;
    document.getElementById('ec_codigo').value = codigo;
    document.getElementById('ec_obs').value    = obs;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarCobro')).show();
}

function anularCobro(uuid) {
    if (!confirm('¿Anular este cobro? Se registrará una reversa en tesorería.')) return;

    fetch(`/pagos/clientes/${uuid}/destroy`)
        .then(r => {
            if (r.ok || r.redirected) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarCobro')).hide();
                verDetalle(_detalleTramoId);
            } else {
                alert('Error al anular el cobro.');
            }
        })
        .catch(() => alert('Error de conexión.'));
}

function guardarEditarCobro() {
    const btn = document.querySelector('#modalEditarCobro .btn-warning');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    fetch(`/pagos/clientes/${_editarCobroUuid}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            tipo_pago:          document.getElementById('ec_tipo').value,
            monto:              document.getElementById('ec_monto').value,
            fecha_pago:         document.getElementById('ec_fecha').value,
            metodo_pago:        document.getElementById('ec_metodo').value,
            codigo_seguimiento: document.getElementById('ec_codigo').value,
            observaciones:      document.getElementById('ec_obs').value,
        }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarCobro')).hide();
            verDetalle(_detalleTramoId);
        } else {
            alert('Error al guardar los cambios.');
        }
    })
    .catch(() => alert('Error de conexión.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar cambios';
    });
}
</script>
@endsection
