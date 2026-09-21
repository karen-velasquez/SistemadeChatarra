@extends('layouts.app')
@section('titulo', 'Reportes')
@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="mb-2">Reportes</h1>
        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Genera reportes detallados de deudas a proveedores, deudas por fletes, cuentas por cobrar y flujo de caja.</p>
    </div>

    <div class="section-card mb-4">
        <ul class="nav nav-tabs report-tabs px-3 pt-3" id="reportTabs">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'deudas_proveedores' ? 'active' : '' }}" href="{{ route('newReports.index', ['tab' => 'deudas_proveedores']) }}"><i class="bi bi-truck me-1"></i>Deudas a Proveedores</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'deudas_fletes' ? 'active' : '' }}" href="{{ route('newReports.index', ['tab' => 'deudas_fletes']) }}"><i class="bi bi-truck-flatbed me-1"></i>Deudas por Fletes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'cuentas_cobrar' ? 'active' : '' }}" href="{{ route('newReports.index', ['tab' => 'cuentas_cobrar']) }}"><i class="bi bi-cash-stack me-1"></i>Cuentas por Cobrar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'flujo_caja' ? 'active' : '' }}" href="{{ route('newReports.index', ['tab' => 'flujo_caja']) }}"><i class="bi bi-bank me-1"></i>Flujo de Caja</a>
            </li>
        </ul>

        <div class="p-4">
            @if($tab === 'deudas_proveedores')
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="panel-title mb-1"><i class="bi bi-funnel me-2"></i>Filtros - Deudas a Proveedores</h5>
                        <p class="text-muted small mb-0">Selecciona los filtros para generar el reporte.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit" form="formDeudasProveedores"><i class="bi bi-search me-1"></i>Vista Previa</button>
                        @can('reportes.export')
                        <button class="btn btn-success" type="submit" form="formDeudasProveedores" formaction="{{ route('newReports.export') }}"><i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel</button>
                        @endcan
                    </div>
                </div>
                <form method="GET" action="{{ route('newReports.index') }}" id="formDeudasProveedores">
                    <input type="hidden" name="tab" value="deudas_proveedores">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" value="{{ $fechaInicio }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" class="form-control" name="fecha_fin" value="{{ $fechaFin }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select name="proveedor_id" id="proveedor_id" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3" id="divContrato" style="display: none;">
                            <label class="form-label fw-semibold">Contrato</label>
                            <select name="contrato_id" id="contrato_id" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}" data-proveedor="{{ $c->proveedor_id }}" {{ request('contrato_id') == $c->id ? 'selected' : '' }}>{{ $c->numero_contrato }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Estado Contrato</label>
                            <select class="form-select" name="estado_contrato">
                                <option value="">TODOS</option>
                                @foreach($estadoContratos as $estado)
                                    <option value="{{ $estado }}" {{ request('estado_contrato') == $estado ? 'selected' : '' }}>{{ ucfirst($estado) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Estado Deuda</label>
                            <select class="form-select" name="estado_deuda">
                                <option value="">TODAS</option>
                                <option value="pendiente" {{ request('estado_deuda') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagada" {{ request('estado_deuda') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                            </select>
                        </div>
                    </div>
                </form>

                <hr class="my-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Monto Total Contratos</div>
                            <div class="fs-4 fw-bold">{{ number_format($reporte['totales']['monto_total'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Pagado</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($reporte['totales']['pagado'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Saldo Pendiente</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($reporte['totales']['pendiente'], 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="datos" class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Contrato</th>
                                <th>Proveedor</th>
                                <th>Estado</th>
                                <th class="text-end">Toneladas Recibidas</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">Pendiente</th>
                                <th>Moneda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporte['items'] as $c)
                                <tr>
                                    <td>{{ $c->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $c->numero_contrato }}</td>
                                    <td>{{ $c->proveedor->nombre ?? '—' }}</td>
                                    <td>{{ ucfirst($c->estado) }}</td>
                                    <td class="text-end">{{ number_format($c->toneladas_entregadas, 2, ',', '.') }} t</td>
                                    <td class="text-end">{{ number_format($c->monto_total, 2) }}</td>
                                    <td class="text-end">{{ number_format($c->total_pagado_proveedor, 2) }}</td>
                                    <td class="text-end">{{ number_format($c->saldo_pendiente_proveedor, 2) }}</td>
                                    <td>{{ $c->moneda }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No hay resultados para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
            @if($tab === 'deudas_fletes')
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div><h5 class="panel-title mb-1"><i class="bi bi-funnel me-2"></i>Filtros - Deudas por Fletes</h5><p class="text-muted small mb-0">Selecciona los filtros para generar el reporte.</p></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit" form="formDeudasFletes"><i class="bi bi-search me-1"></i>Vista Previa</button>
                        @can('reportes.export')
                        <button class="btn btn-success" type="submit" form="formDeudasFletes" formaction="{{ route('newReports.export') }}"><i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel</button>
                        @endcan
                    </div>
                </div>
                <form method="GET" action="{{ route('newReports.index') }}" id="formDeudasFletes">
                    <input type="hidden" name="tab" value="deudas_fletes">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" value="{{ $fechaInicio }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" class="form-control" name="fecha_fin" value="{{ $fechaFin }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select name="proveedor_id" id="proveedor_id_fletes" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3" id="divContratoFletes" style="display: none;">
                            <label class="form-label fw-semibold">Contrato</label>
                            <select name="contrato_id" id="contrato_id_fletes" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}" data-proveedor="{{ $c->proveedor_id }}" {{ request('contrato_id') == $c->id ? 'selected' : '' }}>{{ $c->numero_contrato }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Estado Deuda</label>
                            <select class="form-select" name="estado_deuda">
                                <option value="">TODAS</option>
                                <option value="pendiente" {{ request('estado_deuda') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagada" {{ request('estado_deuda') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                            </select>
                        </div>
                    </div>
                </form>

                <hr class="my-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Monto Acordado (Neto)</div>
                            <div class="fs-4 fw-bold">{{ number_format($reporte['totales']['monto_neto'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Pagado</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($reporte['totales']['pagado'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Saldo Pendiente</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($reporte['totales']['pendiente'], 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Proveedor</th>
                                <th>Camión</th>
                                <th class="text-end">Monto Neto</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">Pendiente</th>
                                <th>Moneda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporte['items'] as $cc)
                                <tr>
                                    <td>{{ $cc->contrato->numero_contrato ?? '—' }}</td>
                                    <td>{{ $cc->contrato->proveedor->nombre ?? '—' }}</td>
                                    <td>{{ $cc->camion->placa ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($cc->monto_neto, 2) }}</td>
                                    <td class="text-end">{{ number_format($cc->total_pagado, 2) }}</td>
                                    <td class="text-end">{{ number_format($cc->saldo_pendiente, 2) }}</td>
                                    <td>{{ $cc->moneda_flete }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No hay resultados para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
            @if($tab === 'cuentas_cobrar')
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="panel-title mb-1"><i class="bi bi-funnel me-2"></i>Filtros - Cuentas por Cobrar</h5>
                        <p class="text-muted small mb-0">Selecciona los filtros para generar el reporte.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit" form="formCuentasCobrar"><i class="bi bi-search me-1"></i>Vista Previa</button>
                        @can('reportes.export')
                        <button class="btn btn-success" type="submit" form="formCuentasCobrar" formaction="{{ route('newReports.export') }}"><i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel</button>
                        @endcan
                    </div>
                </div>
                <form method="GET" action="{{ route('newReports.index') }}" id="formCuentasCobrar">
                    <input type="hidden" name="tab" value="cuentas_cobrar">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" value="{{ $fechaInicio }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" class="form-control" name="fecha_fin" value="{{ $fechaFin }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Cliente</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Contrato</label>
                            <select name="contrato_id" class="form-select">
                                <option value="">TODOS</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}" {{ request('contrato_id') == $c->id ? 'selected' : '' }}>{{ $c->numero_contrato }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Estado Entrega</label>
                            <select class="form-select" name="estado_entrega">
                                <option value="">TODOS</option>
                                <option value="En ruta" {{ request('estado_entrega') == 'En ruta' ? 'selected' : '' }}>En ruta</option>
                                <option value="Transbordando" {{ request('estado_entrega') == 'Transbordando' ? 'selected' : '' }}>Transbordando</option>
                                <option value="Entregado" {{ request('estado_entrega') == 'Entregado' ? 'selected' : '' }}>Entregado</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Estado Cobro</label>
                            <select class="form-select" name="estado_cobro">
                                <option value="">TODOS</option>
                                <option value="pendiente" {{ request('estado_cobro') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="cobrado" {{ request('estado_cobro') == 'cobrado' ? 'selected' : '' }}>Cobrado</option>
                            </select>
                        </div>
                    </div>
                </form>
                <hr class="my-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Facturado</div>
                            <div class="fs-4 fw-bold">{{ number_format($reporte['totales']['facturado'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Cobrado</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($reporte['totales']['cobrado'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Saldo Pendiente</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($reporte['totales']['pendiente'], 2) }}</div>
                        </div>
                    </div>
                </div>

                @if($reporte['sinCobro']->isNotEmpty())
                    <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>{{ $reporte['sinCobro']->count() }} entrega(s) marcadas como "Entregado" sin ningún cobro registrado.</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Cliente</th>
                                <th>Estado Entrega</th>
                                <th class="text-end">Peso Llegada (Ton)</th>
                                <th class="text-end">Facturado</th>
                                <th class="text-end">Cobrado</th>
                                <th class="text-end">Pendiente</th>
                                <th>Moneda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporte['items'] as $t)
                                <tr>
                                    <td>{{ $t->contratoCamion->contrato->numero_contrato ?? '—' }}</td>
                                    <td>{{ $t->cliente->nombre ?? '—' }}</td>
                                    <td>{{ $t->estado }}</td>
                                    <td class="text-end">{{ number_format($t->peso_llegada, 3) }}</td>
                                    <td class="text-end">{{ number_format($t->monto_deuda_cliente, 2) }}</td>
                                    <td class="text-end">{{ number_format($t->total_cobrado_cliente, 2) }}</td>
                                    <td class="text-end">{{ number_format($t->saldo_cliente, 2) }}</td>
                                    <td>{{ $t->moneda_venta }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No hay resultados para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if($tab === 'flujo_caja')
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="panel-title mb-1"><i class="bi bi-funnel me-2"></i>Filtros - Flujo de Caja</h5>
                        <p class="text-muted small mb-0">Selecciona los filtros para generar el reporte.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit" form="formFlujoCaja"><i class="bi bi-search me-1"></i>Vista Previa</button>
                        @can('reportes.export')
                        <button class="btn btn-success" type="submit" form="formFlujoCaja" formaction="{{ route('newReports.export') }}"><i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel</button>
                        @endcan
                    </div>
                </div>
                <form method="GET" action="{{ route('newReports.index') }}" id="formFlujoCaja">
                    <input type="hidden" name="tab" value="flujo_caja">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" value="{{ $fechaInicio }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" class="form-control" name="fecha_fin" value="{{ $fechaFin }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Cuenta</label>
                            <select name="cuenta_empresa_id" class="form-select">
                                <option value="">TODAS</option>
                                @foreach($cuentasEmpresa as $cuenta)
                                    <option value="{{ $cuenta->id }}" {{ request('cuenta_empresa_id') == $cuenta->id ? 'selected' : '' }}>{{ $cuenta->banco->nombre }} {{ $cuenta->moneda }} - {{ str_repeat('*', strlen($cuenta->numero_cuenta) - 4) . substr($cuenta->numero_cuenta, -4) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select name="tipo_movimiento" class="form-select">
                                <option value="">TODOS</option>
                                <option value="ingreso" {{ request('tipo_movimiento') == 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                                <option value="egreso" {{ request('tipo_movimiento') == 'egreso' ? 'selected' : '' }}>Egreso</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Categoría</label>
                            <select name="categoria" class="form-select">
                                <option value="">TODAS</option>
                                @foreach($categoriasMovimiento as $valor => $label)
                                    <option value="{{ $valor }}" {{ request('categoria') == $valor ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
                <hr class="my-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Ingresos</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($reporte['totales']['ingresos'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Total Egresos</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($reporte['totales']['egresos'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Saldo Neto</div>
                            <div class="fs-4 fw-bold {{ $reporte['totales']['saldoNeto'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($reporte['totales']['saldoNeto'], 2) }}</div>
                        </div>
                    </div>
                </div>

                @if($reporte['porCategoria']->isNotEmpty())
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reporte['porCategoria'] as $cat)
                                <tr>
                                    <td>{{ $cat['label'] }}</td>
                                    <td class="text-end">{{ number_format($cat['ingreso'], 2) }}</td>
                                    <td class="text-end">{{ number_format($cat['egreso'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cuenta</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th>Concepto</th>
                                <th class="text-end">Monto (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporte['items'] as $m)
                                <tr>
                                    <td>{{ $m->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $m->cuentaEmpresa->nombre_cuenta ?? '—' }}</td>
                                    <td><span class="badge {{ $m->tipo === 'ingreso' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($m->tipo) }}</span></td>
                                    <td>{{ \App\Models\Movimiento::categoriaLabel($m->categoria) }}</td>
                                    <td>{{ $m->concepto }}</td>
                                    <td class="text-end">{{ number_format($m->monto_bolivianos, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function activarFiltroContrato(idProveedor, idContrato, idDiv) {
        const proveedor = document.getElementById(idProveedor);
        const contrato = document.getElementById(idContrato);
        const divContrato = document.getElementById(idDiv);
        if (!proveedor || !contrato || !divContrato) return;
        function filtrar() {
            const proveedorSeleccionado = proveedor.value;
            if (proveedorSeleccionado === '') {
                divContrato.style.display = 'none';
                contrato.value = '';
                Array.from(contrato.options).forEach((option, index) => {
                    option.hidden = index !== 0;
                });
                return;
            }
            divContrato.style.display = 'block';
            Array.from(contrato.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }
                option.hidden = option.dataset.proveedor != proveedorSeleccionado;
            });
            if (contrato.options[contrato.selectedIndex] && contrato.options[contrato.selectedIndex].hidden) {
                contrato.value = '';
            }
        }
        filtrar();
        proveedor.addEventListener('change', filtrar);
    }
    activarFiltroContrato('proveedor_id', 'contrato_id', 'divContrato');
    activarFiltroContrato('proveedor_id_fletes', 'contrato_id_fletes', 'divContratoFletes');
});
</script>
@endsection