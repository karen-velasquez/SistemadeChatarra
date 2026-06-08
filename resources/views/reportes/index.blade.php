@extends('layouts.app')
@section('titulo', 'Reportes')
@section('content')

<div class="container-fluid">
    <div class="mb-4">
    <h1>Reportes</h1>
     <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>Genera reportes detallados de tus operaciones de compra y venta de chatarra. Filtra por fechas, clientes, proveedores y tipo de contrato para obtener insights clave sobre ingresos, gastos, rentabilidad y desempeño comercial. Visualiza resúmenes generales o detalles específicos para tomar decisiones informadas y optimizar tu negocio.
    </p>   
    </div>
    <div class="section-card p-4 mb-4">
        <h5 class="panel-title mb-4"><i class="bi bi-funnel me-2"></i>Filtros de Reporte</h5>

       <form method="GET" action="{{ route('reportes.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor_id" class="form-select">
                        <option value="">TODOS</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}" {{ request('proveedor_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">TODOS</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipo Contrato</label>
                    <select name="tipo_contrato" class="form-select">
                        <option value="">TODOS</option>
                        <option value="NACIONAL" {{ request('tipo_contrato') == 'NACIONAL' ? 'selected' : '' }}>NACIONAL</option>
                        <option value="INTERNACIONAL" {{ request('tipo_contrato') == 'INTERNACIONAL' ? 'selected' : '' }}>INTERNACIONAL</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnGuiaReportes" class="btn btn-outline-primary btn-iniciar-tour"
                            data-steps='[
                                {"intro":"📊 Bienvenido al módulo de <b>Reportes Generales</b>. Aquí puedes analizar contratos, proveedores, clientes, pagos, cobros, gastos, utilidad y movimientos operativos."},
                                {"element":"input[name=fecha_inicio]","intro":"📅 Selecciona la <b>fecha de inicio</b> para limitar el reporte a un periodo específico.","position":"bottom"},
                                {"element":"input[name=fecha_fin]","intro":"📅 Selecciona la <b>fecha final</b>. El reporte mostrará datos registrados dentro de este rango.","position":"bottom"},
                                {"element":"select[name=proveedor_id]","intro":"🏭 Filtra por <b>proveedor</b> para revisar contratos, pagos y operaciones relacionadas con un proveedor específico.","position":"bottom"},
                                {"element":"select[name=cliente_id]","intro":"👤 Filtra por <b>cliente</b> para analizar ventas, cobros y entregas asociadas a ese cliente.","position":"bottom"},
                                {"element":"select[name=tipo_contrato]","intro":"📄 Filtra por <b>tipo de contrato</b>, nacional o internacional, para comparar operaciones según su naturaleza.","position":"bottom"},
                                {"element":"#btnFiltrarReportes","intro":"🔎 Presiona <b>Filtrar</b> para actualizar todos los indicadores, tablas y resultados del reporte general.","position":"left"},
                                {"element":"#btnExportarExcel","intro":"📥 Con <b>Exportar Excel</b> descargas el reporte filtrado en formato Excel.","position":"left"},
                                {"element":".report-tabs","intro":"🧭 Estas pestañas dividen el reporte por secciones: <b>Resumen General</b>, <b>Ventas</b>, <b>Compras</b> y <b>Gastos</b>.","position":"bottom"},
                                {"intro":"✅ Usa los filtros, revisa los indicadores y exporta el reporte cuando necesites respaldar la información."}
                            ]'>
                            <i class="bi bi-question-circle"></i>
                        </button>
                        <button type="submit" id="btnFiltrarReportes" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filtrar</button>
                    </div>
                </div>
            </div>
        </form>

        @can('reportes.export')
            <div class="d-flex justify-content-end mt-2">
              <a href="{{ route('reportes.exportar.excel', request()->query()) }}" id="btnExportarExcel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
            </div>
        @endcan
    <div class="report-tabs mb-4">
        <button type="button" class="report-tab active" onclick="mostrarReporte('resumen', this)">Resumen General</button>
        <button type="button" class="report-tab" onclick="mostrarReporte('ventas', this)">Ventas</button>
        <button type="button" class="report-tab" onclick="mostrarReporte('compras', this)">Compras</button>
        <button type="button" class="report-tab" onclick="mostrarReporte('gastos', this)">Gastos</button>
    </div>

    <div id="tab-resumen" class="reporte-contenido">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="dash-card p-4">
                    <div class="metric-label">Ingresos Totales</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($totalVentas, 2) }}</div>
                    <div class="metric-help">Cobros a clientes</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card p-4">
                    <div class="metric-label">Gastos Totales</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($gastosTotales, 2) }}</div>
                    <div class="metric-help">Compras + gastos + transporte</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card p-4">
                    <div class="metric-label">Margen Bruto</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($margenBruto, 2) }}</div>
                    <div class="metric-help">{{ $margenBruto >= 0 ? 'Resultado positivo' : 'Resultado negativo' }}</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card p-4">
                    <div class="metric-label">Rentabilidad</div>
                    <div class="metric-number mt-4">{{ number_format($rentabilidad, 2) }}%</div>
                    <div class="metric-help">Margen sobre ingresos</div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="section-card p-4 h-100">
                    <h5 class="panel-title mb-4">Resumen de Movimientos</h5>
                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Ventas / Cobros</h6>
                            <span class="text-muted">{{ $cobrosCliente->count() }} transacciones</span>
                        </div>
                        <h5 class="fw-bold mb-0">Bs. {{ number_format($totalVentas, 2) }}</h5>
                    </div>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Compras / Proveedores</h6>
                            <span class="text-muted">{{ $pagosProveedor->count() }} transacciones</span>
                        </div>
                        <h5 class="fw-bold mb-0">Bs. {{ number_format($totalCompras, 2) }}</h5>
                    </div>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Gastos Extras</h6>
                            <span class="text-muted">{{ $gastosExtras->count() }} transacciones</span>
                        </div>
                        <h5 class="fw-bold mb-0">Bs. {{ number_format($totalGastosExtras, 2) }}</h5>
                    </div>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Pagos a Camiones</h6>
                            <span class="text-muted">{{ $pagosCamiones->count() }} transacciones</span>
                        </div>
                        <h5 class="fw-bold mb-0">Bs. {{ number_format($totalPagosCamiones, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-card p-4 h-100">
                    <h5 class="panel-title mb-4">Indicadores del Periodo</h5>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Contratos filtrados</h6>
                            <span class="text-muted">Según filtros aplicados</span>
                        </div>
                        <h5 class="fw-bold mb-0">{{ $contratos->count() }}</h5>
                    </div>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Resultado bruto</h6>
                            <span class="text-muted">Ingresos menos egresos</span>
                        </div>
                        <h5 class="fw-bold mb-0 {{ $margenBruto >= 0 ? 'text-success' : 'text-danger' }}">Bs. {{ number_format($margenBruto, 2) }}</h5>
                    </div>

                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">Rentabilidad</h6>
                            <span class="text-muted">Sobre ingresos</span>
                        </div>
                        <h5 class="fw-bold mb-0 {{ $rentabilidad >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($rentabilidad, 2) }}%</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-ventas" class="reporte-contenido d-none">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="dash-card p-4">
                    <div class="metric-label">Total Ventas</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($totalVentas, 2) }}</div>
                    <div class="metric-help">{{ $cobrosCliente->count() }} cobros registrados</div>
                </div>
            </div>

        </div>
        <div class="section-card p-4">
            <h5 class="panel-title mb-4">Ventas / Cobros a Clientes</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Contrato</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cobrosCliente as $c)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $c->contrato->numero_contrato ?? '-' }}</td>
                                <td>{{ $c->contrato->cliente->nombre ?? '-' }}</td>
                                <td>{{ $c->moneda ?? 'BOB' }} {{ number_format($c->monto, 2) }}</td>
                                <td><span class="badge {{ $c->estado == 'pagado' ? 'bg-success' : 'bg-warning text-dark' }}">{{ strtoupper($c->estado) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay ventas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-compras" class="reporte-contenido d-none">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="dash-card p-4">
                    <div class="metric-label">Total Compras</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($totalCompras, 2) }}</div>
                    <div class="metric-help">{{ $pagosProveedor->count() }} pagos a proveedores</div>
                </div>
            </div>
        </div>
        <div class="section-card p-4">
            <h5 class="panel-title mb-4">Compras / Pagos a Proveedores</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Contrato</th>
                            <th>Proveedor</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagosProveedor as $p)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $p->contrato->numero_contrato ?? '-' }}</td>
                                <td>{{ $p->contrato->proveedor->nombre ?? '-' }}</td>
                                <td>{{ $p->moneda }} {{ number_format($p->monto, 2) }}</td>
                                <td><span class="badge {{ $p->estado == 'pagado' ? 'bg-success' : 'bg-warning text-dark' }}">{{ strtoupper($p->estado) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay compras registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-gastos" class="reporte-contenido d-none">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="dash-card p-4">
                    <div class="metric-label">Gastos Extras</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($totalGastosExtras, 2) }}</div>
                    <div class="metric-help">{{ $gastosExtras->count() }} transacciones</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="dash-card p-4">
                    <div class="metric-label">Pagos a Camiones</div>
                    <div class="metric-number mt-4">Bs. {{ number_format($totalPagosCamiones, 2) }}</div>
                    <div class="metric-help">{{ $pagosCamiones->count() }} transacciones</div>
                </div>
            </div>

        </div>

        <div class="section-card p-4 mb-4">
            <h5 class="panel-title mb-4">Distribución de Gastos Extras</h5>
            @forelse($gastosPorCategoria as $g)
                <div class="item-box d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $g->categoria }}</h6>
                        <span class="text-muted">{{ $g->cantidad }} transacciones</span>
                    </div>
                    <h5 class="fw-bold mb-0">Bs. {{ number_format($g->total, 2) }}</h5>
                </div>
            @empty
                <div class="text-center text-muted py-4">No hay gastos extras en el rango seleccionado.</div>
            @endforelse
        </div>
        <div class="section-card p-4">
            <h5 class="panel-title mb-4">Detalle de Gastos Extras</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Contrato</th>
                            <th>Categoría</th>
                            <th>Concepto</th>
                            <th>Monto BOB</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($gastosExtras as $g)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($g->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $g->contrato->numero_contrato ?? '-' }}</td>
                                <td><span class="badge bg-primary">{{ $g->categoria }}</span></td>
                                <td>{{ $g->concepto }}</td>
                                <td>Bs. {{ number_format($g->monto_bolivianos, 2) }}</td>
                                <td><span class="badge {{ $g->estado == 'pagado' ? 'bg-success' : 'bg-warning text-dark' }}">{{ strtoupper($g->estado) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay gastos extras registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function mostrarReporte(tab, boton) {
    document.querySelectorAll('.reporte-contenido').forEach(function(contenido) {
        contenido.classList.add('d-none');
    });
    document.getElementById('tab-' + tab).classList.remove('d-none');
    document.querySelectorAll('.report-tab').forEach(function(btn) {
        btn.classList.remove('active');
    });
    boton.classList.add('active');
}
</script>
@endsection