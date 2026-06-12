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
                <div class="fw-bold fs-4">{{ $resumen['en_ruta'] }}</div>
                <div class="text-muted small">En ruta</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#ffc107;"><i class="bi bi-arrow-left-right"></i></div>
                <div class="fw-bold fs-4">{{ $resumen['transbordando'] }}</div>
                <div class="text-muted small">Transbordando</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#0dcaf0;"><i class="bi bi-check2-all"></i></div>
                <div class="fw-bold fs-4">{{ $resumen['transbordado'] }}</div>
                <div class="text-muted small">Transbordado</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:2rem; color:#198754;"><i class="bi bi-check-circle"></i></div>
                <div class="fw-bold fs-4">{{ $resumen['entregado'] }}</div>
                <div class="text-muted small">Entregados</div>
            </div>
        </div>
    </div>

    {{-- Filtro por proveedor --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-0"><i class="bi bi-box-seam me-1"></i>Filtrar por proveedor:</label>
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" id="filtro_proveedor_seg" onchange="filtrarPorProveedorSeg(this.value)">
                        <option value="">— Todos los proveedores —</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm" onclick="filtrarPorProveedorSeg('')">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs" id="segTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-en-ruta" type="button">
                        <i class="bi bi-truck text-primary"></i> En ruta
                        <span class="badge bg-primary ms-1">{{ $resumen['en_ruta'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-transbordando" type="button">
                        <i class="bi bi-arrow-left-right text-warning"></i> Transbordando
                        <span class="badge bg-warning text-dark ms-1">{{ $resumen['transbordando'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-transbordado" type="button">
                        <i class="bi bi-check2-all text-info"></i> Transbordado
                        <span class="badge bg-info text-dark ms-1">{{ $resumen['transbordado'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-entregados" type="button">
                        <i class="bi bi-check-circle text-success"></i> Entregados
                        <span class="badge bg-success ms-1">{{ $resumen['entregado'] }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3">

                {{-- EN RUTA --}}
                <div class="tab-pane fade show active" id="pane-en-ruta">
                    @if($enRuta->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay camiones en ruta.</div>
                    @else
                    <div class="table-responsive">
                        <table id="tabla_en_ruta" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrato</th>
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
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}">
                                    <td>
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
                                    <td class="text-end">{{ number_format($t->peso_salida, 3) }} t</td>
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
                                                    <button class="dropdown-item" onclick="abrirModalLlegada('{{ $t->uuid }}','{{ $t->origen }} → {{ $t->destino }} ({{ $t->camion->placa }})','{{ $t->peso_salida }}','{{ $t->fecha_salida->format('Y-m-d') }}',{{ $t->camion_id }},{{ $t->conductor_id ?? 'null' }},'{{ $t->tipo_tramo }}')">
                                                        <i class="bi bi-geo-alt text-success me-2"></i> Registrar llegada
                                                    </button>
                                                </li>
                                                @if($t->contratoCamion->monto_acordado)
                                                <li>
                                                    <button class="dropdown-item" onclick="abrirModalPagoSeg({{ $t->contratoCamion->id }},'{{ addslashes($t->camion->placa) }} — {{ addslashes($t->camion->marca->valor ?? '-') }}',{{ $t->contratoCamion->saldo_pendiente }},'{{ $t->contratoCamion->moneda_flete ?? 'BOB' }}',{{ $t->conductor_id ?? 'null' }},'{{ addslashes($t->conductor?->nombre_completo ?? '') }}',{{ $t->contratoCamion->camion->propietario_id ?? 'null' }},'{{ addslashes($t->contratoCamion->camion->propietario?->nombre_completo ?? '') }}')">
                                                        <i class="bi bi-cash-coin text-warning me-2"></i> Registrar pago
                                                    </button>
                                                </li>
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
                                    <th>Contrato</th>
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
                                @endphp
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}">
                                    <td>
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
                                        {{ number_format($t->peso_llegada, 3) }} t
                                        @if($disponibleTransbordo > 0)
                                            <small class="text-warning d-block">{{ number_format($disponibleTransbordo, 3) }} t libres</small>
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
                                    <th>Contrato</th>
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
                                <tr class="{{ !$t->contratoCamion->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $t->contratoCamion->contrato->proveedor_id }}">
                                    <td>
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
                                    <td class="text-end">{{ number_format($t->peso_llegada, 3) }} t</td>
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
                    @if($entregados->isEmpty())
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay entregas registradas aún.</div>
                    @else
                    <p class="text-muted small"><i class="bi bi-info-circle"></i> Mostrando las últimas 50 entregas.</p>
                    <div class="table-responsive">
                        <table id="tabla_entregados" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrato</th>
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
                                @php $cc = $t->contratoCamion; @endphp
                                <tr class="{{ !$cc->monto_acordado ? 'table-warning' : '' }}" data-proveedor-id="{{ $cc->contrato->proveedor_id }}">
                                    <td>
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
                                        <strong class="text-success">{{ number_format($t->peso_llegada, 3) }} t</strong>
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
            <form method="POST" action="{{ route('pagos.camiones.store') }}">
                @csrf
                <input type="hidden" name="contrato_camion_id" id="seg_pago_cc_id">
                <div class="modal-body">

                    {{-- Info del camión --}}
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong id="seg_pago_camion_label"></strong>
                            <span>
                                <small class="text-muted" id="seg_pago_saldo_lbl_texto">Saldo pendiente:</small>
                                <span class="badge bg-danger ms-1" id="seg_pago_saldo_label"></span>
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
                                            <input type="number" step="0.01" min="0.01" class="form-control" name="monto" id="seg_inp_monto" required placeholder="0.00" oninput="segCalcEquiv()">
                                        </div>
                                    </div>
                                    <div class="col-md-5" id="seg_sec_tc" style="display:none;">
                                        <label class="form-label fw-semibold mb-1">
                                            Tipo de cambio <span class="text-danger">*</span>
                                            <small class="text-muted fw-normal">— 1 <span id="seg_lbl_tc_moneda"></span> =</small>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.0001" min="0.0001" class="form-control" name="tipo_cambio" id="seg_inp_tc" placeholder="0.0000" oninput="segCalcEquiv()" disabled>
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
                        <input type="hidden" name="tipo_cambio" id="seg_inp_tc_bob" value="1">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="seg_metodo_pago" required onchange="segToggleCodigo(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="seg_sec_codigo" style="display:none;">
                            <label class="form-label">Código de Seguimiento / N° Cheque</label>
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
                            <select class="form-select" name="cuenta_origen_id">
                                <option value="">-- Efectivo / Sin cuenta --</option>
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
                            <label class="form-label">Cuenta Destino (Receptor)</label>
                            <select class="form-select" name="cuenta_destino_id" id="seg_cuenta_destino">
                                <option value="">-- Efectivo / Sin cuenta --</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500" placeholder="Notas del pago..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark"><i class="bi bi-save"></i> Registrar Pago</button>
                </div>
            </form>

            {{-- Historial de pagos anteriores --}}
            <div class="border-top px-4 py-3" id="seg_historial_wrap">
                <h6 class="text-muted mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i> Historial de pagos
                </h6>
                <div id="seg_historial_body">
                    <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== MODAL EDITAR PAGO CAMIÓN ===== --}}
<div class="modal fade" id="modalEditarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarPago" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_pago" id="edit_tipo_pago" required>
                                <option value="adelanto">Adelanto</option>
                                <option value="flete">Flete</option>
                                <option value="pago_final">Pago Final</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_pago" id="edit_fecha_pago" required>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="monto" id="edit_monto" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-12" id="edit_sec_tc">
                            <label class="form-label fw-semibold">Tipo de Cambio a BOB <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="tipo_cambio" id="edit_tipo_cambio" step="0.0001" min="0.0001" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="edit_metodo_pago" required>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="qr">QR</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código de seguimiento</label>
                            <input type="text" class="form-control" name="codigo_seguimiento" id="edit_codigo" maxlength="100">
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
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    name="monto_acordado" required placeholder="0.00">
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
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-light btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalLlegada"
                            data-steps='[
                                {"intro":"📍 Aquí registras qué pasó cuando el camión <b>llegó</b> a su destino. Es el paso clave del seguimiento. Te explico los campos."},
                                {"element":"#inp_peso_llegada","intro":"⚖️ <b>Peso al llegar</b>: las toneladas reales pesadas al llegar. Puede diferir del peso de salida (merma).","position":"bottom"},
                                {"element":"#inp_fecha_llegada","intro":"📅 <b>Fecha de llegada</b>. No puede ser anterior a la fecha de salida.","position":"bottom"},
                                {"element":"#seg_accion_entregado","intro":"✅ <b>Entregado al cliente</b>: la carga llegó a su destino final. Pedirá el cliente y el precio de venta.","position":"right"},
                                {"element":"#seg_accion_parcial","intro":"🥧 <b>Div. Carga</b>: entregas una parte a un cliente y el resto continúa en otro camión. Se crean 2 tramos automáticamente.","position":"right"},
                                {"element":"#seg_accion_transbordo","intro":"🔄 <b>Transbordo</b>: la carga cambia de camión y continúa (típico en frontera o cambio de unidad).","position":"right"},
                                {"element":"#seg_chk_descuento","intro":"➖ Opcional: aplica un <b>descuento</b> al pago del camionero (ej. por chatarra en mal estado o faltante).","position":"top"},
                                {"element":"#btn_confirmar_llegada_seg","intro":"💾 El botón <b>Confirmar</b> se activa cuando completas todos los campos obligatorios según la acción elegida.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i> Ayuda
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form method="POST" id="formLlegada" action="">
                @csrf
                <input type="hidden" name="origen" value="seguimiento">
                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                    <div class="alert alert-light border mb-3 py-2">
                        <small class="text-muted">Tramo:</small><br>
                        <strong id="llegada_tramo_info"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" id="lbl_peso_llegada">Peso al llegar (t) <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.001" min="0.001" class="form-control"
                                name="peso_llegada" id="inp_peso_llegada" required placeholder="Toneladas reales pesadas"
                                oninput="calcularRestanteParcial()">
                            <small class="text-muted">Carga estipulada en el origen: <strong id="llegada_peso_max"></strong> t</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de llegada <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_llegada" id="inp_fecha_llegada" required>
                            <small class="text-muted">No puede ser anterior a la fecha de salida.</small>
                        </div>

                        {{-- Sección cliente (entregado normal y parcial) --}}
                        <div class="col-12 d-none" id="seg_sec_cliente">
                            <label class="form-label fw-semibold">Cliente que recibe la carga <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cliente_id" id="seg_sel_cliente">
                                <option value="">-- Seleccione un cliente --</option>
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}">{{ $cli->nombre }}@if($cli->nit) — NIT: {{ $cli->nit }}@endif</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Precio de venta al cliente (entregado y entrega parcial) --}}
                        <div class="col-12 d-none" id="seg_sec_precio_venta">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="fw-semibold mb-2"><i class="bi bi-tag text-success"></i> Precio de venta al cliente</div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Moneda</label>
                                        <select class="form-select form-select-sm" name="moneda_venta" id="seg_sel_moneda_venta">
                                            @foreach($monedas as $moneda)
                                                <option value="{{ $moneda->valor }}">{{ $moneda->valor }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Precio por tonelada</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.0001" min="0" class="form-control"
                                                name="precio_por_tonelada" id="seg_inp_precio_ton"
                                                placeholder="0.00">
                                            <span class="input-group-text">/t</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Sección entrega parcial --}}
                        <div class="col-12 d-none" id="seg_sec_parcial">
                            <div class="border rounded-3 p-3 bg-light">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart text-info"></i> Datos de la entrega parcial</h6>
                                <div class="alert alert-info py-2 mb-3">
                                    <small><i class="bi bi-info-circle"></i> El campo <strong>"Peso al llegar"</strong> arriba indica el total que llegó. Ingresa abajo cuántas toneladas se entregan ahora a este cliente — el resto continuará en un nuevo tramo.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">TN entregadas a este cliente <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" class="form-control"
                                            name="tn_parcial" id="seg_inp_tn_parcial"
                                            placeholder="0.000" oninput="calcularRestanteParcial()">
                                        <small class="text-muted">TN para el nuevo tramo: <strong id="lbl_tn_restante">—</strong></small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Destino del nuevo tramo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="destino_nuevo_tramo"
                                            id="seg_inp_destino_nuevo" maxlength="150"
                                            placeholder="Ciudad / punto de entrega">
                                    </div>
                                </div>
                                <input type="hidden" name="camion_nuevo_id" id="seg_hidden_camion_nuevo">
                                <input type="hidden" name="conductor_nuevo_id" id="seg_hidden_conductor_nuevo">
                                <input type="hidden" name="fecha_salida_nuevo_tramo" id="seg_hidden_fecha_nuevo">
                                <input type="hidden" name="tipo_tramo_nuevo" id="seg_hidden_tipo_tramo_nuevo">
                            </div>
                        </div>

                        {{-- ¿Qué ocurrió al llegar? --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">¿Qué ocurrió al llegar? <span class="text-danger">(*)</span></label>
                            <div class="d-flex flex-column gap-2 mt-1">
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="radio" name="accion" value="entregado" id="seg_accion_entregado" required
                                        onchange="accionLlegadaCambiada('entregado')">
                                    <label class="form-check-label" for="seg_accion_entregado">
                                        <i class="bi bi-check-circle text-success"></i>
                                        <strong>Entregado al cliente</strong>
                                        <small class="d-block text-muted">La carga llegó a su destino final.</small>
                                    </label>
                                </div>
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="radio" name="accion" value="div_carga" id="seg_accion_parcial" required
                                        onchange="accionLlegadaCambiada('div_carga')">
                                    <label class="form-check-label" for="seg_accion_parcial">
                                        <i class="bi bi-pie-chart text-info"></i>
                                        <strong>Div. Carga</strong>
                                        <small class="d-block text-muted">Entrega parte al cliente 1 y el restante continúa en otro camión al cliente 2. Se generan 2 tramos automáticamente.</small>
                                    </label>
                                </div>
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="radio" name="accion" value="transbordo" id="seg_accion_transbordo" required
                                        onchange="accionLlegadaCambiada('transbordo')">
                                    <label class="form-check-label" for="seg_accion_transbordo">
                                        <i class="bi bi-arrow-left-right text-warning"></i>
                                        <strong>Transbordando a otro(s) camión(es)</strong>
                                        <small class="d-block text-muted">La carga continúa en otros camiones.</small>
                                    </label>
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
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Historial de Pagos — <span id="hist_camion_label"></span></h5>
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

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
const dtLang = {
    processing:  "Procesando...",
    search:      "Buscar:",
    lengthMenu:  'Mostrar <select><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="-1">Todos</option></select> registros',
    info:        "Página _PAGE_ de _PAGES_",
    infoEmpty:   "",
    emptyTable:  "No hay datos disponibles.",
    zeroRecords: "No se encontraron resultados.",
    paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
};

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('tabla_en_ruta')) {
        $('#tabla_en_ruta').DataTable({ language: dtLang, pageLength: 25 });
    }

    const tabsConfig = {
        'pane-transbordando': 'tabla_transbordando',
        'pane-transbordado':  'tabla_transbordado',
        'pane-entregados':    'tabla_entregados',
    };

    document.querySelectorAll('#segTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            const paneId  = e.target.getAttribute('data-bs-target').replace('#', '');
            const tablaId = tabsConfig[paneId];
            if (tablaId && document.getElementById(tablaId) && !$.fn.DataTable.isDataTable('#' + tablaId)) {
                $('#' + tablaId).DataTable({ language: dtLang, pageLength: 25 });
            }
        });
    });
});

// ---- Pago desde seguimiento ----
let segReceptorActual = {};
let _segMonedaPendiente = null;

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
    document.getElementById('seg_pago_saldo_label').textContent     = parseFloat(saldo).toFixed(2) + ' ' + (moneda || 'BOB');
    document.getElementById('seg_pago_saldo_lbl_texto').textContent = 'Saldo pendiente:';

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
    document.getElementById('seg_inp_monto').value = '';
    document.getElementById('seg_inp_tc').value    = '';

    // Guardar moneda para aplicarla cuando el modal esté visible (Bootstrap no permite cambiar el DOM antes)
    _segMonedaPendiente = moneda || 'BOB';

    segCargarHistorial(ccId);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPagoSeg')).show();
}

function segCargarHistorial(ccId) {
    const wrap = document.getElementById('seg_historial_body');
    wrap.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

    fetch(`${url_global}/api/pagos/camiones/${ccId}/detalle`)
        .then(r => r.json())
        .then(d => {
            if (!d.pagos || d.pagos.length === 0) {
                wrap.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No hay pagos registrados aún.</p>';
                return;
            }

            const monedaFlag = { BOB: '🇧🇴', USD: '🇺🇸', BRL: '🇧🇷', ARS: '🇦🇷', EUR: '🇪🇺' };
            const badgeTipo  = { 'Adelanto': 'bg-warning text-dark', 'Flete': 'bg-info text-dark', 'Pago Final': 'bg-success' };

            // Resumen compacto
            const mon = d.moneda_flete || 'BOB';
            let html = `
            <div class="d-flex gap-2 flex-wrap mb-3">
                <div class="rounded-2 px-3 py-1 border text-center" style="min-width:90px">
                    <div class="text-muted" style="font-size:.7rem">ACORDADO</div>
                    <div class="fw-semibold small">${mon} ${parseFloat(d.monto_neto||0).toFixed(2)}</div>
                </div>
                <div class="rounded-2 px-3 py-1 border text-center" style="min-width:90px">
                    <div class="text-muted" style="font-size:.7rem">PAGADO</div>
                    <div class="fw-semibold small text-success">${mon} ${parseFloat(d.total_pagado||0).toFixed(2)}</div>
                </div>
                <div class="rounded-2 px-3 py-1 border text-center ${parseFloat(d.saldo_pendiente)<=0?'bg-success bg-opacity-10':'bg-danger bg-opacity-10'}" style="min-width:90px">
                    <div class="text-muted" style="font-size:.7rem">SALDO</div>
                    <div class="fw-semibold small ${parseFloat(d.saldo_pendiente)<=0?'text-success':'text-danger'}">${mon} ${parseFloat(d.saldo_pendiente||0).toFixed(2)}</div>
                </div>
            </div>`;

            // Lista de pagos
            html += '<div class="d-flex flex-column gap-2">';
            d.pagos.forEach(p => {
                const esBob   = p.moneda_pago === 'BOB';
                const flag    = monedaFlag[p.moneda_pago] || '';
                const badge   = badgeTipo[p.tipo] || 'bg-secondary';
                const tcLine  = esBob ? '' : `<span class="text-muted ms-1" style="font-size:.7rem">TC: 1 ${p.moneda_pago} = ${parseFloat(p.tipo_cambio).toFixed(4)} Bs</span>`;
                const bobLine = esBob ? '' : `<span class="text-primary ms-2 small">= Bs ${parseFloat(p.monto_bob).toFixed(2)}</span>`;

                // Cuenta destino
                let cuentaDestLine = '';
                if (p.cuenta_destino) {
                    const relacion = p.cuenta_destino.tipo_relacion
                        ? ` <span class="badge bg-secondary" style="font-size:.65rem">${p.cuenta_destino.tipo_relacion}</span>`
                        : '';
                    const titular = p.cuenta_destino.titular_cuenta
                        ? `<span class="fw-semibold text-warning-emphasis">👤 ${p.cuenta_destino.titular_cuenta}</span>${relacion} — `
                        : '';
                    cuentaDestLine = `<div class="small mt-1">🏦 ${titular}${p.cuenta_destino.banco} <code>${p.cuenta_destino.numero}</code> [${p.cuenta_destino.moneda}]${p.cuenta_destino.alias ? ' <span class="text-muted">(' + p.cuenta_destino.alias + ')</span>' : ''}</div>`;
                }

                // Cuenta origen (quién pagó)
                let cuentaOrigLine = '';
                if (p.cuenta_origen) {
                    const alias = p.cuenta_origen.alias ? ` (${p.cuenta_origen.alias})` : '';
                    cuentaOrigLine = `<span class="text-muted ms-2" style="font-size:.75rem">· Pagó: ${p.cuenta_origen.titular}${alias}</span>`;
                }

                html += `
                <div class="rounded-2 border px-3 py-2 bg-white">
                    <div class="d-flex align-items-start gap-3">
                        <div class="pt-1"><span class="badge ${badge}">${p.tipo}</span></div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${flag} ${p.moneda_pago} ${parseFloat(p.monto).toFixed(2)}${tcLine}${bobLine}</div>
                            <div class="text-muted small">${p.fecha} &nbsp;·&nbsp; ${p.metodo}${p.receptor ? ' &nbsp;·&nbsp; <span class="text-dark">' + p.receptor + '</span>' : ''}${cuentaOrigLine}</div>
                            ${cuentaDestLine}
                        </div>
                        <div class="d-flex flex-column gap-1 mt-1">
                            <button class="btn btn-sm btn-outline-secondary border-0"
                                onclick="abrirModalEditarPago('${p.uuid}', '${p.tipo_raw}', ${parseFloat(p.monto)}, '${p.moneda_pago}', ${parseFloat(p.tipo_cambio)}, '${p.fecha_raw}', '${p.metodo_raw}', '${p.codigo||''}')"
                                title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="/pagos/camiones/${p.uuid}/destroy"
                               class="btn btn-sm btn-outline-danger border-0"
                               onclick="return confirm('¿Eliminar este pago?')" title="Eliminar">
                               <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            wrap.innerHTML = html;
        })
        .catch(() => {
            wrap.innerHTML = '<p class="text-danger small mb-0"><i class="bi bi-exclamation-circle me-1"></i>No se pudo cargar el historial.</p>';
        });
}

function segToggleTC(moneda) {
    const secTC    = document.getElementById('seg_sec_tc');
    const secEquiv = document.getElementById('seg_sec_equiv');
    const inpTC    = document.getElementById('seg_inp_tc');
    const inpBob   = document.getElementById('seg_inp_tc_bob');

    document.getElementById('seg_moneda_pago').value    = moneda;
    document.getElementById('seg_lbl_moneda').textContent = moneda;

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
        document.getElementById('seg_lbl_equiv').textContent = (monto * tc).toFixed(2);
        secEquiv.style.display = 'block';
    } else {
        secEquiv.style.display = 'none';
    }
}

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

function filtrarPorProveedorSeg(proveedorId) {
    const tablas = ['tabla_en_ruta', 'tabla_transbordando', 'tabla_transbordado', 'tabla_entregados'];
    tablas.forEach(function(id) {
        const tabla = document.getElementById(id);
        if (!tabla) return;
        tabla.querySelectorAll('tbody tr').forEach(function(fila) {
            const ok = !proveedorId || fila.dataset.proveedorId == proveedorId;
            fila.style.display = ok ? '' : 'none';
        });
    });
    const sel = document.getElementById('filtro_proveedor_seg');
    if (sel) sel.value = proveedorId;
}

function abrirModalEditarPago(uuid, tipo, monto, moneda, tipoCambio, fecha, metodo, codigo) {
    document.getElementById('formEditarPago').action = url_global + '/pagos/camiones/' + uuid;
    document.getElementById('edit_tipo_pago').value   = tipo;
    document.getElementById('edit_monto').value       = monto;
    document.getElementById('edit_moneda_pago').value = moneda;
    document.getElementById('edit_tipo_cambio').value = tipoCambio;
    document.getElementById('edit_fecha_pago').value  = fecha;
    document.getElementById('edit_metodo_pago').value = metodo;
    document.getElementById('edit_codigo').value      = codigo;
    editToggleTc(moneda);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarPago')).show();
}

function editToggleTc(moneda) {
    document.getElementById('edit_sec_tc').style.display = moneda === 'BOB' ? 'none' : 'block';
    if (moneda === 'BOB') document.getElementById('edit_tipo_cambio').value = 1;
}

function abrirModalFlete(ccUuid, label) {
    document.getElementById('flete_label').textContent = label;
    document.getElementById('formFlete').action = '{{ url("contrato-camion") }}/' + ccUuid + '/flete';
    document.getElementById('flete_moneda').value = 'BOB';
    document.querySelector('#formFlete input[name="monto_acordado"]').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFlete')).show();
}

function abrirModalLlegada(tramoUuid, info, pesoSalida, fechaSalida, camionId, conductorId, tipoTramo) {
    document.getElementById('llegada_tramo_info').textContent = info;
    document.getElementById('formLlegada').action            = '{{ url("tramo") }}/' + tramoUuid + '/llegada';
    document.getElementById('llegada_peso_max').textContent  = pesoSalida;
    document.querySelectorAll('input[name="accion"]').forEach(r => r.checked = false);

    const inp = document.getElementById('inp_peso_llegada');
    inp.removeAttribute('max');
    inp.value = '';

    document.getElementById('inp_fecha_llegada').min   = fechaSalida;
    document.getElementById('inp_fecha_llegada').value = fechaSalida;

    document.getElementById('seg_sec_cliente').classList.add('d-none');
    document.getElementById('seg_sel_cliente').value = '';
    document.getElementById('seg_sec_precio_venta').classList.add('d-none');
    document.getElementById('seg_inp_precio_ton').value = '';
    document.getElementById('seg_sec_parcial').classList.add('d-none');
    document.getElementById('seg_inp_tn_parcial').value    = '';
    document.getElementById('seg_inp_destino_nuevo').value = '';
    document.getElementById('seg_hidden_camion_nuevo').value    = camionId || '';
    document.getElementById('seg_hidden_conductor_nuevo').value = conductorId || '';
    document.getElementById('seg_hidden_fecha_nuevo').value     = fechaSalida;
    document.getElementById('seg_hidden_tipo_tramo_nuevo').value = tipoTramo || '';
    document.getElementById('lbl_tn_restante').textContent = '—';
    _llegadaPesoSalida = pesoSalida;
    const chk = document.getElementById('seg_chk_descuento');
    chk.checked = false;
    document.getElementById('seg_sec_descuento').classList.add('d-none');
    document.getElementById('seg_inp_descuento').value = '';
    document.querySelector('#formLlegada textarea[name="observaciones_llegada"]').value = '';

    const btnConf = document.getElementById('btn_confirmar_llegada_seg');
    btnConf.disabled  = true;
    btnConf.className = 'btn btn-secondary';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLlegada')).show();
}

let _llegadaPesoSalida = 0;

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalLlegada');
    if (modal) {
        modal.addEventListener('input',  validarFormLlegadaSeg);
        modal.addEventListener('change', validarFormLlegadaSeg);
    }
});

function validarFormLlegadaSeg() {
    const peso   = document.getElementById('inp_peso_llegada')?.value;
    const fecha  = document.getElementById('inp_fecha_llegada')?.value;
    const accion = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    const cliente   = document.getElementById('seg_sel_cliente')?.value;
    const tnParcial = document.getElementById('seg_inp_tn_parcial')?.value;
    const destNuevo = document.getElementById('seg_inp_destino_nuevo')?.value.trim();
    const btn = document.getElementById('btn_confirmar_llegada_seg');
    if (!btn) return;

    let ok = peso && fecha && accion;
    if (accion === 'entregado')       ok = ok && cliente;
    if (accion === 'div_carga') ok = ok && cliente && tnParcial && destNuevo;

    btn.disabled  = !ok;
    btn.className = ok ? 'btn btn-success' : 'btn btn-secondary';
}

function accionLlegadaCambiada(accion) {
    const secCliente = document.getElementById('seg_sec_cliente');
    const secPrecio  = document.getElementById('seg_sec_precio_venta');
    const secParcial = document.getElementById('seg_sec_parcial');
    const selCliente = document.getElementById('seg_sel_cliente');

    secCliente.classList.add('d-none');
    secPrecio.classList.add('d-none');
    secParcial.classList.add('d-none');

    if (accion === 'entregado') {
        secCliente.classList.remove('d-none');
        secPrecio.classList.remove('d-none');
    } else if (accion === 'div_carga') {
        secCliente.classList.remove('d-none');
        secPrecio.classList.remove('d-none');
        secParcial.classList.remove('d-none');
    } else {
        selCliente.value = '';
    }
    validarFormLlegadaSeg();
}

function calcularRestanteParcial() {
    const tnEntregadas = parseFloat(document.getElementById('seg_inp_tn_parcial').value) || 0;
    const pesoTotal    = parseFloat(document.getElementById('inp_peso_llegada').value) || 0;
    const restante     = Math.max(0, pesoTotal - tnEntregadas);
    document.getElementById('lbl_tn_restante').textContent = restante > 0 ? restante.toFixed(3) + ' t' : '—';
}

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
        abrirModalLlegada(d.uuid, d.label, d.peso, d.fecha, d.camionId || null, d.conductorId || null, d.tipoTramo || '');

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
    document.getElementById('hist_camion_label').textContent = camionLabel;
    document.getElementById('hist_loading').style.display = 'block';
    document.getElementById('hist_contenido').style.display = 'none';
    document.getElementById('hist_contenido').innerHTML = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistorialPagos')).show();

    fetch(`${url_global}/api/pagos/camiones/${ccId}/detalle`)
        .then(r => r.json())
        .then(d => {
            document.getElementById('hist_loading').style.display = 'none';
            const cont = document.getElementById('hist_contenido');
            if (!d.pagos || !d.pagos.length) {
                cont.innerHTML = '<div class="alert alert-info m-3"><i class="bi bi-info-circle me-1"></i>Este contrato no tiene pagos registrados aún.</div>';
                cont.style.display = 'block';
                return;
            }
            const moneda    = d.moneda_flete ?? 'BOB';
            const saldoClass = d.saldo_pendiente > 0 ? 'text-danger' : 'text-success';
            const saldoIcon  = d.saldo_pendiente > 0 ? 'bi-exclamation-circle' : 'bi-check-circle';
            let html = `
            <div class="px-3 pt-3 pb-2">
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="small text-muted">Contrato: <strong>${d.contrato}</strong></span>
                    <span class="small text-muted">Acordado: <strong>${moneda} ${parseFloat(d.monto_acordado||0).toFixed(2)}</strong></span>
                    <span class="small text-success">Pagado: <strong>${moneda} ${parseFloat(d.total_pagado||0).toFixed(2)}</strong></span>
                    <span class="small ${saldoClass}"><i class="bi ${saldoIcon} me-1"></i>Saldo: <strong>${moneda} ${parseFloat(d.saldo_pendiente||0).toFixed(2)}</strong></span>
                </div>
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th class="text-end">Monto</th>
                            <th>Método</th>
                            <th>Receptor</th>
                            <th>Cuenta origen</th>
                            <th>Código</th>
                        </tr>
                    </thead>
                    <tbody>`;
            d.pagos.forEach(p => {
                const origen = p.cuenta_origen ? p.cuenta_origen.titular : '—';
                html += `<tr>
                    <td class="small">${p.fecha}</td>
                    <td><span class="badge bg-secondary">${p.tipo}</span></td>
                    <td class="text-end small fw-semibold">${p.moneda_pago} ${parseFloat(p.monto).toFixed(2)}</td>
                    <td class="small">${p.metodo}</td>
                    <td class="small">${p.receptor ?? '—'}</td>
                    <td class="small">${origen}</td>
                    <td class="small text-muted">${p.codigo ?? '—'}</td>
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
</script>
@endsection
