@extends('layouts.app')
@section('titulo', 'Seguimiento de Cargas')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>SEGUIMIENTO DE CARGAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Seguimiento de Cargas</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                data-steps='[
                    {"intro":"📍 Esta pantalla monitorea en <b>tiempo real</b> dónde está cada carga en su recorrido. Cada fila es un <b>tramo</b> de transporte. Te muestro cómo leerla."},
                    {"element":"#tarjetas-resumen","intro":"📊 Estas tarjetas resumen cuántas cargas hay en cada estado: <b>En ruta</b>, <b>Transbordando</b>, <b>Transbordado</b> y <b>Entregadas</b>.","position":"bottom"},
                    {"element":"#filtro_proveedor_seg","intro":"🔎 Puedes <b>filtrar</b> las cargas por proveedor para enfocarte en uno solo.","position":"bottom"},
                    {"element":"#segTabs","intro":"🗂️ Las pestañas separan las cargas según su <b>estado</b>. Te explico qué significa cada una.","position":"bottom"},
                    {"element":"#segTabs button[data-bs-target=\"#pane-en-ruta\"]","intro":"🚛 <b>En ruta</b>: el camión va viajando hacia su destino. Todavía no ha llegado. Desde aquí registras su llegada.","position":"bottom"},
                    {"element":"#segTabs button[data-bs-target=\"#pane-transbordando\"]","intro":"🔄 <b>Transbordando</b>: el camión llegó y su carga se está pasando a otro(s) camión(es) — por ejemplo en una frontera. Aún quedan toneladas por reasignar.","position":"bottom"},
                    {"element":"#segTabs button[data-bs-target=\"#pane-transbordado\"]","intro":"✅ <b>Transbordado</b>: la carga ya se traspasó completamente a otros camiones y continúa su viaje en ellos.","position":"bottom"},
                    {"element":"#segTabs button[data-bs-target=\"#pane-entregados\"]","intro":"🏁 <b>Entregados</b>: la carga llegó a su destino final y se entregó al cliente. Aquí ves el historial de entregas con su peso y descuentos.","position":"bottom"},
                    {"element":"#tabla_en_ruta","intro":"📋 Dentro de cada pestaña, cada fila muestra el contrato, el camión, el conductor, la ruta (origen → destino), el peso y cuánto del flete se ha pagado.","position":"top"},
                    {"element":"#tabla_en_ruta tbody tr:first-child td:last-child","intro":"⚙️ El botón <b>Opciones</b> de cada carga te deja <b>Registrar llegada</b>, registrar el pago del flete, asignar flete o ver el contrato.","position":"left"}
                ]'>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">

    <div class="card mb-3">
        <div class="card-body py-3">
            <h5 class="card-title mb-1">Seguimiento de Cargas</h5>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Monitorea en tiempo real el estado de todos los tramos de transporte activos.
                Desde aquí puedes ver qué camiones están en ruta, cuáles están transbordando su carga a otras unidades,
                cuáles ya completaron el transbordo y cuáles han entregado al cliente final.
                Los camiones en ruta pueden registrar su llegada directamente desde este módulo.
            </p>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row mb-4" id="tarjetas-resumen">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#0d6efd;"><i class="bi bi-truck"></i></div>
                <div class="fw-bold fs-4" id="contador_tarjeta_en_ruta">{{ $resumen['en_ruta'] }}</div>
                <div class="text-muted small">En ruta</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#ffc107;"><i class="bi bi-arrow-left-right"></i></div>
                <div class="fw-bold fs-4" id="contador_tarjeta_transbordando">{{ $resumen['transbordando'] }}</div>
                <div class="text-muted small">Transbordando</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#0dcaf0;"><i class="bi bi-check2-all"></i></div>
                <div class="fw-bold fs-4" id="contador_tarjeta_transbordado">{{ $resumen['transbordado'] }}</div>
                <div class="text-muted small">Transbordado</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#198754;"><i class="bi bi-check-circle"></i></div>
                <div class="fw-bold fs-4" id="contador_tarjeta_entregado">{{ $resumen['entregado'] }}</div>
                <div class="text-muted small">Entregados</div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-0"><i class="bi bi-box-seam me-1"></i>Proveedor:</label>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filtro_proveedor_seg" onchange="aplicarFiltrosSeg()">
                        <option value="">— Todos —</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-0"><i class="bi bi-truck me-1"></i>Tipo transporte:</label>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filtro_tipo_tramo_seg" onchange="aplicarFiltrosSeg()">
                        <option value="">— Todos —</option>
                        <option value="Nacional">Nacional</option>
                        <option value="Internacional">Internacional</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-0"><i class="bi bi-cash-stack me-1"></i>Estado flete:</label>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filtro_flete_estado_seg" onchange="aplicarFiltrosSeg()">
                        <option value="">— Todos —</option>
                        <option value="sin_flete">Sin flete asignado</option>
                        <option value="pendiente">Con saldo pendiente</option>
                        <option value="pagado">Pagado completo</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosSeg()">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            <p class="text-muted d-none mt-2 fs-5" id="resumen_contratos_tipo"></p>
            <ul class="nav nav-tabs" id="segTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-en-ruta" type="button">
                        <i class="bi bi-truck text-primary"></i> En ruta
                        <span class="badge bg-primary ms-1" id="badge_tab_en_ruta" data-total="{{ $resumen['en_ruta'] }}">{{ $resumen['en_ruta'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-transbordando" type="button">
                        <i class="bi bi-arrow-left-right text-warning"></i> Transbordando
                        <span class="badge bg-warning text-dark ms-1" id="badge_tab_transbordando" data-total="{{ $resumen['transbordando'] }}">{{ $resumen['transbordando'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-transbordado" type="button">
                        <i class="bi bi-check2-all text-info"></i> Transbordado
                        <span class="badge bg-info text-dark ms-1" id="badge_tab_transbordado" data-total="{{ $resumen['transbordado'] }}">{{ $resumen['transbordado'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-entregados" type="button">
                        <i class="bi bi-check-circle text-success"></i> Entregados
                        <span class="badge bg-success ms-1" id="badge_tab_entregado" data-total="{{ $resumen['entregado'] }}">{{ $resumen['entregado'] }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3">

                {{-- EN RUTA --}}
                <div class="tab-pane fade show active" id="pane-en-ruta">
                    @if($enRuta->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay camiones en ruta.</div>
                    @else
                    @can('tramo.edit')
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btn_entrega_masiva" disabled onclick="abrirModalEntregaMasiva()">
                            <i class="bi bi-truck"></i> Entrega masiva (<span id="lbl_entrega_masiva_count">0</span>)
                        </button>
                        <small class="text-muted" id="lbl_entrega_masiva_ayuda">Selecciona 2 o más tramos para entregarlos juntos.</small>
                    </div>
                    @endcan
                    <div class="table-responsive">
                        <table id="tabla_en_ruta" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    @can('tramo.edit')
                                    <th style="width:1%;"><input type="checkbox" id="chk_en_ruta_todos" onclick="toggleTodosEntregaMasiva(this)"></th>
                                    @endcan
                                    <th style="white-space:nowrap; width:1%;">Contrato</th>
                                    <th>Proveedor</th>
                                    <th>Camión</th>
                                    <th>Conductor</th>
                                    <th>Ruta</th>
                                    <th>Tipo</th>
                                    <th>Peso salida</th>
                                    <th>Fecha salida</th>
                                    <th class="text-center">Flete pagado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enRuta as $t)
                                @php
                                    $ccFlete = $t->contratoCamion;
                                    $fleteEstado = !$ccFlete->monto_acordado ? 'sin_flete' : ($ccFlete->saldo_pendiente > 0 ? 'pendiente' : 'pagado');
                                @endphp
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}" data-tipo-tramo="{{ $t->tipo_tramo }}" data-flete-estado="{{ $fleteEstado }}">
                                    @can('tramo.edit')
                                    <td>
                                        <input type="checkbox" class="chk_entrega_masiva"
                                            data-uuid="{{ $t->uuid }}"
                                            data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}"
                                            data-proveedor-nombre="{{ addslashes($t->contratoCamion->contrato->proveedor->nombre ?? '') }}"
                                            data-placa="{{ addslashes($t->camion->placa) }}"
                                            data-ruta="{{ addslashes($t->origen . ' → ' . $t->destino) }}"
                                            data-peso-salida="{{ $t->peso_salida }}"
                                            onchange="actualizarSeleccionEntregaMasiva()">
                                    </td>
                                    @endcan
                                    <td style="white-space:nowrap;">
                                        <a href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}"
                                            class="fw-bold text-primary text-decoration-none">
                                            {{ $t->contratoCamion->contrato->numero_contrato }}
                                        </a>
                                    </td>
                                    <td>
                                        <small>{{ $t->contratoCamion->contrato->proveedor?->nombre ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $t->camion->placa }}</strong>
                                        <small class="text-muted d-block">{{ $t->camion->marca->valor ?? '-' }} {{ $t->camion->modelo }}</small>
                                    </td>
                                    <td>
                                        @if($t->conductor)
                                            {{ $t->conductor->nombre_completo }}
                                            <small class="text-muted d-block">Lic: {{ $t->conductor->licencia_numero ?? '—' }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $t->origen }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="small">{{ $t->destino }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $t->tipo_tramo === 'Internacional' ? 'danger' : 'secondary' }}">
                                            {{ $t->tipo_tramo }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($t->peso_salida, 2, ',', '.') }} t</td>
                                    <td>{{ $t->fecha_salida?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @php
                                            $cc  = $t->contratoCamion;
                                            $pct = $cc->monto_neto > 0 ? min(100, round($cc->total_pagado / $cc->monto_neto * 100)) : null;
                                        @endphp
                                        @if(is_null($pct))
                                            <span class="text-muted small">Sin flete</span>
                                        @else
                                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                                <div class="progress flex-grow-1" style="height:8px;min-width:60px;">
                                                    <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary') }}"
                                                        style="width:{{ $pct }}%"></div>
                                                </div>
                                                <small class="{{ $pct >= 100 ? 'text-success' : 'text-warning' }} fw-semibold">{{ $pct }}%</small>
                                            </div>
                                            <div class="text-muted" style="font-size:.68rem;white-space:nowrap">
                                                {{ $cc->moneda_flete ?? 'BOB' }}
                                                {{ number_format($cc->total_pagado, 0, ',', '.') }} / {{ number_format($cc->monto_neto, 0, ',', '.') }}
                                                <span class="{{ $cc->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                                                    (saldo {{ number_format($cc->saldo_pendiente, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-list-ul"></i> Opciones
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('contratos.edit')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalLlegada('{{ $t->uuid }}','{{ $t->origen }} → {{ $t->destino }} ({{ $t->camion->placa }})','{{ $t->peso_salida }}','{{ $t->fecha_salida->format('Y-m-d') }}',{{ $t->camion_id }},{{ $t->conductor_id ?? 'null' }},'{{ $t->tipo_tramo }}',{{ $t->contratoCamion->contrato->proveedor_id }},'{{ $t->contratoCamion->contrato->proveedor->tipo_proveedor }}',{{ $t->contratoCamion->contrato_id }})">
                                                        <i class="bi bi-geo-alt text-success me-2"></i> Registrar llegada
                                                    </button>
                                                </li>
                                                @if($t->contratoCamion->monto_acordado)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalPagoSeg({{ $t->contratoCamion->id }},'{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}',{{ $t->contratoCamion->saldo_pendiente }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}',{{ $t->conductor_id ?? 'null' }},'{{ addslashes($t->conductor?->nombre_completo ?? '') }}',{{ $t->contratoCamion->camion->propietario_id ?? 'null' }},'{{ addslashes($t->contratoCamion->camion->propietario?->nombre_completo ?? '') }}')">
                                                        <i class="bi bi-cash-coin text-warning me-2"></i> Registrar pago
                                                    </button>
                                                </li>
                                                @if($t->contratoCamion->total_pagado == 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}',{{ $t->contratoCamion->monto_acordado }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}')">
                                                        <i class="bi bi-pencil text-info me-2"></i> Editar flete
                                                    </button>
                                                </li>
                                                @endif
                                                @else
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}')">
                                                        <i class="bi bi-tag text-info me-2"></i> Asignar flete
                                                    </button>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('contratos.index')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}">
                                                        <i class="bi bi-eye text-primary me-2"></i> Ver contrato
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('pagos_camiones.index')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirHistorialPagos({{ $t->contratoCamion->id }}, '{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}')">
                                                        <i class="bi bi-clock-history text-secondary me-2"></i> Ver historial de pagos
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

                {{-- TRANSBORDANDO --}}
                <div class="tab-pane fade" id="pane-transbordando">
                    @if($transbordando->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay camiones transbordando.</div>
                    @else
                    <div class="table-responsive">
                        <table id="tabla_transbordando" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="white-space:nowrap; width:1%;">Contrato</th>
                                    <th>Proveedor</th>
                                    <th>Camión</th>
                                    <th>Conductor</th>
                                    <th>Ruta</th>
                                    <th>Tipo</th>
                                    <th>Peso llegada</th>
                                    <th>Fecha llegada</th>
                                    <th class="text-center">Flete pagado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transbordando as $t)
                                @php
                                    $yaAsignadoHijos      = (float) $t->tramosHijos()->where('activo', true)->sum('peso_salida');
                                    $disponibleTransbordo = round((float) $t->peso_llegada - $yaAsignadoHijos, 3);
                                    $ccFlete2 = $t->contratoCamion;
                                    $fleteEstado2 = !$ccFlete2->monto_acordado ? 'sin_flete' : ($ccFlete2->saldo_pendiente > 0 ? 'pendiente' : 'pagado');
                                @endphp
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}" data-tipo-tramo="{{ $t->tipo_tramo }}" data-flete-estado="{{ $fleteEstado2 }}">
                                    <td style="white-space:nowrap;">
                                        <a href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}"
                                            class="fw-bold text-primary text-decoration-none">
                                            {{ $t->contratoCamion->contrato->numero_contrato }}
                                        </a>
                                    </td>
                                    <td>
                                        <small>{{ $t->contratoCamion->contrato->proveedor?->nombre ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $t->camion->placa }}</strong>
                                        <small class="text-muted d-block">{{ $t->camion->marca->valor ?? '-' }} {{ $t->camion->modelo }}</small>
                                    </td>
                                    <td>
                                        @if($t->conductor)
                                            {{ $t->conductor->nombre_completo }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $t->origen }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="small">{{ $t->destino }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $t->tipo_tramo === 'Internacional' ? 'danger' : 'secondary' }}">
                                            {{ $t->tipo_tramo }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($t->peso_llegada, 2, ',', '.') }} t
                                        @if($disponibleTransbordo > 0)
                                            <small class="text-warning d-block">{{ number_format($disponibleTransbordo, 2, ',', '.') }} t libres</small>
                                        @endif
                                    </td>
                                    <td>{{ $t->fecha_llegada?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @php
                                            $cc  = $t->contratoCamion;
                                            $pct = $cc->monto_neto > 0 ? min(100, round($cc->total_pagado / $cc->monto_neto * 100)) : null;
                                        @endphp
                                        @if(is_null($pct))
                                            <span class="text-muted small">Sin flete</span>
                                        @else
                                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                                <div class="progress flex-grow-1" style="height:8px;min-width:60px;">
                                                    <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary') }}"
                                                        style="width:{{ $pct }}%"></div>
                                                </div>
                                                <small class="{{ $pct >= 100 ? 'text-success' : 'text-warning' }} fw-semibold">{{ $pct }}%</small>
                                            </div>
                                            <div class="text-muted" style="font-size:.68rem;white-space:nowrap">
                                                {{ $cc->moneda_flete ?? 'BOB' }}
                                                {{ number_format($cc->total_pagado, 0, ',', '.') }} / {{ number_format($cc->monto_neto, 0, ',', '.') }}
                                                <span class="{{ $cc->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                                                    (saldo {{ number_format($cc->saldo_pendiente, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-list-ul"></i> Opciones
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('contratos.edit')
                                                @if($disponibleTransbordo > 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalTransbordoSeg({{ $t->contrato_camion_id }}, {{ $t->id }}, '{{ addslashes($t->camion->placa) }} → {{ addslashes($t->destino) }}', {{ $disponibleTransbordo }}, '{{ $t->fecha_llegada?->format('Y-m-d') }}')">
                                                        <i class="bi bi-arrow-down-right text-info me-2"></i> Agregar transbordo
                                                    </button>
                                                </li>
                                                @endif
                                                @if($t->contratoCamion->monto_acordado)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalPagoSeg({{ $t->contratoCamion->id }},'{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}',{{ $t->contratoCamion->saldo_pendiente }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}',{{ $t->conductor_id ?? 'null' }},'{{ addslashes($t->conductor?->nombre_completo ?? '') }}',{{ $t->contratoCamion->camion->propietario_id ?? 'null' }},'{{ addslashes($t->contratoCamion->camion->propietario?->nombre_completo ?? '') }}')">
                                                        <i class="bi bi-cash-coin text-warning me-2"></i> Registrar pago
                                                    </button>
                                                </li>
                                                @if($t->contratoCamion->total_pagado == 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}',{{ $t->contratoCamion->monto_acordado }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}')">
                                                        <i class="bi bi-pencil text-info me-2"></i> Editar flete
                                                    </button>
                                                </li>
                                                @endif
                                                @else
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}')">
                                                        <i class="bi bi-tag text-info me-2"></i> Asignar flete
                                                    </button>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('contratos.index')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}">
                                                        <i class="bi bi-eye text-primary me-2"></i> Ver contrato
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('pagos_camiones.index')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirHistorialPagos({{ $t->contratoCamion->id }}, '{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}')">
                                                        <i class="bi bi-clock-history text-secondary me-2"></i> Ver historial de pagos
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

                {{-- TRANSBORDADO --}}
                <div class="tab-pane fade" id="pane-transbordado">
                    @if($transbordado->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay camiones transbordados.</div>
                    @else
                    <div class="table-responsive">
                        <table id="tabla_transbordado" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="white-space:nowrap; width:1%;">Contrato</th>
                                    <th>Proveedor</th>
                                    <th>Camión</th>
                                    <th>Conductor</th>
                                    <th>Ruta</th>
                                    <th>Tipo</th>
                                    <th>Peso llegada</th>
                                    <th>Fecha llegada</th>
                                    <th class="text-center">Flete pagado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transbordado as $t)
                                @php
                                    $ccFlete3 = $t->contratoCamion;
                                    $fleteEstado3 = !$ccFlete3->monto_acordado ? 'sin_flete' : ($ccFlete3->saldo_pendiente > 0 ? 'pendiente' : 'pagado');
                                @endphp
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}" data-tipo-tramo="{{ $t->tipo_tramo }}" data-flete-estado="{{ $fleteEstado3 }}">
                                    <td style="white-space:nowrap;">
                                        <a href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}"
                                            class="fw-bold text-primary text-decoration-none">
                                            {{ $t->contratoCamion->contrato->numero_contrato }}
                                        </a>
                                    </td>
                                    <td>
                                        <small>{{ $t->contratoCamion->contrato->proveedor?->nombre ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $t->camion->placa }}</strong>
                                        <small class="text-muted d-block">{{ $t->camion->marca->valor ?? '-' }} {{ $t->camion->modelo }}</small>
                                    </td>
                                    <td>
                                        @if($t->conductor)
                                            {{ $t->conductor->nombre_completo }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $t->origen }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="small">{{ $t->destino }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $t->tipo_tramo === 'Internacional' ? 'danger' : 'secondary' }}">
                                            {{ $t->tipo_tramo }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($t->peso_llegada, 2, ',', '.') }} t</td>
                                    <td>{{ $t->fecha_llegada?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @php
                                            $cc  = $t->contratoCamion;
                                            $pct = $cc->monto_neto > 0 ? min(100, round($cc->total_pagado / $cc->monto_neto * 100)) : null;
                                        @endphp
                                        @if(is_null($pct))
                                            <span class="text-muted small">Sin flete</span>
                                        @else
                                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                                <div class="progress flex-grow-1" style="height:8px;min-width:60px;">
                                                    <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary') }}"
                                                        style="width:{{ $pct }}%"></div>
                                                </div>
                                                <small class="{{ $pct >= 100 ? 'text-success' : 'text-warning' }} fw-semibold">{{ $pct }}%</small>
                                            </div>
                                            <div class="text-muted" style="font-size:.68rem;white-space:nowrap">
                                                {{ $cc->moneda_flete ?? 'BOB' }}
                                                {{ number_format($cc->total_pagado, 0, ',', '.') }} / {{ number_format($cc->monto_neto, 0, ',', '.') }}
                                                <span class="{{ $cc->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                                                    (saldo {{ number_format($cc->saldo_pendiente, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-list-ul"></i> Opciones
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('contratos.edit')
                                                @if($t->contratoCamion->monto_acordado)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalPagoSeg({{ $t->contratoCamion->id }},'{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}',{{ $t->contratoCamion->saldo_pendiente }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}',{{ $t->conductor_id ?? 'null' }},'{{ addslashes($t->conductor?->nombre_completo ?? '') }}',{{ $t->contratoCamion->camion->propietario_id ?? 'null' }},'{{ addslashes($t->contratoCamion->camion->propietario?->nombre_completo ?? '') }}')">
                                                        <i class="bi bi-cash-coin text-warning me-2"></i> Registrar pago
                                                    </button>
                                                </li>
                                                @if($t->contratoCamion->total_pagado == 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}',{{ $t->contratoCamion->monto_acordado }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}')">
                                                        <i class="bi bi-pencil text-info me-2"></i> Editar flete
                                                    </button>
                                                </li>
                                                @endif
                                                @else
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $t->contratoCamion->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($t->contratoCamion->contrato->numero_contrato) }}')">
                                                        <i class="bi bi-tag text-info me-2"></i> Asignar flete
                                                    </button>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('contratos.index')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('contratos.camiones', $t->contratoCamion->contrato->uuid) }}">
                                                        <i class="bi bi-eye text-primary me-2"></i> Ver contrato
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('tramo.nota-entrega', $t->uuid) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf text-success me-2"></i> Nota de entrega
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('pagos_camiones.index')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirHistorialPagos({{ $t->contratoCamion->id }}, '{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}')">
                                                        <i class="bi bi-clock-history text-secondary me-2"></i> Ver historial de pagos
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

                {{-- ENTREGADOS --}}
                <div class="tab-pane fade" id="pane-entregados">
                    <div id="pane-entregados-contenido" data-total-entregados="{{ $resumen['entregado'] }}">
                    @if($entregados->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay entregas registradas aún.</div>
                    @else
                    @if($entregados instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <p class="text-muted small"><i class="bi bi-info-circle"></i> Mostrando {{ $entregados->firstItem() }}–{{ $entregados->lastItem() }} de {{ $entregados->total() }} entregas.</p>
                    @else
                    <p class="text-muted small"><i class="bi bi-info-circle"></i> {{ $entregados->count() }} entregas encontradas.</p>
                    @endif
                    <div class="table-responsive">
                        <table id="tabla_entregados" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="white-space:nowrap; width:1%;">Contrato</th>
                                    <th>Proveedor</th>
                                    <th>Camión</th>
                                    <th>Conductor</th>
                                    <th>Ruta</th>
                                    <th>Tipo</th>
                                    <th>Peso llegada</th>
                                    <th>Fecha entrega</th>
                                    <th>Cliente</th>
                                    <th>Descuento</th>
                                    <th class="text-center">Flete pagado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entregados as $t)
                                @php
                                    $cc = $t->contratoCamion;
                                    $fleteEstado4 = !$cc->monto_acordado ? 'sin_flete' : ($cc->saldo_pendiente > 0 ? 'pendiente' : 'pagado');
                                @endphp
                                <tr class="{{ !$cc->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $cc->contrato->proveedor_id }}" data-tipo-tramo="{{ $t->tipo_tramo }}" data-flete-estado="{{ $fleteEstado4 }}">
                                    <td style="white-space:nowrap;">
                                        <a href="{{ route('contratos.camiones', $cc->contrato->uuid) }}"
                                            class="fw-bold text-primary text-decoration-none">
                                            {{ $cc->contrato->numero_contrato }}
                                        </a>
                                    </td>
                                    <td>
                                        <small>{{ $cc->contrato->proveedor?->nombre ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $t->camion->placa }}</strong>
                                        <small class="text-muted d-block">{{ $t->camion->marca->valor ?? '-' }} {{ $t->camion->modelo }}</small>
                                    </td>
                                    <td>{{ $t->conductor?->nombre_completo ?? '—' }}</td>
                                    <td>
                                        <span class="text-muted small">{{ $t->origen }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="small">{{ $t->destino }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $t->tipo_tramo === 'Internacional' ? 'danger' : 'secondary' }}">
                                            {{ $t->tipo_tramo }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">{{ number_format($t->peso_llegada, 2, ',', '.') }} t</strong>
                                        @if($t->estado === 'Div. Carga')
                                            <span class="badge bg-info text-dark d-block mt-1"><i class="bi bi-pie-chart"></i> Div. Carga</span>
                                        @endif
                                    </td>
                                    <td>{{ $t->fecha_llegada?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $t->cliente?->nombre ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($t->descuento_porcentaje)
                                            <span class="badge bg-danger">{{ number_format($t->descuento_porcentaje, 1) }}%</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $pct = $cc->monto_neto > 0 ? min(100, round($cc->total_pagado / $cc->monto_neto * 100)) : null;
                                        @endphp
                                        @if(is_null($pct))
                                            <span class="text-muted small">Sin flete</span>
                                        @else
                                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                                <div class="progress flex-grow-1" style="height:8px;min-width:60px;">
                                                    <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary') }}"
                                                        style="width:{{ $pct }}%"></div>
                                                </div>
                                                <small class="{{ $pct >= 100 ? 'text-success' : 'text-warning' }} fw-semibold">{{ $pct }}%</small>
                                            </div>
                                            <div class="text-muted" style="font-size:.68rem;white-space:nowrap">
                                                {{ $cc->moneda_flete ?? 'BOB' }}
                                                {{ number_format($cc->total_pagado, 0, ',', '.') }} / {{ number_format($cc->monto_neto, 0, ',', '.') }}
                                                <span class="{{ $cc->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                                                    (saldo {{ number_format($cc->saldo_pendiente, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-list-ul"></i> Opciones
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('contratos.edit')
                                                @if($cc->monto_acordado)
                                                @if($cc->saldo_pendiente > 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalPagoSeg({{ $cc->id }},'{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}',{{ $cc->saldo_pendiente }},'{{ $cc->moneda_flete ?? 'BOB' }}',{{ $t->conductor_id ?? 'null' }},'{{ addslashes($t->conductor?->nombre_completo ?? '') }}',{{ $cc->camion->propietario_id ?? 'null' }},'{{ addslashes($cc->camion->propietario?->nombre_completo ?? '') }}')">
                                                        <i class="bi bi-cash-coin text-warning me-2"></i> Registrar pago
                                                    </button>
                                                </li>
                                                @else
                                                <li>
                                                    <span class="dropdown-item text-success"><i class="bi bi-check-circle me-2"></i> Flete pagado</span>
                                                </li>
                                                @endif
                                                @if($cc->total_pagado == 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $cc->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($cc->contrato->numero_contrato) }}',{{ $cc->monto_acordado }},'{{ $cc->moneda_flete ?? 'BOB' }}')">
                                                        <i class="bi bi-pencil text-info me-2"></i> Editar flete
                                                    </button>
                                                </li>
                                                @endif
                                                @else
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalFlete('{{ $cc->uuid }}','{{ addslashes($t->camion->placa) }} — {{ addslashes($cc->contrato->numero_contrato) }}')">
                                                        <i class="bi bi-tag text-info me-2"></i> Asignar flete
                                                    </button>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('contratos.index')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('contratos.camiones', $cc->contrato->uuid) }}">
                                                        <i class="bi bi-eye text-primary me-2"></i> Ver contrato
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('tramo.nota-entrega', $t->uuid) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf text-success me-2"></i> Nota de entrega
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('tramo.edit')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalEditarLote('{{ $t->uuid }}', {{ $t->contratoCamion->contrato->proveedor_id }}, {{ $t->lote_entrega_id ?? 'null' }})">
                                                        <i class="bi bi-collection text-info me-2"></i> Editar lote de entrega
                                                    </button>
                                                </li>
                                                @endcan
                                                @can('pagos_camiones.index')
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirHistorialPagos({{ $t->contratoCamion->id }}, '{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}')">
                                                        <i class="bi bi-clock-history text-secondary me-2"></i> Ver historial de pagos
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
                    @if($entregados instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center">
                        {{ $entregados->links() }}
                    </div>
                    @endif
                    @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

</section>

{{-- ===== MODAL REGISTRAR PAGO (desde seguimiento) ===== --}}
<div class="modal fade" id="modalPagoSeg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pagos.camiones.store') }}" enctype="multipart/form-data">
                @csrf
                {{-- Token de idempotencia: sin él, store() rechaza el pago como duplicado --}}
                <input type="hidden" name="_idempotency_token" id="segIdempotencyToken" value="{{ $idempotencyToken ?? '' }}">
                <input type="hidden" name="contrato_camion_id" id="seg_pago_cc_id">
                <div class="modal-body">

                    {{-- Contexto: contrato y proveedor arriba, camión abajo --}}
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div>
                                    <span class="text-muted small">Contrato:</span>
                                    <strong id="seg_pago_contrato_label">—</strong>
                                </div>
                                <div>
                                    <span class="text-muted small">Proveedor:</span>
                                    <strong id="seg_pago_proveedor_label">—</strong>
                                </div>
                                <div class="mt-1 pt-1 border-top">
                                    <i class="bi bi-truck text-muted me-1"></i>
                                    <strong id="seg_pago_camion_label"></strong>
                                    <span class="text-muted small ms-1" id="seg_pago_tipo_camion"></span>
                                </div>
                                <div>
                                    <i class="bi bi-person text-muted me-1"></i>
                                    <span class="text-muted small">Conductor:</span>
                                    <strong id="seg_pago_conductor_label">—</strong>
                                </div>
                                <div>
                                    <i class="bi bi-signpost-split text-muted me-1"></i>
                                    <span class="text-muted small">Ruta:</span>
                                    <strong id="seg_pago_ruta_label">—</strong>
                                </div>
                            </div>
                            <span class="text-end flex-shrink-0">
                                <small class="text-muted d-block" id="seg_pago_saldo_lbl_texto">Saldo pendiente del flete:</small>
                                <span class="badge bg-danger" id="seg_pago_saldo_label"></span>
                            </span>
                        </div>
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_pago" required>
                                <option value="">-- Seleccione --</option>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Pago <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_pago" required value="{{ date('Y-m-d') }}">
                        </div>

                        {{-- Monto / Moneda / Tipo de cambio --}}
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                {{-- Moneda oculta: se fija con la moneda del flete al abrir el modal --}}
                                <input type="hidden" name="moneda_pago" id="seg_moneda_pago">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold mb-1">Monto <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold" id="seg_lbl_moneda">BOB</span>
                                            <input type="text" inputmode="numeric" class="form-control" id="seg_inp_monto_display" placeholder="0,00" autocomplete="off">
                                            <input type="hidden" name="monto" id="seg_inp_monto" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-5" id="seg_sec_tc" style="display:none;">
                                        <label class="form-label fw-semibold mb-1">
                                            Tipo de cambio <span class="text-danger">*</span>
                                            <small class="text-muted fw-normal">— 1 <span id="seg_lbl_tc_moneda"></span> =</small>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" inputmode="numeric" class="form-control" id="seg_inp_tc_display" placeholder="0,00" autocomplete="off" disabled>
                                            <input type="hidden" name="tipo_cambio" id="seg_inp_tc" value="">
                                            <span class="input-group-text">BOB</span>
                                        </div>
                                    </div>
                                </div>
                                <div id="seg_sec_equiv" style="display:none;" class="mt-2">
                                    <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2" style="background:#e8f4fd; border:1px solid #b8d9f5;">
                                        <i class="bi bi-arrow-left-right text-primary"></i>
                                        <span class="text-muted small">Equivalente en bolivianos:</span>
                                        <strong class="text-primary fs-6" id="seg_lbl_equiv">—</strong>
                                        <span class="text-muted small">BOB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="seg_inp_tc_bob" value="1">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="seg_metodo_pago" required onchange="segToggleCodigo(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="seg_sec_codigo" style="display:none;">
                            <label class="form-label">Código de transferencia</label>
                            <input type="text" class="form-control" name="codigo_seguimiento" maxlength="100" placeholder="Ej: TRX-001">
                        </div>

                        {{-- Receptor --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pagar a</label>
                            <select class="form-select" name="receptor_type" id="seg_receptor_type" onchange="segCambiarReceptor(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="conductor" id="seg_opt_conductor">Conductor</option>
                                <option value="propietario" id="seg_opt_propietario">Propietario</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="seg_sec_receptor_nombre" style="display:none;">
                            <label class="form-label">Nombre del Receptor</label>
                            <input type="text" class="form-control" id="seg_receptor_display" readonly>
                            <input type="hidden" name="receptor_id" id="seg_receptor_id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cuenta Origen (Tesorería)</label>
                            <select class="form-select" name="cuenta_origen_id" id="seg_cuenta_origen"
                                    onchange="segMostrarSaldoCuenta()">
                                <option value="">-- Efectivo / Sin cuenta --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cta)
                                            <option value="{{ $cta->id }}"
                                                    data-moneda="{{ $cta->moneda }}"
                                                    data-saldo="{{ $cta->saldo_actual }}">
                                                {{ $cta->nombre_cuenta }}@if($cta->banco) — {{ $cta->banco->nombre }}@endif @if($cta->numero_cuenta) {{ $cta->numero_cuenta }} @endif[{{ $cta->moneda }}] — Saldo: {{ number_format($cta->saldo_actual, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="form-text" id="seg_saldo_cuenta_info" style="display:none"></div>
                            <div class="alert alert-danger py-1 px-2 mt-1 small mb-0"
                                 id="seg_aviso_saldo_insuficiente" style="display:none"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cuenta Destino (Receptor)</label>
                            <select class="form-select" name="cuenta_destino_id" id="seg_cuenta_destino">
                                <option value="">-- Efectivo / Sin cuenta --</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500" placeholder="Notas del pago..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Voucher / Comprobante</label>
                            <input type="file" class="form-control" name="voucher" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark"><i class="bi bi-save"></i> Registrar Pago</button>
                </div>
            </form>
            {{-- El historial vive en Acciones → Ver historial de pagos, no se duplica aquí --}}
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR PAGO CAMIÓN ===== --}}
{{-- Se abre sobre el historial: Bootstrap no eleva el z-index del segundo modal --}}
<style>
    #modalEditarPago { z-index: 1060; }
    .modal-backdrop.editar-pago-cam-backdrop { z-index: 1055; }
</style>
<div class="modal fade" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarPago" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_pago" id="edit_tipo_pago" required>
                                <option value="adelanto">Adelanto</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_pago" id="edit_fecha_pago" required>
                        </div>
                        {{-- El pago va en la moneda del flete: se fija sola y no se elige.
                             El selector queda oculto (no eliminado) porque el form debe enviarlo. --}}
                        <div class="col-md-4 d-none">
                            <label class="form-label fw-semibold">Moneda <span class="text-danger">*</span></label>
                            <select class="form-select" name="moneda_pago" id="edit_moneda_pago" required onchange="editToggleTc(this.value)">
                                @php
                                    $flags = ['BOB' => '🇧🇴', 'USD' => '🇺🇸', 'BRL' => '🇧🇷', 'ARS' => '🇦🇷', 'PEN' => '🇵🇪', 'EUR' => '🇪🇺', 'CLP' => '🇨🇱', 'PYG' => '🇵🇾', 'COP' => '🇨🇴', 'UYU' => '🇺🇾'];
                                @endphp
                                @foreach($monedas as $moneda)
                                    <option value="{{ $moneda->valor }}">{{ $flags[$moneda->valor] ?? '' }} {{ $moneda->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold" id="edit_monto_moneda">BOB</span>
                                <input type="number" class="form-control" name="monto" id="edit_monto"
                                       step="0.01" min="0.01" required oninput="editCalcEquiv()">
                            </div>
                            <div class="form-text">Se paga en la moneda del flete del contrato.</div>
                        </div>
                        <div class="col-12" id="edit_sec_tc">
                            <label class="form-label fw-semibold">Tipo de Cambio a BOB <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="tipo_cambio" id="edit_tipo_cambio"
                                   step="0.01" min="0.01" value="1" oninput="editCalcEquiv()">
                            {{-- Equivalente en bolivianos: solo cuando el flete no es en BOB --}}
                            <div id="edit_sec_equiv" style="display:none;" class="mt-2">
                                <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2" style="background:#e8f4fd; border:1px solid #b8d9f5;">
                                    <i class="bi bi-arrow-left-right text-primary"></i>
                                    <span class="text-muted small">Equivalente en bolivianos:</span>
                                    <strong class="text-primary fs-6" id="edit_lbl_equiv">—</strong>
                                    <span class="text-muted small">BOB</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="edit_metodo_pago" required
                                    onchange="editToggleCodigo(this.value)">
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código de seguimiento</label>
                            {{-- Solo editable en transferencia: en QR lo genera el sistema --}}
                            <input type="text" class="form-control" name="codigo_seguimiento" id="edit_codigo" maxlength="100">
                            <div class="form-text d-none" id="edit_codigo_ayuda">
                                Generado por el sistema para pagos por QR.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Voucher / Comprobante</label>
                            <div class="mb-1" id="edit_voucher_actual" style="display:none;">
                                <a href="#" target="_blank" id="edit_voucher_link" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-file-earmark-check me-1"></i>Ver voucher actual
                                </a>
                            </div>
                            <input type="file" class="form-control" name="voucher" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text">Subir un archivo nuevo reemplaza al anterior.</div>
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

{{-- ===== MODAL ASIGNAR FLETE ===== --}}
<div class="modal fade" id="modalFlete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title"><i class="bi bi-tag"></i> Asignar Flete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formFlete" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 py-2">
                        <small class="text-muted">Camión / Contrato:</small><br>
                        <strong id="flete_label"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Monto del flete <span class="text-danger">(*)</span></label>
                            <div class="input-group">
                                <select class="form-select flex-grow-0" style="width:100px;" name="moneda_flete" id="flete_moneda">
                                    @foreach($monedas as $moneda)
                                        <option value="{{ $moneda->valor }}">{{ $flags[$moneda->valor] ?? '' }} {{ $moneda->valor }}</option>
                                    @endforeach
                                </select>
                                <input type="text" inputmode="numeric" class="form-control"
                                    id="flete_monto_display" required placeholder="0,00" autocomplete="off">
                                <input type="hidden" name="monto_acordado" id="flete_monto_hidden">
                            </div>
                            <small class="text-muted">Monto pactado con el transportista.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-dark"><i class="bi bi-save"></i> Guardar Flete</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL REGISTRAR LLEGADA ===== --}}
<div class="modal fade" id="modalLlegada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-geo-alt"></i> Registrar Llegada</h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button"
                            class="btn btn-sm btn-iniciar-tour text-white rounded-circle d-flex align-items-center justify-content-center p-0"
                            style="width:28px;height:28px;background:#146c43"
                            title="Ayuda"
                            aria-label="Ayuda"
                            data-tour-modal="#modalLlegada"
                            data-steps='[
                                {"intro":"📍 Aquí registras qué pasó cuando el camión <b>llegó</b> a su destino. Es el paso clave del seguimiento. Te explico los campos."},
                                {"element":"#seg_inp_peso_llegada_display","intro":"⚖️ <b>Peso al llegar</b>: las toneladas reales pesadas al llegar. Puede diferir del peso de salida (merma).","position":"bottom"},
                                {"element":"#inp_fecha_llegada","intro":"📅 <b>Fecha de llegada</b>. No puede ser anterior a la fecha de salida.","position":"bottom"},
                                {"element":"#seg_accion_entregado","intro":"✅ <b>Entregado al cliente</b>: la carga llegó a su destino final. Pedirá el cliente, empresa y precio de venta.","position":"right"},
                                {"element":"#seg_accion_parcial","intro":"🥧 <b>Div. Carga</b>: entregas una parte a un cliente y el resto continúa en otro camión. Se crean 2 tramos automáticamente.","position":"right"},
                                {"element":"#seg_accion_transbordo","intro":"🔄 <b>Transbordo</b>: la carga cambia de camión y continúa (típico en frontera o cambio de unidad).","position":"right"},
                                {"element":"#seg_chk_descuento","intro":"➖ Opcional: aplica un <b>descuento</b> al pago del camionero (ej. por chatarra en mal estado o faltante).","position":"top"},
                                {"element":"#btn_confirmar_llegada_seg","intro":"💾 El botón <b>Confirmar</b> se activa cuando completas todos los campos obligatorios según la acción elegida.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form method="POST" id="formLlegada" action="" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="origen" value="seguimiento">
                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                    @if($errors->llegada->any())
                    <div class="alert alert-danger py-2 mb-3" id="errores_llegada_backend">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->llegada->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="alert alert-primary border-0 mb-2 py-2 text-center" id="seg_llegada_resumen_contrato" style="display:none;">
                        {{-- Fila 1: identificación del contrato --}}
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center">
                            <div>
                                <small class="text-primary-emphasis opacity-75">Contrato</small><br>
                                <span class="fw-bold" id="seg_llegada_numero_contrato">—</span>
                            </div>
                            <div class="vr d-none d-sm-block"></div>
                            <div>
                                <small class="text-primary-emphasis opacity-75">Vigencia</small><br>
                                <span class="fw-bold" id="seg_llegada_fechas">—</span>
                            </div>
                        </div>
                        <hr class="my-2 opacity-25">
                        {{-- Fila 2: toneladas --}}
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center">
                            <div>
                                <small class="text-primary-emphasis opacity-75">Estipuladas</small><br>
                                <span class="fw-bold" id="seg_llegada_tn_pactadas">—</span>
                            </div>
                            <div class="vr d-none d-sm-block"></div>
                            <div>
                                <small class="text-success opacity-75">Entregadas</small><br>
                                <span class="fw-bold text-success" id="seg_llegada_tn_entregadas">—</span>
                            </div>
                            <div class="vr d-none d-sm-block"></div>
                            <div>
                                <small class="text-info opacity-75">En ruta</small><br>
                                <span class="fw-bold text-info" id="seg_llegada_tn_en_ruta">—</span>
                            </div>
                            <div class="vr d-none d-sm-block"></div>
                            <div>
                                <small class="text-secondary opacity-75">Pendientes</small><br>
                                <span class="fw-bold text-secondary" id="seg_llegada_tn_pendientes">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border mb-3 py-2">
                        <small class="text-muted">Tramo:</small><br>
                        <strong id="llegada_tramo_info"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Peso al llegar (t) <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" class="form-control"
                                id="seg_inp_peso_llegada_display" required placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="peso_llegada" id="seg_inp_peso_llegada">
                            <small class="text-muted">Carga estipulada en el origen: <strong id="llegada_peso_max"></strong> t</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de llegada <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_llegada" id="inp_fecha_llegada" required>
                            <small class="text-muted">No puede ser anterior a la fecha de salida.</small>
                        </div>

                        {{-- ¿Qué ocurrió al llegar? --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">¿Qué ocurrió al llegar? <span class="text-danger">(*)</span></label>
                            <div id="seg_aviso_peso_requerido" class="text-muted small mb-2">
                                <i class="bi bi-lock text-warning"></i> Ingresa primero el peso al llegar para habilitar estas opciones.
                            </div>
                            <div class="d-flex flex-column gap-2 mt-1">

                                {{-- Opción: Entregado al cliente --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="entregado" id="seg_accion_entregado" required disabled
                                            onchange="segAccionLlegadaCambiada('entregado')">
                                        <label class="form-check-label" for="seg_accion_entregado">
                                            <i class="bi bi-check-circle text-success"></i>
                                            <strong>Entregado al cliente</strong>
                                            <small class="d-block text-muted">La carga llegó a su destino final.</small>
                                        </label>
                                    </div>
                                    {{-- Cliente receptor --}}
                                    <div class="d-none mt-3" id="seg_sec_cliente">
                                        <label class="form-label fw-semibold">Cliente que recibe la carga <span class="text-danger">(*)</span></label>
                                        <select class="form-select" name="cliente_id" id="seg_sel_cliente">
                                            <option value="">-- Seleccione cliente y dirección --</option>
                                            @foreach($clientes as $cli)
                                                @if($cli->contacts->isEmpty())
                                                    <option value="{{ $cli->id }}" data-direccion="">
                                                        {{ $cli->nombre }} — Sin dirección registrada
                                                    </option>
                                                @else
                                                    @foreach($cli->contacts as $dir)
                                                        <option value="{{ $cli->id }}" data-direccion="{{ $dir->valor }}">
                                                            {{ $cli->nombre }} — {{ $dir->valor }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="direccion_entrega" id="seg_inp_direccion_entrega">
                                    </div>
                                    {{-- Tipo de chatarra entregada --}}
                                    <div class="d-none mt-3" id="seg_sec_tipo_chatarra">
                                        <label class="form-label fw-semibold">Tipo de material entregado <span class="text-danger">(*)</span></label>
                                        <select class="form-select" name="tipo_chatarra" id="seg_sel_tipo_chatarra">
                                            <option value="">-- Seleccione tipo --</option>
                                            <option value="Chatarra">Chatarra</option>
                                            <option value="Fundido">Fundido</option>
                                        </select>
                                    </div>
                                    {{-- Empresa que facturará --}}
                                    <div class="d-none mt-3" id="seg_sec_empresa_factura">
                                        <label class="form-label fw-semibold">Empresa que facturará <span class="text-danger">(*)</span></label>
                                        <select class="form-select" name="empresa_facturadora_id" id="seg_sel_empresa_factura">
                                            <option value="">-- Seleccione empresa --</option>
                                            @foreach($empresas as $emp)
                                                <option value="{{ $emp->id }}" data-ultimo-precio="{{ $emp->precio_referencia ?? '' }}">{{ $emp->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- Precio de venta al cliente --}}
                                    <div class="d-none mt-3" id="seg_sec_precio_venta">
                                        <div class="border rounded-3 p-3 bg-light">
                                            <div class="fw-semibold mb-2"><i class="bi bi-tag text-success"></i> Precio de venta al cliente</div>
                                            <div class="row g-2 align-items-end">
                                                {{-- Las entregas son en Bolivia: se fija BOB.
                                                     El selector queda oculto (no eliminado) por si más adelante
                                                     se vende en otra moneda: basta quitar el d-none. --}}
                                                <div class="col-md-4 d-none">
                                                    <label class="form-label mb-1">Moneda</label>
                                                    <select class="form-select form-select-sm" name="moneda_venta" id="seg_sel_moneda_venta">
                                                        <option value="BOB" selected>BOB</option>
                                                        @foreach($monedas as $moneda)
                                                            @if($moneda->valor !== 'BOB')
                                                                <option value="{{ $moneda->valor }}">{{ $moneda->valor }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Precio por tonelada</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text fw-bold">BOB</span>
                                                        <input type="text" inputmode="numeric" class="form-control"
                                                            id="seg_inp_precio_ton_display" placeholder="0,00" autocomplete="off">
                                                        <input type="hidden" name="precio_por_tonelada" id="seg_inp_precio_ton">
                                                        <span class="input-group-text">/t</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Total estimado</label>
                                                    <div id="seg_lbl_total_venta" class="form-control form-control-sm bg-white text-success fw-semibold">—</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Opción: Div. Carga --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="div_carga" id="seg_accion_parcial" required disabled
                                            onchange="segAccionLlegadaCambiada('div_carga')">
                                        <label class="form-check-label" for="seg_accion_parcial">
                                            <i class="bi bi-pie-chart text-info"></i>
                                            <strong>Div. Carga</strong>
                                            <small class="d-block text-muted">Entrega parte al cliente 1 y el restante continúa en otro camión al cliente 2. Se generan 2 tramos automáticamente.</small>
                                        </label>
                                    </div>
                                    <div class="d-none mt-3" id="seg_sec_parcial">
                                        <div class="border rounded-3 p-3 bg-light">
                                            <div class="alert alert-info py-2 mb-3">
                                                <small><i class="bi bi-info-circle"></i> El campo <strong>"Peso al llegar"</strong> arriba indica el total que llegó. Ingresa abajo cuántas toneladas se entregan ahora a este cliente — el resto continuará en un nuevo tramo.</small>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Cliente que recibe esta parte <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="cliente_id" id="seg_sel_cliente_div">
                                                        <option value="">-- Seleccione cliente y dirección --</option>
                                                        @foreach($clientes as $cli)
                                                            @if($cli->contacts->isEmpty())
                                                                <option value="{{ $cli->id }}" data-direccion="">
                                                                    {{ $cli->nombre }} — Sin dirección registrada
                                                                </option>
                                                            @else
                                                                @foreach($cli->contacts as $dir)
                                                                    <option value="{{ $cli->id }}" data-direccion="{{ $dir->valor }}">
                                                                        {{ $cli->nombre }} — {{ $dir->valor }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="direccion_entrega" id="seg_inp_direccion_entrega_div">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Tipo de material entregado <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="tipo_chatarra" id="seg_sel_tipo_chatarra_div">
                                                        <option value="">-- Seleccione tipo --</option>
                                                        <option value="Chatarra">Chatarra</option>
                                                        <option value="Fundido">Fundido</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Empresa que facturará <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="empresa_facturadora_id" id="seg_sel_empresa_factura_div">
                                                        <option value="">-- Seleccione empresa --</option>
                                                        @foreach($empresas as $emp)
                                                            <option value="{{ $emp->id }}" data-ultimo-precio="{{ $emp->precio_referencia ?? '' }}">{{ $emp->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">TN entregadas a este cliente <span class="text-danger">*</span></label>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        id="seg_inp_tn_parcial_display" placeholder="0,00" autocomplete="off">
                                                    <input type="hidden" name="tn_parcial" id="seg_inp_tn_parcial">
                                                    <small class="text-muted">TN para el nuevo tramo: <strong id="seg_lbl_tn_restante">—</strong></small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Destino del nuevo tramo <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="destino_nuevo_tramo"
                                                        id="seg_inp_destino_nuevo" maxlength="150"
                                                        placeholder="Ciudad / punto de entrega">
                                                </div>
                                                <div class="col-12">
                                                    <div class="border rounded-3 p-3 bg-white">
                                                        <div class="fw-semibold mb-2"><i class="bi bi-tag text-success"></i> Precio de venta al cliente (esta entrega)</div>
                                                        <div class="row g-2 align-items-end">
                                                            {{-- Ver nota en la sección de entrega: BOB fijo, selector oculto --}}
                                                            <div class="col-md-4 d-none">
                                                                <label class="form-label mb-1">Moneda</label>
                                                                <select class="form-select form-select-sm" name="moneda_venta" id="seg_sel_moneda_venta_div">
                                                                    <option value="BOB" selected>BOB</option>
                                                                    @foreach($monedas as $moneda)
                                                                        @if($moneda->valor !== 'BOB')
                                                                            <option value="{{ $moneda->valor }}">{{ $moneda->valor }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label mb-1">Precio por tonelada</label>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text fw-bold">BOB</span>
                                                                    <input type="text" inputmode="numeric" class="form-control"
                                                                        id="seg_inp_precio_ton_div_display" placeholder="0,00" autocomplete="off">
                                                                    <input type="hidden" name="precio_por_tonelada" id="seg_inp_precio_ton_div">
                                                                    <span class="input-group-text">/t</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label mb-1">Total estimado</label>
                                                                <div id="seg_lbl_total_venta_div" class="form-control form-control-sm bg-white text-success fw-semibold">—</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="camion_nuevo_id" id="seg_hidden_camion_nuevo">
                                            <input type="hidden" name="conductor_nuevo_id" id="seg_hidden_conductor_nuevo">
                                            <input type="hidden" name="fecha_salida_nuevo_tramo" id="seg_hidden_fecha_nuevo">
                                            <input type="hidden" name="tipo_tramo_nuevo" id="seg_hidden_tipo_tramo_nuevo">
                                        </div>
                                    </div>
                                </div>

                                {{-- Opción: Transbordando --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="transbordo" id="seg_accion_transbordo" required disabled
                                            onchange="segAccionLlegadaCambiada('transbordo')">
                                        <label class="form-check-label" for="seg_accion_transbordo">
                                            <i class="bi bi-arrow-left-right text-warning"></i>
                                            <strong>Transbordando a otro(s) camión(es)</strong>
                                            <small class="d-block text-muted">La carga continúa en otros camiones.</small>
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="seg_chk_descuento"
                                    onchange="document.getElementById('seg_sec_descuento').classList.toggle('d-none', !this.checked); if(!this.checked) document.getElementById('seg_inp_descuento').value='';">
                                <label class="form-check-label fw-semibold" for="seg_chk_descuento">
                                    <i class="bi bi-percent text-danger"></i> Aplicar descuento al pago del camionero
                                </label>
                            </div>
                            <div id="seg_sec_descuento" class="d-none">
                                <label class="form-label">Porcentaje de descuento (%)</label>
                                <input type="number" step="0.01" min="0" max="60" class="form-control"
                                    name="descuento_porcentaje" id="seg_inp_descuento" placeholder="Ej: 10.00"
                                    oninput="if(parseFloat(this.value)>60) this.value=60;">
                                <small class="text-muted">Máximo 60%.</small>
                            </div>
                        </div>
                        {{-- Lote de entrega --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-collection text-primary"></i>
                                Lote de entrega
                                <span class="text-danger">(*)</span>
                            </label>
                            <div class="input-group">
                                <select class="form-select" name="lote_entrega_id" id="seg_sel_lote_entrega" required>
                                    <option value="">Cargando lotes...</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary d-none" id="seg_btn_nuevo_lote"
                                        title="Crear nuevo lote para este proveedor">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="seg_lote_hint">Se asigna automáticamente al lote de esta semana.</small>
                        </div>

                        {{-- Documento de entrega --}}
                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-arrow-up text-success"></i>
                                Documento de entrega
                                <span class="text-muted small">(recomendado)</span>
                            </label>
                            <input type="file" class="form-control" name="documento_entrega"
                                id="seg_inp_documento_entrega"
                                accept=".pdf,.png,.jpg,.jpeg">
                            <small class="text-muted">PDF, PNG o JPG. Máx. 20 MB. No obligatorio pero recomendable.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones de llegada</label>
                            <textarea class="form-control" name="observaciones_llegada" rows="2" maxlength="500"
                                placeholder="Notas sobre el estado de la carga, incidentes, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2">
                    <div class="text-muted text-center" style="font-size:12px;">
                        <i class="bi bi-lock"></i> El botón se activará cuando todos los campos obligatorios (<span class="text-danger fw-bold">*</span>) estén completos.
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn_confirmar_llegada_seg" class="btn btn-secondary" disabled>
                            <i class="bi bi-check-lg"></i> Confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL ENTREGA MASIVA (desde seguimiento) ===== --}}
<div class="modal fade" id="modalEntregaMasiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-truck"></i> Entrega Masiva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEntregaMasiva" action="{{ route('tramo.entrega_masiva') }}" method="POST" onsubmit="return submitEntregaMasiva(event)">
                @csrf
                <input type="hidden" name="origen" value="seguimiento">
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle"></i> Cliente, empresa, tipo de material y precio se aplican a <strong>todos</strong> los tramos seleccionados. El peso de llegada se ingresa por separado en cada uno.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha de llegada <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_llegada" id="em_inp_fecha_llegada" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cliente que recibe la carga <span class="text-danger">*</span></label>
                            <select class="form-select" name="cliente_id" id="em_sel_cliente" required>
                                <option value="">-- Seleccione cliente y dirección --</option>
                                @foreach($clientes as $cli)
                                    @if($cli->contacts->isEmpty())
                                        <option value="{{ $cli->id }}" data-direccion="">{{ $cli->nombre }} — Sin dirección registrada</option>
                                    @else
                                        @foreach($cli->contacts as $dir)
                                            <option value="{{ $cli->id }}" data-direccion="{{ $dir->valor }}">{{ $cli->nombre }} — {{ $dir->valor }}</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                            <input type="hidden" name="direccion_entrega" id="em_inp_direccion_entrega">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo de material <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_chatarra" id="em_sel_tipo_chatarra" required>
                                <option value="">-- Seleccione tipo --</option>
                                <option value="Chatarra">Chatarra</option>
                                <option value="Fundido">Fundido</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Empresa que facturará <span class="text-danger">*</span></label>
                            <select class="form-select" name="empresa_facturadora_id" id="em_sel_empresa_factura" required>
                                <option value="">-- Seleccione empresa --</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id }}" data-ultimo-precio="{{ $emp->precio_referencia ?? '' }}">{{ $emp->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Precio por tonelada</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">BOB</span>
                                <input type="text" inputmode="numeric" class="form-control" id="em_inp_precio_ton_display" placeholder="0,00" autocomplete="off">
                                <input type="hidden" name="precio_por_tonelada" id="em_inp_precio_ton">
                                <span class="input-group-text">/t</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary py-2 mb-0 mt-3">
                        <i class="bi bi-collection"></i> El lote de entrega se asigna automáticamente (el más reciente de cada proveedor). Si necesitas cambiarlo, puedes editarlo después desde la pestaña Entregados.
                    </div>

                    <hr>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="reasignar_camion" value="1" id="em_chk_reasignar">
                        <label class="form-check-label fw-semibold" for="em_chk_reasignar">
                            <i class="bi bi-arrow-repeat text-warning"></i> ¿Reasignar camión? (otro camión recogió lo acumulado y lo llevó al destino)
                        </label>
                    </div>
                    <div class="d-none row g-3 mb-3" id="em_sec_reasignar">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Camión que recoge <span class="text-danger">*</span></label>
                            <select class="form-select" name="camion_nuevo_id" id="em_sel_camion_nuevo">
                                <option value="">-- Seleccione --</option>
                                @foreach($camionesDisponibles as $cam)
                                    <option value="{{ $cam->id }}" data-uuid="{{ $cam->uuid }}">
                                        {{ $cam->placa }} — {{ $cam->marca->valor ?? '-' }} {{ $cam->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Conductor <span class="text-danger">*</span></label>
                            <select class="form-select" name="conductor_nuevo_id" id="em_sel_conductor_nuevo" disabled>
                                <option value="">— Seleccione un camión primero —</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-semibold"><i class="bi bi-list-check"></i> Tramos seleccionados</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Camión</th>
                                    <th>Ruta</th>
                                    <th>Peso salida</th>
                                    <th style="width:180px;">Peso de llegada <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody id="em_tbody_tramos"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="em_btn_confirmar" class="btn btn-success" disabled>
                        <i class="bi bi-check-lg"></i> Confirmar entrega masiva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR LOTE DE ENTREGA (desde seguimiento) ===== --}}
<div class="modal fade" id="modalEditarLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title"><i class="bi bi-collection"></i> Editar Lote de Entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarLote" method="POST">
                @csrf
                <input type="hidden" name="origen" value="seguimiento">
                <div class="modal-body">
                    <label class="form-label fw-semibold">Lote de entrega <span class="text-danger">*</span></label>
                    <select class="form-select" name="lote_entrega_id" id="el_sel_lote_entrega" required>
                        <option value="">Cargando lotes...</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info"><i class="bi bi-check-lg"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL TRANSBORDO (desde seguimiento) ===== --}}
<div class="modal fade" id="modalTransbordoSeg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title"><i class="bi bi-arrow-down-right"></i> Registrar Transbordo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tramo.store') }}">
                @csrf
                {{-- Token de idempotencia: sin él, store() rechaza el transbordo como duplicado --}}
                <input type="hidden" name="_idempotency_token" value="{{ $tokenTransbordo ?? '' }}">
                <input type="hidden" name="origen" value="seguimiento">
                <input type="hidden" name="contrato_camion_id" id="seg_tsb_cc_id">
                <input type="hidden" name="tramo_padre_id"     id="seg_tsb_padre_id">
                <input type="hidden" name="tipo_tramo"         value="Nacional">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        Transbordo desde: <strong id="seg_tsb_info_padre"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Camión <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="camion_id" id="seg_tsb_camion_id" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($camionesDisponibles as $cam)
                                    <option value="{{ $cam->id }}" data-uuid="{{ $cam->uuid }}">
                                        {{ $cam->placa }} — {{ $cam->marca->valor ?? '-' }} {{ $cam->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conductor <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="conductor_id" id="seg_tsb_conductor_id" disabled required>
                                <option value="">— Seleccione un camión primero —</option>
                            </select>
                            <div id="seg_tsb_sin_conductor_aviso" class="alert alert-warning py-1 px-2 mt-1 mb-0 d-none" style="font-size:13px;">
                                <i class="bi bi-exclamation-triangle"></i> Este camión no tiene conductores asignados. Asigne un conductor antes de registrar el transbordo.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destino <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="destino" id="seg_tsb_destino" required maxlength="150" placeholder="Ej: LA PAZ"
                                style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase(); validarFormTransbordoSeg();">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Peso que lleva este camión (t) <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.001" min="0.001" class="form-control"
                                name="peso_salida" id="seg_tsb_peso_salida" required placeholder="Toneladas que carga este camión">
                            <small class="text-muted">Disponible para transbordo: <strong id="seg_tsb_peso_disponible"></strong> t</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Salida <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_salida" id="seg_tsb_fecha_salida" required>
                            <small class="text-muted">No puede ser anterior a la llegada del camión anterior.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2">
                    <div class="text-muted text-center" style="font-size:12px;">
                        <i class="bi bi-lock"></i> El botón se activará cuando todos los campos obligatorios (<span class="text-danger fw-bold">*</span>) estén completos.
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn_registrar_transbordo_seg" class="btn btn-secondary" disabled>
                            <i class="bi bi-save"></i> Registrar Transbordo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL HISTORIAL DE PAGOS DEL CAMIÓN ===== --}}
<div class="modal fade" id="modalHistorialPagos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Historial de Pagos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="hist_body">
                <div class="text-center py-5 text-muted" id="hist_loading">
                    <div class="spinner-border spinner-border-sm me-2"></div> Cargando...
                </div>
                <div id="hist_contenido" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Mini-modal: crear lote internacional desde seguimiento --}}
<div class="modal fade" id="seg_modalNuevoLote" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-globe me-1"></i> Nuevo Lote Internacional</h5>
                <button type="button" class="btn-close" id="seg_btn_cerrar_nuevo_lote"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha inicio <span class="text-danger">(*)</span></label>
                        <input type="date" id="seg_nl_fecha_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha fin <span class="text-danger">(*)</span></label>
                        <input type="date" id="seg_nl_fecha_fin" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea id="seg_nl_observaciones" class="form-control" rows="2" maxlength="500" placeholder="Notas opcionales..."></textarea>
                    </div>
                </div>
                <div id="seg_nl_error" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="seg_btn_cancelar_nuevo_lote">Cancelar</button>
                <button type="button" class="btn btn-primary" id="seg_btn_guardar_lote">
                    <i class="bi bi-check-lg"></i> Crear Lote
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
// Permisos para las acciones del historial de pagos
const segCanEditPago   = {{ auth()->user()->can('pagos_camiones.edit') ? 'true' : 'false' }};
const segCanDeletePago = {{ auth()->user()->can('pagos_camiones.destroy') ? 'true' : 'false' }};

function _fmtS(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}
function _fmtS4(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 4, maximumFractionDigits: 4 }).format(parseFloat(n) || 0);
}

// Estas 4 tablas ya tienen su propio filtro (aplicarFiltrosSeg) manipulando
// el DOM directamente. DataTables no aportaba más que paginación y buscador,
// y su envoltorio le rompía el sticky de la cabecera al hacer scroll: se
// quitó para que se comporten como el resto de tablas del sistema.

// ---- Pago desde seguimiento ----
let segReceptorActual = {};
let _segMonedaPendiente = null;
// Moneda del flete del contrato abierto en el historial: la usa el modal de editar
let _segMonedaFlete = null;

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('modalPagoSeg').addEventListener('shown.bs.modal', function () {
        if (_segMonedaPendiente) {
            segToggleTC(_segMonedaPendiente);
            _segMonedaPendiente = null;
        }
    });
});

function abrirModalPagoSeg(ccId, label, saldo, moneda, conductorId, conductorNombre, propietarioId, propietarioNombre) {
    document.getElementById('seg_pago_cc_id').value              = ccId;
    document.getElementById('seg_pago_camion_label').textContent = label;
    document.getElementById('seg_pago_saldo_label').textContent     = _fmtS(saldo) + ' ' + (moneda || 'BOB');
    document.getElementById('seg_pago_saldo_lbl_texto').textContent = 'Saldo pendiente del flete:';

    // El conductor llega como parámetro, no hace falta esperar al detalle
    document.getElementById('seg_pago_conductor_label').textContent = conductorNombre || '—';

    // Se llenan al cargar el detalle: limpiar para no mostrar los del camión anterior
    document.getElementById('seg_pago_contrato_label').textContent  = '—';
    document.getElementById('seg_pago_proveedor_label').textContent = '—';
    document.getElementById('seg_pago_tipo_camion').textContent     = '';
    document.getElementById('seg_pago_ruta_label').textContent      = '—';

    segReceptorActual = {
        conductor_id:   conductorId    || null,
        conductor:      conductorNombre || '',
        propietario_id: propietarioId  || null,
        propietario:    propietarioNombre || '',
    };
    document.getElementById('seg_opt_conductor').disabled   = !conductorId;
    document.getElementById('seg_opt_propietario').disabled = !propietarioId;
    document.getElementById('seg_receptor_type').value      = '';
    document.getElementById('seg_sec_receptor_nombre').style.display = 'none';
    document.getElementById('seg_cuenta_destino').innerHTML = '<option value="">-- Efectivo / Sin cuenta --</option>';

    // Resetear monto y TC
    document.getElementById('seg_inp_monto_display').value = '';
    document.getElementById('seg_inp_monto').value         = '';
    document.getElementById('seg_inp_tc_display').value    = '';
    document.getElementById('seg_inp_tc').value            = '';

    // Resetear cuenta origen y su saldo
    document.getElementById('seg_cuenta_origen').value = '';
    segMostrarSaldoCuenta();

    // Guardar moneda para aplicarla cuando el modal esté visible (Bootstrap no permite cambiar el DOM antes)
    _segMonedaPendiente = moneda || 'BOB';

    segCargarContexto(ccId);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPagoSeg')).show();
}

// Muestra el saldo disponible de la cuenta origen y avisa si no alcanza para el monto
function segMostrarSaldoCuenta() {
    const sel   = document.getElementById('seg_cuenta_origen');
    const info  = document.getElementById('seg_saldo_cuenta_info');
    const aviso = document.getElementById('seg_aviso_saldo_insuficiente');
    if (!sel) return;

    const opt = sel.options[sel.selectedIndex];
    const saldo  = opt ? parseFloat(opt.dataset.saldo) : NaN;
    const moneda = opt ? (opt.dataset.moneda || '') : '';

    if (!sel.value || isNaN(saldo)) {
        info.style.display  = 'none';
        aviso.style.display = 'none';
        return;
    }

    info.style.display = '';
    info.innerHTML = `<i class="bi bi-wallet2 me-1"></i>Disponible en la cuenta: <strong>${moneda} ${_fmtS(saldo)}</strong>`;

    // El saldo de la cuenta está en su propia moneda: solo se compara si coincide
    // con la del pago, para no contrastar importes de monedas distintas.
    const monto       = parseFloat(document.getElementById('seg_inp_monto').value) || 0;
    const monedaPago  = document.getElementById('seg_moneda_pago')?.value || '';
    const comparable  = monedaPago && moneda && monedaPago === moneda;

    if (comparable && monto > saldo) {
        aviso.style.display = '';
        aviso.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>Saldo insuficiente: disponible ${moneda} ${_fmtS(saldo)}, se intenta pagar ${moneda} ${_fmtS(monto)}.`;
    } else {
        aviso.style.display = 'none';
    }
}

// Carga el contexto de la cabecera del modal (contrato, proveedor, tipo de camión).
// El historial de pagos no se muestra aquí: está en Acciones → Ver historial de pagos.
function segCargarContexto(ccId) {
    fetch(`${url_global}/api/pagos/camiones/${ccId}/detalle`)
        .then(r => r.json())
        .then(d => {
            document.getElementById('seg_pago_contrato_label').textContent  = d.contrato || '—';
            document.getElementById('seg_pago_proveedor_label').textContent = d.proveedor || '—';
            document.getElementById('seg_pago_tipo_camion').textContent     = d.tipo_camion ? '· ' + d.tipo_camion : '';
            document.getElementById('seg_pago_ruta_label').textContent      = d.ruta || '—';

            // Respaldo: no todas las vías de apertura traen el nombre del conductor
            const lblConductor = document.getElementById('seg_pago_conductor_label');
            if (lblConductor.textContent === '—' && d.conductor) {
                lblConductor.textContent = d.conductor;
            }
        })
        .catch(() => {});
}

function segToggleTC(moneda) {
    const secTC      = document.getElementById('seg_sec_tc');
    const secEquiv   = document.getElementById('seg_sec_equiv');
    const inpTCDisp  = document.getElementById('seg_inp_tc_display');
    const inpTC      = document.getElementById('seg_inp_tc');
    const inpBob     = document.getElementById('seg_inp_tc_bob');

    document.getElementById('seg_moneda_pago').value     = moneda;
    document.getElementById('seg_lbl_moneda').textContent = moneda;

    if (moneda === 'BOB') {
        secTC.style.display    = 'none';
        secEquiv.style.display = 'none';
        inpTCDisp.disabled = true;
        inpTCDisp.value    = '';
        inpTC.value        = '';
        inpBob.value       = '1';
    } else {
        secTC.style.display = 'block';
        inpTCDisp.disabled  = false;
        document.getElementById('seg_lbl_tc_moneda').textContent = moneda;
        segCalcEquiv();
    }
}

function segCalcEquiv() {
    const moneda = document.getElementById('seg_moneda_pago').value;
    if (moneda === 'BOB') return;
    const monto = parseFloat(document.getElementById('seg_inp_monto').value) || 0;
    const tc    = parseFloat(document.getElementById('seg_inp_tc').value) || 0;
    const secEquiv = document.getElementById('seg_sec_equiv');
    if (monto > 0 && tc > 0) {
        document.getElementById('seg_lbl_equiv').textContent = _fmtS(monto * tc);
        secEquiv.style.display = 'block';
    } else {
        secEquiv.style.display = 'none';
    }
}

// ── Cajero monto y tipo_cambio en Registrar Pago (seguimiento) ──
(function() {
    function _txt2num(v) { return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0; }
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
            this.value   = n > 0 ? (decimals === 4 ? _fmtS4(n) : _fmtS(n)) : '';
            hidden.value = n > 0 ? n : '';
            if (onChangeCb) onChangeCb();
        });
    }
    // El aviso de saldo insuficiente debe reevaluarse al cambiar el monto.
    // Va aquí y no dentro de segCalcEquiv porque esa función corta antes si la moneda es BOB.
    const alCambiarMonto = () => { segCalcEquiv(); segMostrarSaldoCuenta(); };
    _initCajero('seg_inp_monto_display', 'seg_inp_monto', 2, alCambiarMonto);
    _initCajero('seg_inp_tc_display',    'seg_inp_tc',    2, segCalcEquiv);
})();

function segToggleCodigo(metodo) {
    document.getElementById('seg_sec_codigo').style.display = metodo === 'transferencia' ? 'block' : 'none';
}

function segCambiarReceptor(tipo) {
    const sec = document.getElementById('seg_sec_receptor_nombre');
    if (!tipo) { sec.style.display = 'none'; return; }
    const nombre = tipo === 'conductor' ? segReceptorActual.conductor : segReceptorActual.propietario;
    const id     = tipo === 'conductor' ? segReceptorActual.conductor_id : segReceptorActual.propietario_id;
    document.getElementById('seg_receptor_display').value = nombre;
    document.getElementById('seg_receptor_id').value      = id || '';
    sec.style.display = 'block';

    // Cargar cuentas del receptor vía AJAX (misma ruta que pagos/camiones)
    const sel = document.getElementById('seg_cuenta_destino');
    sel.innerHTML = '<option value="">-- Cargando... --</option>';
    if (!id) { sel.innerHTML = '<option value="">-- Efectivo / Sin cuenta --</option>'; return; }
    fetch(`${url_global}/api/pagos/cuentas-receptor?receptor_id=${id}`)
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
// ---- Fin pago desde seguimiento ----

const _resumenContratosPorTipo      = @json($resumenContratosPorTipo);
const _resumenContratosPorProveedor = @json($resumenContratosPorProveedor);
const _proveedoresSegNombre         = @json($proveedores->pluck('nombre', 'id'));

function _lineaResumenContratos(r, etiqueta) {
    return `📄 ${r.total} contrato(s) ${etiqueta}: ${r.con_envios} con envíos asignados, <strong class="text-decoration-underline">${r.sin_envios} sin ningún envío</strong>.`;
}

function actualizarResumenContratosTipo(tipoTramo, proveedorId) {
    const el = document.getElementById('resumen_contratos_tipo');
    const lineas = [];

    const rTipo = tipoTramo && _resumenContratosPorTipo[tipoTramo];
    if (rTipo) lineas.push(_lineaResumenContratos(rTipo, `${tipoTramo.toLowerCase()}(es)`));

    if (proveedorId) {
        const rProv = _resumenContratosPorProveedor[proveedorId] || { total: 0, con_envios: 0, sin_envios: 0 };
        lineas.push(_lineaResumenContratos(rProv, `del proveedor ${_proveedoresSegNombre[proveedorId]}`));
    }

    if (!lineas.length) { el.classList.add('d-none'); return; }
    el.innerHTML = lineas.join('<br>');
    el.classList.remove('d-none');
}

function aplicarFiltrosSeg() {
    const proveedorId  = (document.getElementById('filtro_proveedor_seg')?.value    || '');
    const tipoTramo    = (document.getElementById('filtro_tipo_tramo_seg')?.value   || '');
    const fleteEstado  = (document.getElementById('filtro_flete_estado_seg')?.value || '');
    const hayFiltro    = !!(proveedorId || tipoTramo || fleteEstado);
    actualizarResumenContratosTipo(tipoTramo, proveedorId);
    const grupos = [
        { tabla: 'tabla_en_ruta',        tarjeta: 'contador_tarjeta_en_ruta',        badge: 'badge_tab_en_ruta' },
        { tabla: 'tabla_transbordando',  tarjeta: 'contador_tarjeta_transbordando',  badge: 'badge_tab_transbordando' },
        { tabla: 'tabla_transbordado',   tarjeta: 'contador_tarjeta_transbordado',   badge: 'badge_tab_transbordado' },
    ];
    grupos.forEach(function(g) {
        const tabla = document.getElementById(g.tabla);
        let visibles = 0;
        if (tabla) {
            tabla.querySelectorAll('tbody tr').forEach(function(fila) {
                const okProv  = !proveedorId || fila.dataset.proveedorId == proveedorId;
                const okTipo  = !tipoTramo   || fila.dataset.tipoTramo  === tipoTramo;
                const okFlete = !fleteEstado || fila.dataset.fleteEstado === fleteEstado;
                const visible = okProv && okTipo && okFlete;
                fila.style.display = visible ? '' : 'none';
                if (visible) visibles++;
            });
        }
        const tarjeta = document.getElementById(g.tarjeta);
        const badge   = document.getElementById(g.badge);
        // Sin filtro, se muestra el total real (data-total) por si la tabla no trae
        // todas las filas cargadas (p.ej. si en el futuro se pagina como "Entregados").
        const valor = hayFiltro ? visibles : (badge?.dataset.total ?? visibles);
        if (tarjeta) tarjeta.textContent = valor;
        if (badge)   badge.textContent   = valor;
    });

    // "Entregados" está paginado en el servidor: con filtro se recarga la tabla
    // en vez de ocultar filas de la página cargada, para buscar en todos los registros.
    // proveedor_id/tipo_tramo se resuelven en SQL (el total sigue siendo exacto);
    // solo flete_estado se calcula en PHP tras traer todo, así que ahí sí se cuentan
    // las filas visibles en vez de usar el total.
    cargarPaneEntregados(hayFiltro ? `${url_global}/seguimiento-cargas?proveedor_id=${proveedorId}&tipo_tramo=${tipoTramo}&flete_estado=${fleteEstado}` : null, !!fleteEstado);
}

function cargarPaneEntregados(url, esFiltro) {
    fetch(url || (url_global + '/seguimiento-cargas'))
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevo = doc.getElementById('pane-entregados-contenido');
            if (!nuevo) return;
            const contenedor = document.getElementById('pane-entregados-contenido');
            contenedor.innerHTML = nuevo.innerHTML;
            contenedor.dataset.totalEntregados = nuevo.dataset.totalEntregados;
            // Sin filtro (incluida la paginación), la tarjeta/badge muestran el total real
            // que trae el propio fragmento recargado; con filtro activo, muestran las
            // filas encontradas (ya no hay paginación en ese caso).
            const totalReal = contenedor.dataset.totalEntregados ?? 0;
            const valor = esFiltro ? document.getElementById('tabla_entregados')?.querySelectorAll('tbody tr').length ?? 0 : totalReal;
            document.getElementById('badge_tab_entregado').dataset.total = totalReal;
            document.getElementById('contador_tarjeta_entregado').textContent = valor;
            document.getElementById('badge_tab_entregado').textContent = valor;
        });
}

// Pagina "Entregados" por AJAX para no recargar toda la vista ni perder la pestaña activa
document.getElementById('pane-entregados')?.addEventListener('click', function(e) {
    const link = e.target.closest('.pagination a[href]');
    if (!link) return;
    e.preventDefault();
    cargarPaneEntregados(link.href);
});

function limpiarFiltrosSeg() {
    const sel1 = document.getElementById('filtro_proveedor_seg');
    const sel2 = document.getElementById('filtro_tipo_tramo_seg');
    const sel3 = document.getElementById('filtro_flete_estado_seg');
    if (sel1) sel1.value = '';
    if (sel2) sel2.value = '';
    if (sel3) sel3.value = '';
    aplicarFiltrosSeg();
}

// ── Entrega masiva ───────────────────────────────────────────────────────
function actualizarSeleccionEntregaMasiva() {
    const marcados = Array.from(document.querySelectorAll('.chk_entrega_masiva:checked'));
    const btn      = document.getElementById('btn_entrega_masiva');
    const lbl      = document.getElementById('lbl_entrega_masiva_count');
    lbl.textContent = marcados.length;
    btn.disabled = marcados.length < 2;
}

function toggleTodosEntregaMasiva(chkTodos) {
    document.querySelectorAll('#tabla_en_ruta .chk_entrega_masiva').forEach(function (chk) {
        // Solo afecta filas visibles (respeta los filtros de proveedor/tipo/flete activos)
        const fila = chk.closest('tr');
        if (fila.style.display === 'none') return;
        chk.checked = chkTodos.checked;
    });
    actualizarSeleccionEntregaMasiva();
}

function abrirModalEntregaMasiva() {
    const marcados = Array.from(document.querySelectorAll('.chk_entrega_masiva:checked'));
    if (marcados.length < 2) return;

    document.getElementById('formEntregaMasiva').reset();
    document.getElementById('em_inp_precio_ton').value = '';
    document.getElementById('em_inp_direccion_entrega').value = '';
    document.getElementById('em_sec_reasignar').classList.add('d-none');
    document.getElementById('em_sel_conductor_nuevo').innerHTML = '<option value="">— Seleccione un camión primero —</option>';
    document.getElementById('em_sel_conductor_nuevo').disabled = true;
    document.getElementById('em_btn_confirmar').disabled = true;

    const hoy = new Date().toISOString().slice(0, 10);
    document.getElementById('em_inp_fecha_llegada').value = hoy;

    const tbody = document.getElementById('em_tbody_tramos');
    tbody.innerHTML = '';
    marcados.forEach(function (chk) {
        const tr = document.createElement('tr');
        tr.dataset.uuid = chk.dataset.uuid;
        tr.innerHTML = `
            <td>${chk.dataset.placa}</td>
            <td>${chk.dataset.ruta}</td>
            <td>${parseFloat(chk.dataset.pesoSalida).toLocaleString('es-BO', {minimumFractionDigits:2, maximumFractionDigits:2})} t</td>
            <td>
                <input type="text" inputmode="numeric" class="form-control form-control-sm em_inp_peso_llegada_display"
                    placeholder="0,00" autocomplete="off">
                <input type="hidden" class="em_inp_peso_llegada_hidden" value="">
            </td>`;
        tbody.appendChild(tr);
    });
    _emInicializarCajerosPeso();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEntregaMasiva')).show();
}

function _emInicializarCajerosPeso() {
    const fmt = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function textoANum(txt) { return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0; }

    document.querySelectorAll('.em_inp_peso_llegada_display').forEach(function (disp) {
        const hidd = disp.nextElementSibling;
        disp.addEventListener('input', function () {
            var raw = this.value.replace(/[^0-9,]/g, '');
            var p = raw.split(',');
            if (p.length > 2) raw = p[0] + ',' + p.slice(1).join('');
            if (p[1] !== undefined && p[1].length > 2) raw = p[0] + ',' + p[1].substring(0, 2);
            var partes = raw.split(',');
            var entF   = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var nuevo  = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
            this.value = nuevo;
            hidd.value = nuevo ? textoANum(nuevo) : '';
            validarFormEntregaMasiva();
        });
        disp.addEventListener('blur', function () {
            var n = textoANum(this.value);
            this.value = n > 0 ? fmt.format(n) : '';
            hidd.value = n > 0 ? n : '';
            validarFormEntregaMasiva();
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('em_sel_cliente')?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        document.getElementById('em_inp_direccion_entrega').value = opt?.dataset.direccion || '';
    });

    const fmtEm = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function textoANumEm(txt) { return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0; }
    const dispPrecio = document.getElementById('em_inp_precio_ton_display');
    const hiddPrecio = document.getElementById('em_inp_precio_ton');
    if (dispPrecio) {
        dispPrecio.addEventListener('input', function () {
            var raw = this.value.replace(/[^0-9,]/g, '');
            var p = raw.split(',');
            if (p.length > 2) raw = p[0] + ',' + p.slice(1).join('');
            if (p[1] !== undefined && p[1].length > 2) raw = p[0] + ',' + p[1].substring(0, 2);
            var partes = raw.split(',');
            var entF   = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var nuevo  = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
            this.value = nuevo;
            hiddPrecio.value = nuevo ? textoANumEm(nuevo) : '';
        });
        dispPrecio.addEventListener('blur', function () {
            var n = textoANumEm(this.value);
            this.value = n > 0 ? fmtEm.format(n) : '';
            hiddPrecio.value = n > 0 ? n : '';
        });
    }

    // Precio sugerido según la empresa que facturará
    document.getElementById('em_sel_empresa_factura')?.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        var precio = opt ? parseFloat(opt.dataset.ultimoPrecio) : NaN;
        hiddPrecio.value = precio || '';
        dispPrecio.value = precio ? fmtEm.format(precio) : '';
    });

    document.getElementById('em_chk_reasignar')?.addEventListener('change', function () {
        const sec = document.getElementById('em_sec_reasignar');
        sec.classList.toggle('d-none', !this.checked);
        if (!this.checked) {
            document.getElementById('em_sel_camion_nuevo').value = '';
            document.getElementById('em_sel_conductor_nuevo').innerHTML = '<option value="">— Seleccione un camión primero —</option>';
            document.getElementById('em_sel_conductor_nuevo').disabled = true;
        }
        validarFormEntregaMasiva();
    });

    document.getElementById('em_sel_camion_nuevo')?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        emCargarConductores(opt?.dataset.uuid);
    });

    document.getElementById('modalEntregaMasiva')?.addEventListener('input', validarFormEntregaMasiva);
    document.getElementById('modalEntregaMasiva')?.addEventListener('change', validarFormEntregaMasiva);
});

function emCargarConductores(uuid) {
    const sel = document.getElementById('em_sel_conductor_nuevo');
    sel.innerHTML = '<option value="">— Cargando... —</option>';
    sel.disabled  = true;

    if (!uuid) {
        sel.innerHTML = '<option value="">— Primero seleccione un camión —</option>';
        return;
    }

    fetch('{{ url("api/camion") }}/' + uuid + '/conductores-relacionados', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(conductores => {
        if (conductores.length === 0) {
            sel.innerHTML = '<option value="">— Sin conductores —</option>';
            validarFormEntregaMasiva();
            return;
        }
        sel.innerHTML = '<option value="">— Seleccione conductor —</option>';
        conductores.forEach(c => {
            const op = document.createElement('option');
            op.value       = c.id;
            op.textContent = c.nombre + ' — Lic: ' + (c.licencia || 'S/N') + ' [' + c.tipo + ']';
            sel.appendChild(op);
        });
        sel.disabled = false;
        validarFormEntregaMasiva();
    })
    .catch(() => { sel.innerHTML = '<option value="">— Error al cargar —</option>'; });
}

function validarFormEntregaMasiva() {
    const fecha    = document.getElementById('em_inp_fecha_llegada')?.value;
    const cliente  = document.getElementById('em_sel_cliente')?.value;
    const tipo     = document.getElementById('em_sel_tipo_chatarra')?.value;
    const empresa  = document.getElementById('em_sel_empresa_factura')?.value;
    const pesos    = Array.from(document.querySelectorAll('.em_inp_peso_llegada_hidden'));
    const todosPesos = pesos.length > 0 && pesos.every(p => parseFloat(p.value) > 0);

    const reasignar = document.getElementById('em_chk_reasignar')?.checked;
    const camionNuevo    = document.getElementById('em_sel_camion_nuevo')?.value;
    const conductorNuevo = document.getElementById('em_sel_conductor_nuevo')?.value;
    const okReasignar = !reasignar || (camionNuevo && conductorNuevo);

    const btn = document.getElementById('em_btn_confirmar');
    btn.disabled = !(fecha && cliente && tipo && empresa && todosPesos && okReasignar);
}

function submitEntregaMasiva(e) {
    e.preventDefault();

    document.querySelectorAll('#formEntregaMasiva .em-hidden-tramo').forEach(el => el.remove());

    const filas = document.querySelectorAll('#em_tbody_tramos tr');
    filas.forEach(function (fila, i) {
        const uuid = fila.dataset.uuid;
        const peso = fila.querySelector('.em_inp_peso_llegada_hidden').value;

        const inpUuid = document.createElement('input');
        inpUuid.type = 'hidden';
        inpUuid.className = 'em-hidden-tramo';
        inpUuid.name = `tramos[${i}][uuid]`;
        inpUuid.value = uuid;

        const inpPeso = document.createElement('input');
        inpPeso.type = 'hidden';
        inpPeso.className = 'em-hidden-tramo';
        inpPeso.name = `tramos[${i}][peso_llegada]`;
        inpPeso.value = peso;

        e.target.appendChild(inpUuid);
        e.target.appendChild(inpPeso);
    });

    e.target.submit();
    return false;
}

function abrirModalEditarLote(tramoUuid, proveedorId, loteActualId) {
    document.getElementById('formEditarLote').action = url_global + '/tramo/' + tramoUuid + '/lote';

    const selLote = document.getElementById('el_sel_lote_entrega');
    selLote.innerHTML = '<option value="">Cargando lotes...</option>';
    fetch(url_global + '/lotes-entrega/proveedor/' + proveedorId)
        .then(r => r.json())
        .then(lotes => {
            selLote.innerHTML = '';
            if (lotes.length === 0) {
                selLote.innerHTML = '<option value="">— Sin lotes disponibles —</option>';
                return;
            }
            lotes.forEach(function (l) {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = l.nombre;
                if (loteActualId && l.id === loteActualId) opt.selected = true;
                selLote.appendChild(opt);
            });
        })
        .catch(() => { selLote.innerHTML = '<option value="">— Error al cargar —</option>'; });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarLote')).show();
}

function abrirModalEditarPago(uuid, tipo, monto, moneda, tipoCambio, fecha, metodo, codigo, voucherUrl) {
    document.getElementById('formEditarPago').action = url_global + '/pagos/camiones/' + uuid;
    document.getElementById('edit_tipo_pago').value   = tipo;
    document.getElementById('edit_monto').value       = monto;
    document.getElementById('edit_tipo_cambio').value = tipoCambio;
    document.getElementById('edit_fecha_pago').value  = fecha;
    document.getElementById('edit_metodo_pago').value = metodo;
    document.getElementById('edit_codigo').value      = codigo;

    const voucherActual = document.getElementById('edit_voucher_actual');
    const voucherLink   = document.getElementById('edit_voucher_link');
    if (voucherUrl) {
        voucherLink.href = voucherUrl;
        voucherActual.style.display = 'block';
    } else {
        voucherActual.style.display = 'none';
    }

    // La moneda es la del flete del contrato, no la que traiga el pago
    const monedaFlete = _segMonedaFlete || moneda || 'BOB';
    document.getElementById('edit_moneda_pago').value      = monedaFlete;
    document.getElementById('edit_monto_moneda').textContent = monedaFlete;

    editToggleTc(monedaFlete);
    editToggleCodigo(metodo);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarPago')).show();
}

// El código de seguimiento solo se edita en transferencia; en QR lo genera el sistema
function editToggleCodigo(metodo) {
    const inp   = document.getElementById('edit_codigo');
    const ayuda = document.getElementById('edit_codigo_ayuda');
    const esTransferencia = metodo === 'transferencia';
    // readonly y no disabled: un campo deshabilitado no se envía en el POST
    inp.readOnly = !esTransferencia;
    inp.classList.toggle('bg-light', !esTransferencia);
    ayuda.classList.toggle('d-none', esTransferencia);
}

function editToggleTc(moneda) {
    document.getElementById('edit_sec_tc').style.display = moneda === 'BOB' ? 'none' : 'block';
    if (moneda === 'BOB') document.getElementById('edit_tipo_cambio').value = 1;
    editCalcEquiv();
}

// Muestra monto x tipo de cambio cuando el flete no es en bolivianos
function editCalcEquiv() {
    const moneda = document.getElementById('edit_moneda_pago').value;
    const sec    = document.getElementById('edit_sec_equiv');
    if (!sec) return;

    if (moneda === 'BOB') {
        sec.style.display = 'none';
        return;
    }

    const monto = parseFloat(document.getElementById('edit_monto').value) || 0;
    const tc    = parseFloat(document.getElementById('edit_tipo_cambio').value) || 0;

    if (monto > 0 && tc > 0) {
        document.getElementById('edit_lbl_equiv').textContent = _fmtS(monto * tc);
        sec.style.display = 'block';
    } else {
        sec.style.display = 'none';
    }
}

// Editar se abre desde el historial: marcar su backdrop para que quede encima
// y devolver el scroll al historial cuando este se cierra.
document.addEventListener('DOMContentLoaded', function () {
    const modalEditar = document.getElementById('modalEditarPago');
    if (!modalEditar) return;

    modalEditar.addEventListener('shown.bs.modal', function () {
        const backdrops = document.querySelectorAll('.modal-backdrop:not(.editar-pago-cam-backdrop)');
        if (backdrops.length > 1) {
            backdrops[backdrops.length - 1].classList.add('editar-pago-cam-backdrop');
        }
    });

    modalEditar.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });
});

function abrirModalFlete(ccUuid, label, montoActual, monedaActual) {
    document.getElementById('flete_label').textContent    = label;
    document.getElementById('formFlete').action           = '{{ url("contrato-camion") }}/' + ccUuid + '/flete';
    document.getElementById('flete_moneda').value         = monedaActual || 'BOB';
    document.getElementById('flete_monto_hidden').value   = montoActual || '';
    document.getElementById('flete_monto_display').value  = montoActual
        ? new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(montoActual)
        : '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFlete')).show();
}

// Cajero para monto flete
(function () {
    var fmt = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function textoANum(txt) { return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0; }

    document.addEventListener('DOMContentLoaded', function () {
        var disp = document.getElementById('flete_monto_display');
        var hidd = document.getElementById('flete_monto_hidden');
        if (!disp) return;

        disp.addEventListener('input', function () {
            var raw = this.value.replace(/[^0-9,]/g, '');
            var p = raw.split(',');
            if (p.length > 2) raw = p[0] + ',' + p.slice(1).join('');
            if (p[1] !== undefined && p[1].length > 2) raw = p[0] + ',' + p[1].substring(0, 2);
            var partes = raw.split(',');
            var entF   = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var nuevo  = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
            var diff   = nuevo.length - this.value.length;
            var pos    = (this.selectionStart || 0) + diff;
            this.value = nuevo;
            try { this.setSelectionRange(pos, pos); } catch(_) {}
            hidd.value = nuevo ? textoANum(nuevo) : '';
        });

        disp.addEventListener('blur', function () {
            var n = textoANum(this.value);
            this.value = n > 0 ? fmt.format(n) : '';
            hidd.value = n > 0 ? n : '';
        });
    });
})();

// ── Modal llegada (seguimiento) — patrón cajero ──────────────────────────
(function () {
    var _fmt = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function textoANum(txt) { return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0; }
    function formatear(n)   { return _fmt.format(n); }

    function initCajeroSeg(displayId, hiddenId, onChangeCallback) {
        var disp = document.getElementById(displayId);
        var hidd = document.getElementById(hiddenId);
        if (!disp || !hidd) return;
        disp.addEventListener('input', function () {
            var raw = this.value.replace(/[^0-9,]/g, '');
            var p = raw.split(',');
            if (p.length > 2) raw = p[0] + ',' + p.slice(1).join('');
            if (p[1] !== undefined && p[1].length > 2) raw = p[0] + ',' + p[1].substring(0, 2);
            var partes = raw.split(',');
            var entF   = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var nuevo  = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
            var diff   = nuevo.length - this.value.length;
            var pos    = (this.selectionStart || 0) + diff;
            this.value = nuevo;
            try { this.setSelectionRange(pos, pos); } catch(_) {}
            hidd.value = nuevo ? textoANum(nuevo) : '';
            if (onChangeCallback) onChangeCallback();
        });
        disp.addEventListener('blur', function () {
            var n = textoANum(this.value);
            this.value = n > 0 ? formatear(n) : '';
            hidd.value = n > 0 ? n : '';
            if (onChangeCallback) onChangeCallback();
        });
    }

    function segActualizarRadios() {
        var tiene = textoANum(document.getElementById('seg_inp_peso_llegada_display').value) > 0;
        ['seg_accion_entregado', 'seg_accion_parcial', 'seg_accion_transbordo'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.disabled = !tiene;
            if (!tiene && el.checked) {
                el.checked = false;
                segAccionLlegadaCambiada(null);
            }
        });
        document.getElementById('seg_aviso_peso_requerido').style.display = tiene ? 'none' : '';
    }

    // Prellena el precio por tonelada con el último cobrado a ese cliente
    // (data-ultimo-precio en la opción, calculado en el controller). Se
    // actualiza cada vez que cambia el cliente.
    function segPrecargarUltimoPrecio(opt, displayId, hiddenId) {
        var precio = opt ? parseFloat(opt.dataset.ultimoPrecio) : NaN;
        document.getElementById(hiddenId).value = precio || '';
        document.getElementById(displayId).value = precio ? formatear(precio) : '';
        segCalcTotalVenta();
    }

    function segCalcTotalVenta() {
        // La moneda va en el texto porque su selector está oculto (siempre BOB)
        // Los inputs *_hidden ya son números planos (ej. "3528.72"): usar parseFloat,
        // no textoANum (que asume formato con puntos de miles y rompería el decimal).
        var accion = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
        if (accion === 'entregado') {
            var tn    = parseFloat(document.getElementById('seg_inp_peso_llegada').value) || 0;
            var precio = parseFloat(document.getElementById('seg_inp_precio_ton').value) || 0;
            var lbl    = document.getElementById('seg_lbl_total_venta');
            if (lbl) lbl.textContent = (tn > 0 && precio > 0) ? 'BOB ' + formatear(tn * precio) : '—';
        } else if (accion === 'div_carga') {
            var tn2    = parseFloat(document.getElementById('seg_inp_tn_parcial').value) || 0;
            var precio2= parseFloat(document.getElementById('seg_inp_precio_ton_div').value) || 0;
            var lbl2   = document.getElementById('seg_lbl_total_venta_div');
            var restLbl= document.getElementById('seg_lbl_tn_restante');
            var total  = parseFloat(document.getElementById('seg_inp_peso_llegada').value) || 0;
            var rest   = Math.max(0, total - tn2);
            if (lbl2)   lbl2.textContent  = (tn2 > 0 && precio2 > 0) ? 'BOB ' + formatear(tn2 * precio2) : '—';
            if (restLbl) restLbl.textContent = rest > 0 ? formatear(rest) + ' t' : '—';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCajeroSeg('seg_inp_peso_llegada_display', 'seg_inp_peso_llegada', function () {
            segActualizarRadios();
            segCalcTotalVenta();
            validarFormLlegadaSeg();
        });
        initCajeroSeg('seg_inp_precio_ton_display',     'seg_inp_precio_ton',     segCalcTotalVenta);
        initCajeroSeg('seg_inp_precio_ton_div_display', 'seg_inp_precio_ton_div', segCalcTotalVenta);
        initCajeroSeg('seg_inp_tn_parcial_display',     'seg_inp_tn_parcial',     function () {
            segCalcTotalVenta();
            validarFormLlegadaSeg();
        });

        // Dirección al seleccionar cliente (entregado)
        document.getElementById('seg_sel_cliente').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            document.getElementById('seg_inp_direccion_entrega').value = opt ? (opt.dataset.direccion ?? '') : '';
            validarFormLlegadaSeg();
        });
        // Dirección al seleccionar cliente (div. carga)
        document.getElementById('seg_sel_cliente_div').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            document.getElementById('seg_inp_direccion_entrega_div').value = opt ? (opt.dataset.direccion ?? '') : '';
            validarFormLlegadaSeg();
        });

        // Precio de venta sugerido según la empresa que facturará (entregado)
        document.getElementById('seg_sel_empresa_factura').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            segPrecargarUltimoPrecio(opt, 'seg_inp_precio_ton_display', 'seg_inp_precio_ton');
        });
        // Precio de venta sugerido según la empresa que facturará (div. carga)
        document.getElementById('seg_sel_empresa_factura_div').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            segPrecargarUltimoPrecio(opt, 'seg_inp_precio_ton_div_display', 'seg_inp_precio_ton_div');
        });

        var modal = document.getElementById('modalLlegada');
        if (modal) {
            modal.addEventListener('input',  validarFormLlegadaSeg);
            modal.addEventListener('change', validarFormLlegadaSeg);
        }
    });

    window.segAccionLlegadaCambiada = function (accion) {
        var secCliente      = document.getElementById('seg_sec_cliente');
        var secTipoChatarra = document.getElementById('seg_sec_tipo_chatarra');
        var secEmpresa      = document.getElementById('seg_sec_empresa_factura');
        var secPrecio       = document.getElementById('seg_sec_precio_venta');
        var secParcial      = document.getElementById('seg_sec_parcial');

        secCliente.classList.add('d-none');
        secTipoChatarra.classList.add('d-none');
        secEmpresa.classList.add('d-none');
        secPrecio.classList.add('d-none');
        secParcial.classList.add('d-none');
        document.getElementById('seg_sel_cliente').value          = '';
        document.getElementById('seg_sel_cliente_div').value      = '';
        document.getElementById('seg_sel_tipo_chatarra').value     = '';
        document.getElementById('seg_sel_tipo_chatarra_div').value = '';
        document.getElementById('seg_inp_direccion_entrega').value     = '';
        document.getElementById('seg_inp_direccion_entrega_div').value = '';
        document.getElementById('seg_sel_empresa_factura').value     = '';
        document.getElementById('seg_sel_empresa_factura_div').value = '';
        document.getElementById('seg_inp_precio_ton').value          = '';
        document.getElementById('seg_inp_precio_ton_display').value  = '';
        document.getElementById('seg_inp_precio_ton_div').value         = '';
        document.getElementById('seg_inp_precio_ton_div_display').value = '';
        document.getElementById('seg_lbl_total_venta').textContent     = '—';
        document.getElementById('seg_lbl_total_venta_div').textContent = '—';

        if (accion === 'entregado') {
            secCliente.classList.remove('d-none');
            secTipoChatarra.classList.remove('d-none');
            secEmpresa.classList.remove('d-none');
            secPrecio.classList.remove('d-none');
        } else if (accion === 'div_carga') {
            secParcial.classList.remove('d-none');
        }

        window.segSincronizarCamposLlegada();
        validarFormLlegadaSeg();
    };

    // "Entregado" y "Div. Carga" comparten los mismos name (precio_por_tonelada,
    // cliente_id, etc.). Si ambos se envían, el último del DOM pisa al otro y el
    // valor llega vacío: se deshabilitan los de la sección oculta para que no viajen.
    window.segSincronizarCamposLlegada = function () {
        ['seg_sec_cliente', 'seg_sec_tipo_chatarra', 'seg_sec_empresa_factura', 'seg_sec_precio_venta', 'seg_sec_parcial']
            .forEach(function (id) {
                var sec = document.getElementById(id);
                if (!sec) return;
                var oculta = sec.classList.contains('d-none');
                sec.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (campo) {
                    campo.disabled = oculta;
                });
            });
    };
})();

function abrirModalLlegada(tramoUuid, info, pesoSalida, fechaSalida, camionId, conductorId, tipoTramo, proveedorId, tipoProveedor, contratoId) {
    // Guardar en variable global para usarla en el mini-modal
    window._segProveedorId   = proveedorId;
    window._segTipoProveedor = tipoProveedor || 'NACIONAL';

    // Mostrar/ocultar botón + según tipo de proveedor
    const btnNuevoLote = document.getElementById('seg_btn_nuevo_lote');
    const hint         = document.getElementById('seg_lote_hint');
    if (btnNuevoLote) {
        const esIntl = tipoProveedor === 'INTERNACIONAL';
        btnNuevoLote.classList.toggle('d-none', !esIntl);
        if (hint) hint.textContent = esIntl
            ? 'Selecciona un lote existente o crea uno nuevo con +.'
            : 'Se asigna automáticamente al lote de esta semana.';
    }

    // Resumen de toneladas del contrato (arriba, como en Gestionar Camiones)
    const secResumen = document.getElementById('seg_llegada_resumen_contrato');
    if (contratoId) {
        secResumen.style.display = '';
        document.getElementById('seg_llegada_numero_contrato').textContent = '—';
        document.getElementById('seg_llegada_fechas').textContent          = '—';
        document.getElementById('seg_llegada_tn_pactadas').textContent     = '—';
        document.getElementById('seg_llegada_tn_entregadas').textContent  = '—';
        document.getElementById('seg_llegada_tn_en_ruta').textContent     = '—';
        document.getElementById('seg_llegada_tn_pendientes').textContent  = '—';

        fetch('{{ url("api/contrato") }}/' + contratoId + '/toneladas')
            .then(r => r.json())
            .then(d => {
                const fmt = v => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
                const pactadas   = parseFloat(d.toneladas_contrato) || 0;
                const entregadas = parseFloat(d.toneladas_entregadas) || 0;
                const enRuta     = parseFloat(d.toneladas_en_transito) || 0;
                const pendientes = Math.max(0, pactadas - entregadas - enRuta);

                document.getElementById('seg_llegada_numero_contrato').textContent = d.numero_contrato || '—';
                document.getElementById('seg_llegada_fechas').textContent = d.fecha_inicio
                    ? d.fecha_inicio + ' al ' + (d.fecha_fin || 'sin fin')
                    : '—';
                document.getElementById('seg_llegada_tn_pactadas').textContent     = pactadas ? fmt(pactadas) + ' t' : '—';
                document.getElementById('seg_llegada_tn_entregadas').textContent   = fmt(entregadas) + ' t';
                document.getElementById('seg_llegada_tn_en_ruta').textContent      = fmt(enRuta) + ' t';
                document.getElementById('seg_llegada_tn_pendientes').textContent   = fmt(pendientes) + ' t';
            })
            .catch(() => {});
    } else {
        secResumen.style.display = 'none';
    }

    document.getElementById('llegada_tramo_info').textContent = info;
    document.getElementById('formLlegada').action            = '{{ url("tramo") }}/' + tramoUuid + '/llegada';
    document.getElementById('llegada_peso_max').textContent  =
        new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(pesoSalida);

    document.querySelectorAll('#formLlegada input[name="accion"]').forEach(r => { r.checked = false; r.disabled = true; });
    document.getElementById('seg_aviso_peso_requerido').style.display = '';

    document.getElementById('seg_inp_peso_llegada_display').value = '';
    document.getElementById('seg_inp_peso_llegada').value         = '';

    document.getElementById('inp_fecha_llegada').min   = fechaSalida;
    document.getElementById('inp_fecha_llegada').value = fechaSalida;

    segAccionLlegadaCambiada(null);

    document.getElementById('seg_inp_tn_parcial_display').value = '';
    document.getElementById('seg_inp_tn_parcial').value         = '';
    document.getElementById('seg_lbl_tn_restante').textContent  = '—';
    document.getElementById('seg_inp_destino_nuevo').value      = '';
    document.getElementById('seg_hidden_camion_nuevo').value    = camionId || '';
    document.getElementById('seg_hidden_conductor_nuevo').value = conductorId || '';
    document.getElementById('seg_hidden_fecha_nuevo').value     = fechaSalida;
    document.getElementById('seg_hidden_tipo_tramo_nuevo').value = tipoTramo || '';

    var chk = document.getElementById('seg_chk_descuento');
    chk.checked = false;
    document.getElementById('seg_sec_descuento').classList.add('d-none');
    document.getElementById('seg_inp_descuento').value = '';
    document.querySelector('#formLlegada textarea[name="observaciones_llegada"]').value = '';
    const segInpDoc = document.getElementById('seg_inp_documento_entrega');
    if (segInpDoc) segInpDoc.value = '';

    var btnConf = document.getElementById('btn_confirmar_llegada_seg');
    btnConf.disabled  = true;
    btnConf.className = 'btn btn-secondary';

    // Cargar lotes de entrega del proveedor
    const selLote = document.getElementById('seg_sel_lote_entrega');
    if (selLote && proveedorId) {
        selLote.innerHTML = '<option value="">Cargando lotes...</option>';
        fetch('{{ url("lotes-entrega/proveedor") }}/' + proveedorId)
            .then(r => r.json())
            .then(lotes => {
                selLote.innerHTML = '';
                if (lotes.length === 0) {
                    selLote.innerHTML = '<option value="">— Sin lotes disponibles —</option>';
                    return;
                }
                lotes.forEach((l, i) => {
                    const opt = document.createElement('option');
                    opt.value = l.id;
                    opt.textContent = l.nombre;
                    if (i === 0) opt.selected = true;
                    selLote.appendChild(opt);
                });
            })
            .catch(() => { selLote.innerHTML = '<option value="">— Error al cargar —</option>'; });
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLlegada')).show();
}

function validarFormLlegadaSeg() {
    var peso       = document.getElementById('seg_inp_peso_llegada')?.value;
    var fecha      = document.getElementById('inp_fecha_llegada')?.value;
    var accion     = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    var cliente       = document.getElementById('seg_sel_cliente')?.value;
    var tipoChatarra  = document.getElementById('seg_sel_tipo_chatarra')?.value;
    var empresa       = document.getElementById('seg_sel_empresa_factura')?.value;
    var clienteDiv      = document.getElementById('seg_sel_cliente_div')?.value;
    var tipoChatarraDiv = document.getElementById('seg_sel_tipo_chatarra_div')?.value;
    var empresaDiv      = document.getElementById('seg_sel_empresa_factura_div')?.value;
    var tnParcial  = document.getElementById('seg_inp_tn_parcial')?.value;
    var destNuevo  = document.getElementById('seg_inp_destino_nuevo')?.value.trim();
    var btn = document.getElementById('btn_confirmar_llegada_seg');
    if (!btn) return;

    var ok = peso && fecha && accion;
    if (accion === 'entregado') ok = ok && cliente && tipoChatarra && empresa;
    if (accion === 'div_carga') ok = ok && clienteDiv && tipoChatarraDiv && empresaDiv && tnParcial && destNuevo;

    btn.disabled  = !ok;
    btn.className = ok ? 'btn btn-success' : 'btn btn-secondary';
}

function submitLlegadaSeg(e) {
    e.preventDefault();

    document.querySelectorAll('#formLlegada .error-llegada-seg').forEach(el => el.remove());
    document.querySelectorAll('#formLlegada .is-invalid-llegada-seg').forEach(el => el.classList.remove('is-invalid-llegada-seg', 'border-danger'));

    const accion  = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    const errores = [];

    function marcarError(elId, msg) {
        errores.push(msg);
        const el = document.getElementById(elId);
        if (!el) return;
        el.classList.add('is-invalid-llegada-seg', 'border-danger');
        const div = document.createElement('div');
        div.className = 'text-danger small mt-1 error-llegada-seg';
        div.textContent = msg;
        el.parentNode.appendChild(div);
    }

    if (accion === 'entregado') {
        const cliente = document.getElementById('seg_sel_cliente')?.value;
        const tipoChatarra = document.getElementById('seg_sel_tipo_chatarra')?.value;
        const empresa = document.getElementById('seg_sel_empresa_factura')?.value;
        if (!cliente) marcarError('seg_sel_cliente', 'Debe seleccionar el cliente que recibe la carga.');
        if (!tipoChatarra) marcarError('seg_sel_tipo_chatarra', 'Debe indicar si es chatarra o fundido.');
        if (!empresa) marcarError('seg_sel_empresa_factura', 'Debe seleccionar la empresa que facturará.');
    }

    if (accion === 'div_carga') {
        const clienteDiv = document.getElementById('seg_sel_cliente_div')?.value;
        const tipoChatarraDiv = document.getElementById('seg_sel_tipo_chatarra_div')?.value;
        const empresaDiv = document.getElementById('seg_sel_empresa_factura_div')?.value;
        const tnParcial  = document.getElementById('seg_inp_tn_parcial')?.value;
        const destNuevo  = document.getElementById('seg_inp_destino_nuevo')?.value.trim();
        if (!clienteDiv) marcarError('seg_sel_cliente_div', 'Debe seleccionar el cliente que recibe la carga.');
        if (!tipoChatarraDiv) marcarError('seg_sel_tipo_chatarra_div', 'Debe indicar si es chatarra o fundido.');
        if (!empresaDiv) marcarError('seg_sel_empresa_factura_div', 'Debe seleccionar la empresa que facturará.');
        if (!tnParcial || parseFloat(tnParcial) <= 0) marcarError('seg_inp_tn_parcial_display', 'Debe ingresar las toneladas entregadas.');
        if (!destNuevo) marcarError('seg_inp_destino_nuevo', 'Debe ingresar el destino del nuevo tramo.');
    }

    if (errores.length > 0) {
        const primero = document.querySelector('#formLlegada .error-llegada-seg');
        if (primero) primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Deshabilitar campos de la sección oculta para que no se envíen duplicados
    if (accion === 'entregado') {
        ['seg_sel_cliente_div', 'seg_sel_tipo_chatarra_div', 'seg_sel_empresa_factura_div', 'seg_inp_direccion_entrega_div'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });
        document.querySelectorAll('#seg_sec_parcial [name="tn_parcial"], #seg_sec_parcial [name="destino_nuevo_tramo"]').forEach(function(el) {
            el.disabled = true;
        });
    } else if (accion === 'div_carga') {
        ['seg_sel_cliente', 'seg_sel_tipo_chatarra', 'seg_sel_empresa_factura', 'seg_inp_direccion_entrega'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });
    }

    document.getElementById('formLlegada').removeEventListener('submit', submitLlegadaSeg);
    document.getElementById('formLlegada').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const formLlegadaSeg = document.getElementById('formLlegada');
    if (formLlegadaSeg) {
        formLlegadaSeg.addEventListener('submit', submitLlegadaSeg);
    }
});

function ejecutarAccionSeg(sel) {
    const accion = sel.value;
    if (!accion) return;
    sel.value = ''; // reset para permitir volver a seleccionar

    const d = sel.dataset;

    if (accion === 'contrato') {
        window.location.href = d.contratoUrl;

    } else if (accion === 'nota') {
        window.open(d.notaUrl, '_blank');

    } else if (accion === 'llegada') {
        abrirModalLlegada(d.uuid, d.label, d.peso, d.fecha, d.camionId || null, d.conductorId || null, d.tipoTramo || '', d.proveedorId || null);

    } else if (accion === 'pago') {
        abrirModalPagoSeg(
            d.ccId,
            d.ccLabel,
            d.saldo,
            d.moneda,
            d.conductorId || null,
            d.conductorNombre || '',
            d.propietarioId || null,
            d.propietarioNombre || ''
        );

    } else if (accion === 'flete') {
        abrirModalFlete(d.ccUuid, d.fleteLabel);
    }
}

// ---- Transbordo desde seguimiento ----
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('seg_tsb_camion_id')) {
        $('#seg_tsb_camion_id').select2({
            placeholder: 'Busque por placa...',
            width: '100%',
            dropdownParent: $('#modalTransbordoSeg'),
            language: { noResults: () => 'No se encontró ningún camión.', searching: () => 'Buscando...' }
        });
        $('#seg_tsb_camion_id').on('change', function () {
            const uuid = $('#seg_tsb_camion_id option:selected').data('uuid');
            cargarConductoresSeg(uuid);
            validarFormTransbordoSeg();
        });

        document.getElementById('modalTransbordoSeg').addEventListener('input', validarFormTransbordoSeg);
        document.getElementById('seg_tsb_conductor_id').addEventListener('change', validarFormTransbordoSeg);
    }
});

function cargarConductoresSeg(uuid) {
    const sel   = document.getElementById('seg_tsb_conductor_id');
    const aviso = document.getElementById('seg_tsb_sin_conductor_aviso');
    const btn   = document.getElementById('btn_registrar_transbordo_seg');

    sel.innerHTML = '<option value="">— Cargando... —</option>';
    sel.disabled  = true;
    aviso.classList.add('d-none');

    if (!uuid) {
        sel.innerHTML = '<option value="">— Primero seleccione un camión —</option>';
        return;
    }

    fetch('{{ url("api/camion") }}/' + uuid + '/conductores-relacionados', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(conductores => {
        if (conductores.length === 0) {
            sel.innerHTML = '<option value="">— Sin conductores —</option>';
            aviso.classList.remove('d-none');
            if (btn) btn.disabled = true;
            return;
        }
        aviso.classList.add('d-none');
        sel.innerHTML = '<option value="">— Seleccione conductor —</option>';
        conductores.forEach(c => {
            const op = document.createElement('option');
            op.value       = c.id;
            op.textContent = c.nombre + ' — Lic: ' + (c.licencia || 'S/N') + ' [' + c.tipo + ']';
            sel.appendChild(op);
        });
        sel.disabled = false;
        validarFormTransbordoSeg();
    })
    .catch(() => { sel.innerHTML = '<option value="">— Error al cargar —</option>'; });
}

function validarFormTransbordoSeg() {
    const camion    = document.getElementById('seg_tsb_camion_id')?.value;
    const conductor = document.getElementById('seg_tsb_conductor_id')?.value;
    const destino   = document.getElementById('seg_tsb_destino')?.value.trim();
    const peso      = document.getElementById('seg_tsb_peso_salida')?.value;
    const fecha     = document.getElementById('seg_tsb_fecha_salida')?.value;
    const btn       = document.getElementById('btn_registrar_transbordo_seg');
    const aviso     = document.getElementById('seg_tsb_sin_conductor_aviso');
    const sinConductor = aviso && !aviso.classList.contains('d-none');

    const completo = camion && conductor && destino && peso && fecha && !sinConductor;
    btn.disabled  = !completo;
    btn.className = completo ? 'btn btn-info' : 'btn btn-secondary';
}

function abrirModalTransbordoSeg(ccId, tramoPadreId, infoPadre, disponible, fechaLlegadaPadre) {
    document.getElementById('seg_tsb_cc_id').value              = ccId;
    document.getElementById('seg_tsb_padre_id').value           = tramoPadreId;
    document.getElementById('seg_tsb_info_padre').textContent   = infoPadre;
    document.getElementById('seg_tsb_peso_disponible').textContent = disponible;

    const inp = document.getElementById('seg_tsb_peso_salida');
    inp.max   = disponible;
    inp.value = '';
    inp.oninput = function () {
        if (parseFloat(this.value) > parseFloat(disponible)) this.value = disponible;
        validarFormTransbordoSeg();
    };

    document.getElementById('seg_tsb_fecha_salida').min   = fechaLlegadaPadre ?? '';
    document.getElementById('seg_tsb_fecha_salida').value = fechaLlegadaPadre ?? '';
    document.getElementById('seg_tsb_destino').value = '';

    const sel = document.getElementById('seg_tsb_conductor_id');
    sel.innerHTML = '<option value="">— Seleccione un camión primero —</option>';
    sel.disabled  = true;

    document.getElementById('seg_tsb_sin_conductor_aviso').classList.add('d-none');
    document.getElementById('btn_registrar_transbordo_seg').disabled = true;
    document.getElementById('btn_registrar_transbordo_seg').className = 'btn btn-secondary';

    if (window.$) {
        $('#seg_tsb_camion_id').val(null).trigger('change');
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTransbordoSeg')).show();
}

function abrirHistorialPagos(ccId, camionLabel) {
    document.getElementById('hist_loading').style.display = 'block';
    document.getElementById('hist_contenido').style.display = 'none';
    document.getElementById('hist_contenido').innerHTML = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistorialPagos')).show();

    fetch(`${url_global}/api/pagos/camiones/${ccId}/detalle`)
        .then(r => r.json())
        .then(d => {
            document.getElementById('hist_loading').style.display = 'none';
            const cont = document.getElementById('hist_contenido');
            const cabecera = `
                <div class="px-3 pt-3 pb-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="small text-muted"><i class="bi bi-truck me-1"></i>Camión: <strong>${d.camion ?? '—'}</strong></span>
                        <span class="small text-muted">Contrato: <strong>${d.contrato ?? '—'}</strong></span>
                        <span class="small text-muted"><i class="bi bi-person me-1"></i>Conductor: <strong>${d.conductor ?? '—'}</strong></span>
                        <span class="small text-muted"><i class="bi bi-signpost-split me-1"></i>Ruta: <strong>${d.ruta ?? '—'}</strong></span>
                    </div>
                </div>`;
            if (!d.pagos || !d.pagos.length) {
                cont.innerHTML = cabecera + '<div class="alert alert-info mx-3"><i class="bi bi-info-circle me-1"></i>Este contrato no tiene pagos registrados aún.</div>';
                cont.style.display = 'block';
                return;
            }
            const moneda    = d.moneda_flete ?? 'BOB';
            // El pago se hace en la moneda del flete: el modal de editar la fija con esto
            _segMonedaFlete = moneda;
            const saldoClass = d.saldo_pendiente > 0 ? 'text-danger' : 'text-success';
            const saldoIcon  = d.saldo_pendiente > 0 ? 'bi-exclamation-circle' : 'bi-check-circle';
            let html = `
            <div class="px-3 pt-3 pb-2">
                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
                    <span class="small text-muted"><i class="bi bi-truck me-1"></i>Camión: <strong>${d.camion ?? '—'}</strong></span>
                    <span class="small text-muted">Contrato: <strong>${d.contrato}</strong></span>
                    <span class="small text-muted"><i class="bi bi-person me-1"></i>Conductor: <strong>${d.conductor ?? '—'}</strong></span>
                    <span class="small text-muted"><i class="bi bi-signpost-split me-1"></i>Ruta: <strong>${d.ruta ?? '—'}</strong></span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="small text-muted">Acordado: <strong>${moneda} ${_fmtS(d.monto_acordado||0)}</strong></span>
                    <span class="small text-success">Pagado: <strong>${moneda} ${_fmtS(d.total_pagado||0)}</strong></span>
                    <span class="small ${saldoClass}"><i class="bi ${saldoIcon} me-1"></i>Saldo: <strong>${moneda} ${_fmtS(d.saldo_pendiente||0)}</strong></span>
                </div>
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Tipo de cambio</th>
                            <th>Método</th>
                            <th>Receptor</th>
                            <th>Cuenta origen</th>
                            <th>Código</th>
                            <th>Voucher</th>
                            ${(segCanEditPago || segCanDeletePago) ? '<th class="text-center">Acciones</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>`;
            d.pagos.forEach(p => {
                const origen = p.cuenta_origen ? p.cuenta_origen.titular : '—';

                let acciones = '';
                if (segCanEditPago || segCanDeletePago) {
                    const editar = segCanEditPago
                        ? `<button class="btn btn-sm btn-outline-secondary border-0"
                                   onclick="abrirModalEditarPago('${p.uuid}', '${p.tipo_raw}', ${parseFloat(p.monto)}, '${p.moneda_pago}', ${parseFloat(p.tipo_cambio)}, '${p.fecha_raw}', '${p.metodo_raw}', '${p.codigo || ''}', ${p.voucher_url ? `'${p.voucher_url}'` : 'null'})"
                                   title="Editar"><i class="bi bi-pencil"></i></button>`
                        : '';
                    const eliminar = segCanDeletePago
                        ? `<a href="${url_global}/pagos/camiones/${p.uuid}/destroy"
                              class="btn btn-sm btn-outline-danger border-0"
                              onclick="return confirm('¿Eliminar este pago?')"
                              title="Eliminar"><i class="bi bi-trash"></i></a>`
                        : '';
                    acciones = `<td class="text-center" style="white-space:nowrap">${editar}${eliminar}</td>`;
                }

                html += `<tr>
                    <td class="small">${p.fecha}</td>
                    <td><span class="badge bg-secondary">${p.tipo}</span></td>
                    <td class="text-end small fw-semibold">${p.moneda_pago} ${_fmtS(p.monto)}</td>
                    <td class="text-end small">
                        ${p.moneda_pago === 'BOB'
                            ? '<span class="text-muted">—</span>'
                            : `${_fmtS(p.tipo_cambio)}<span class="text-muted d-block" style="font-size:.7rem">= Bs ${_fmtS(p.monto_bob)}</span>`}
                    </td>
                    <td class="small">${p.metodo}</td>
                    <td class="small">${p.receptor ?? '—'}</td>
                    <td class="small">${origen}</td>
                    <td class="small text-muted">${p.codigo ?? '—'}</td>
                    <td class="small">${p.tiene_voucher
                        ? `<a href="${p.voucher_url}" target="_blank" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-check"></i></a>`
                        : '<span class="text-muted">—</span>'}</td>
                    ${acciones}
                </tr>`;
            });
            html += `</tbody></table></div>`;
            cont.innerHTML = html;
            cont.style.display = 'block';
        })
        .catch(() => {
            document.getElementById('hist_loading').style.display = 'none';
            document.getElementById('hist_contenido').innerHTML = '<div class="alert alert-danger m-3">Error al cargar el historial.</div>';
            document.getElementById('hist_contenido').style.display = 'block';
        });
}

// ---- Mini-modal lote internacional (seguimiento) ----
document.addEventListener('DOMContentLoaded', function () {
    const today          = new Date().toISOString().split('T')[0];
    const modalLlegada   = document.getElementById('modalLlegada');
    const modalNuevoLote = document.getElementById('seg_modalNuevoLote');
    if (!modalNuevoLote) return;

    const bsModalNuevoLote = new bootstrap.Modal(modalNuevoLote);
    const bsModalLlegada   = () => bootstrap.Modal.getOrCreateInstance(modalLlegada);

    const nlInicio   = document.getElementById('seg_nl_fecha_inicio');
    const nlFin      = document.getElementById('seg_nl_fecha_fin');
    const nlError    = document.getElementById('seg_nl_error');
    const btnGuardar = document.getElementById('seg_btn_guardar_lote');
    const btnNuevo   = document.getElementById('seg_btn_nuevo_lote');

    // Fecha fin sigue a fecha inicio
    nlInicio.addEventListener('change', function () {
        nlFin.min = this.value;
        if (nlFin.value < this.value) nlFin.value = this.value;
    });

    // Abrir mini-modal
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function () {
            bsModalLlegada().hide();
            modalLlegada.addEventListener('hidden.bs.modal', function abrirNuevo() {
                modalLlegada.removeEventListener('hidden.bs.modal', abrirNuevo);
                nlInicio.value = today;
                nlFin.value    = today;
                nlFin.min      = today;
                nlError.classList.add('d-none');
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Crear Lote';
                bsModalNuevoLote.show();
            }, { once: true });
        });
    }

    // Cancelar / cerrar: volver al modal de llegada
    ['seg_btn_cerrar_nuevo_lote', 'seg_btn_cancelar_nuevo_lote'].forEach(function (id) {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', function () {
            bsModalNuevoLote.hide();
            modalNuevoLote.addEventListener('hidden.bs.modal', function volver() {
                modalNuevoLote.removeEventListener('hidden.bs.modal', volver);
                bsModalLlegada().show();
            }, { once: true });
        });
    });

    // Guardar lote vía AJAX
    btnGuardar.addEventListener('click', function () {
        const inicio = nlInicio.value;
        const fin    = nlFin.value;

        if (!inicio || !fin) {
            nlError.textContent = 'Las fechas de inicio y fin son obligatorias.';
            nlError.classList.remove('d-none');
            return;
        }
        if (fin < inicio) {
            nlError.textContent = 'La fecha fin no puede ser menor a la fecha inicio.';
            nlError.classList.remove('d-none');
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        nlError.classList.add('d-none');

        const _fd2 = new FormData();
        _fd2.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        _fd2.append('proveedor_id', window._segProveedorId);
        _fd2.append('fecha_inicio', inicio);
        _fd2.append('fecha_fin', fin);
        _fd2.append('observaciones', document.getElementById('seg_nl_observaciones').value);
        fetch('{{ route("lotes_entrega.store.ajax") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: _fd2,
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                nlError.textContent = data.error;
                nlError.classList.remove('d-none');
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Crear Lote';
                return;
            }
            bsModalNuevoLote.hide();
            modalNuevoLote.addEventListener('hidden.bs.modal', function volverConLote() {
                modalNuevoLote.removeEventListener('hidden.bs.modal', volverConLote);
                const selLote = document.getElementById('seg_sel_lote_entrega');
                if (selLote.options.length === 1 && !selLote.options[0].value) {
                    selLote.innerHTML = '';
                }
                const opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.nombre;
                opt.selected = true;
                selLote.insertBefore(opt, selLote.firstChild);
                bsModalLlegada().show();
            }, { once: true });
        })
        .catch(() => {
            nlError.textContent = 'Error de conexión. Intenta de nuevo.';
            nlError.classList.remove('d-none');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Crear Lote';
        });
    });
});

@if(isset($tramoErrorLlegada) && $tramoErrorLlegada && $errors->llegada->any())
// Reabrir modal de llegada con los datos del tramo que falló
document.addEventListener('DOMContentLoaded', function () {
    const t = {
        uuid:          '{{ $tramoErrorLlegada->uuid }}',
        info:          '{{ addslashes($tramoErrorLlegada->origen . " → " . $tramoErrorLlegada->destino . " (" . $tramoErrorLlegada->camion->placa . ")") }}',
        pesoSalida:    {{ $tramoErrorLlegada->peso_salida }},
        fechaSalida:   '{{ $tramoErrorLlegada->fecha_salida->format("Y-m-d") }}',
        camionId:      {{ $tramoErrorLlegada->camion_id }},
        conductorId:   {{ $tramoErrorLlegada->conductor_id ?? 'null' }},
        tipoTramo:     '{{ $tramoErrorLlegada->tipo_tramo }}',
        proveedorId:   {{ $tramoErrorLlegada->contratoCamion->contrato->proveedor_id ?? 'null' }},
        tipoProveedor: '{{ $tramoErrorLlegada->contratoCamion->contrato->proveedor->tipo_proveedor ?? "NACIONAL" }}',
        contratoId:    {{ $tramoErrorLlegada->contratoCamion->contrato_id ?? 'null' }},
    };
    abrirModalLlegada(t.uuid, t.info, t.pesoSalida, t.fechaSalida, t.camionId, t.conductorId, t.tipoTramo, t.proveedorId, t.tipoProveedor, t.contratoId);

    // Restaurar campos del old input
    @if(old('peso_llegada'))
        document.getElementById('seg_inp_peso_llegada').value         = '{{ old("peso_llegada") }}';
        document.getElementById('seg_inp_peso_llegada_display').value = new Intl.NumberFormat('es-BO', {minimumFractionDigits:2,maximumFractionDigits:2}).format({{ old("peso_llegada") }});
        document.getElementById('seg_aviso_peso_requerido').style.display = 'none';
        ['seg_accion_entregado','seg_accion_parcial','seg_accion_transbordo'].forEach(function(id){
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
    @endif
    @if(old('fecha_llegada'))
        document.getElementById('inp_fecha_llegada').value = '{{ old("fecha_llegada") }}';
    @endif
    @if(old('accion'))
        const radioOld = document.querySelector('#formLlegada input[name="accion"][value="{{ old("accion") }}"]');
        if (radioOld) { radioOld.checked = true; segAccionLlegadaCambiada('{{ old("accion") }}'); }
    @endif
    @if(old('cliente_id'))
        document.getElementById('seg_sel_cliente').value = '{{ old("cliente_id") }}';
    @endif
    @if(old('tipo_chatarra'))
        document.getElementById('seg_sel_tipo_chatarra').value = '{{ old("tipo_chatarra") }}';
    @endif
    @if(old('empresa_facturadora_id'))
        document.getElementById('seg_sel_empresa_factura').value = '{{ old("empresa_facturadora_id") }}';
    @endif

    validarFormLlegadaSeg();
});
@endif
</script>
@endsection
