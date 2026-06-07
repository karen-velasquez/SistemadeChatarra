@extends('layouts.app')
@section('content')

<div class="container-fluid">

    <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Dashboard</h1>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                data-steps='[
                    {"intro":"👋 ¡Bienvenido al Sistema de Gestión de Chatarra! Te muestro las partes principales del panel."},
                    {"element":"#sidebar","intro":"📋 Este es el menú principal. Desde aquí navegas a Contratos, Proveedores, Transporte, Clientes y más.","position":"right"},
                    {"element":".dash-card","intro":"📊 Estas tarjetas resumen los números clave del negocio: contratos activos, pagos del mes y gastos.","position":"bottom"},
                    {"element":".nav-profile","intro":"👤 Aquí ves tu usuario. Puedes acceder a tus datos o cerrar sesión.","position":"left"}
                ]'>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
     <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>Dashboard de Control para Gestión de Contratos, Pagos y Operaciones. Visualiza métricas clave, accesos rápidos a módulos principales y alertas para mantener un seguimiento eficiente de las actividades comerciales.
    </p>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="dash-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Contratos Registrados</div>
                        <div class="metric-number mt-4">{{ $contratosActivos }}</div>
                        <div class="metric-help">Contratos activos en el sistema</div>
                    </div>
                    <div class="dash-icon icon-blue">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dash-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="metric-label" id="tituloGastoExtra">Gastos Extras Pagados</div>
                        <div class="metric-number mt-3" id="montoGastoExtra">Bs. {{ number_format($gastosExtrasPagadosMes, 2) }}</div>
                        <div class="metric-help" id="ayudaGastoExtra">Movimiento operativo mensual</div>
                    </div>
                    <div class="dash-icon icon-green"><i class="bi bi-cash-coin"></i></div>
                </div>
                <div class="mini-selector">
                    <button type="button" class="mini-option active" onclick="cambiarGastosExtras('pagado', this)"> Pagado</button>
                    <button type="button" class="mini-option" onclick="cambiarGastosExtras('pendiente', this)">Pendiente</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dash-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="metric-label" id="tituloProveedor">Pagos a Proveedores</div>
                        <div class="metric-number mt-3" id="montoProveedor">Bs. {{ number_format($pagosProveedorPagadosMes, 2) }}</div>
                        <div class="metric-help" id="ayudaProveedor">Pagos realizados este mes</div>
                    </div>
                    <div class="dash-icon icon-purple"><i class="bi bi-cash-stack"></i></div>
                </div>
                <div class="mini-selector">
                    <button type="button" class="mini-option active" onclick="cambiarProveedor('pagado', this)"> Pagado</button>
                    <button type="button" class="mini-option" onclick="cambiarProveedor('pendiente', this)"> Pendiente</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dash-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="metric-label" id="tituloCamiones">Camiones Transbordados</div>
                        <div class="metric-number mt-3" id="cantidadCamiones">{{ $camionesTransbordado }}</div>
                        <div class="metric-help" id="ayudaCamiones">Según seguimiento de cargas</div>
                    </div>
                    <div class="dash-icon icon-orange"><i class="bi bi-truck"></i></div>
                </div>
                <div class="mini-selector">
                    <button type="button" class="mini-option active" onclick="cambiarCamiones('transbordado', this)">Transbordado</button>
                    <button type="button" class="mini-option" onclick="cambiarCamiones('en_ruta', this)">En ruta</button>
                    <button type="button" class="mini-option"onclick="cambiarCamiones('descargado', this)">Descargado</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 justify-content-center">
        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('pagos.proveedores.index') }}"
               class="quick-access-card quick-access-blue">
                <div class="d-flex align-items-center gap-3">
                    <div class="quick-access-icon"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="quick-access-title">Pagos a Proveedores</div>
                        <small class="quick-access-subtitle">Gestión de pagos</small>
                    </div>
                </div><i class="bi bi-arrow-right-short quick-access-arrow"></i>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('gastos_extras.index') }}"class="quick-access-card quick-access-green">
                <div class="d-flex align-items-center gap-3">
                    <div class="quick-access-icon"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="quick-access-title">Gastos Extras</div>
                        <small class="quick-access-subtitle">Registro operativo</small>
                    </div>
                </div><i class="bi bi-arrow-right-short quick-access-arrow"></i>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <a href="{{ route('pagos.clientes.index') }}"class="quick-access-card quick-access-orange">
                <div class="d-flex align-items-center gap-3">
                    <div class="quick-access-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="quick-access-title">Cobros a Clientes</div>
                        <small class="quick-access-subtitle">Control de ingresos</small>
                    </div>
                </div>
                <i class="bi bi-arrow-right-short quick-access-arrow"></i>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="#" class="quick-access-card quick-access-gray">
                <div class="d-flex align-items-center gap-3">
                    <div class="quick-access-icon"><i class="bi bi-bar-chart-line"></i></div>
                    <div><div class="quick-access-title">Reportes</div>
                        <small class="quick-access-subtitle">Estadísticas y análisis</small>
                    </div>
                </div>
                <i class="bi bi-arrow-right-short quick-access-arrow"></i>
            </a>
        </div>

    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="section-card p-4 h-100">
                <h5 class="panel-title mb-4">Contratos Recientes</h5>
                @forelse($contratosRecientes as $contrato)
                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $contrato->numero_contrato ?? 'SIN NÚMERO' }}</h6>
                            <span class="text-muted">{{ $contrato->proveedor->nombre ?? 'Sin proveedor' }}</span>
                        </div>
                        <span class="badge badge-soft-primary px-3 py-2">Registrado</span>
                    </div>
                @empty
                    <div class="text-center text-muted py-4"><i class="bi bi-info-circle"></i>No hay contratos registrados.</div>
                @endforelse
            </div>
        </div>
        <div class="col-md-6">
            <div class="section-card p-4 h-100">
                <h5 class="panel-title mb-4">Gastos Extras Pendientes</h5>
                @forelse($pagosPendientes as $gasto)
                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $gasto->concepto }}</h6>
                            <span class="text-muted">{{ $gasto->contrato->numero_contrato ?? 'Sin contrato' }} - {{ $gasto->contrato->proveedor->nombre ?? 'Sin proveedor' }}</span>
                        </div>
                        <div class="text-end">
                            <h6 class="fw-bold mb-2">Bs. {{ number_format($gasto->monto_bolivianos, 2) }}</h6>
                            <span class="badge badge-soft-warning px-3 py-2">Pendiente</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4"><i class="bi bi-check-circle"></i>No hay gastos extras pendientes.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-8">
            <div class="section-card p-4">
                <h5 class="panel-title mb-4">Gastos Extras Pagados por Categoría</h5>
                @forelse($gastosPorCategoria as $categoria)
                    <div class="item-box d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $categoria->categoria }}</h6>
                            <span class="text-muted">Total pagado acumulado</span>
                        </div>
                        <h6 class="fw-bold mb-0">Bs. {{ number_format($categoria->total, 2) }}</h6>
                    </div>
                @empty
                    <div class="text-center text-muted py-4"><i class="bi bi-bar-chart"></i>No hay gastos pagados por categoría.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-4">
            <div class="section-card p-4">
                <h5 class="panel-title mb-4">Alertas del Sistema</h5>
                @if($pagosPendientesCantidad > 0)
                    <div class="alert alert-warning border-0"><i class="bi bi-exclamation-triangle me-1"></i>Tienes {{ $pagosPendientesCantidad }} gastos extras pendientes.</div>
                @else
                    <div class="alert alert-success border-0"><i class="bi bi-check-circle me-1"></i>No tienes gastos extras pendientes.
                    </div>
                @endif

                @if($gastosPendientesTotal > 0)
                    <div class="alert alert-danger border-0"><i class="bi bi-cash-stack me-1"></i>Total en gastos extras pendiente: Bs. {{ number_format($gastosPendientesTotal, 2) }}</div>
                @endif

                @if($pagosProveedorPendientesMes > 0)
                    <div class="alert alert-primary border-0"><i class="bi bi-box-seam me-1"></i>Pagos a proveedores pendientes este mes: Bs. {{ number_format($pagosProveedorPendientesMes, 2) }}</div>
                @endif
                <div class="alert alert-info border-0 mb-0"><i class="bi bi-bank me-1"></i>{{ $cuentasActivas }} cuentas bancarias registradas.</div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="section-card p-4">
                <h5 class="panel-title mb-4">Últimos Gastos Extras Pagados</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Contrato</th>
                                <th>Proveedor</th>
                                <th>Categoría</th>
                                <th>Concepto</th>
                                <th>Monto BOB</th>
                                <th>Método</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosPagos as $pago)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}</td>
                                    <td>{{ $pago->contrato->numero_contrato ?? '-' }}</td>
                                    <td>{{ $pago->contrato->proveedor->nombre ?? '-' }}</td>
                                    <td><span class="badge bg-primary">{{ $pago->categoria }}</span></td>
                                    <td>{{ $pago->concepto }}</td>
                                    <td><strong>Bs. {{ number_format($pago->monto_bolivianos, 2) }}</strong></td>
                                    <td>{{ $pago->metodo_pago ?? '-' }}</td>
                                </tr>
                            @empty

                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay gastos extras pagados registrados.</td>
                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Ranking -->
<div class="col-md-12 mt-4">
    <div class="section-card p-4">
        <h5 class="panel-title mb-4">
            <i class="bi bi-trophy me-1"></i> Ranking de Toneladas Vendidas
        </h5>

        <form method="GET" action="{{ route('home') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="fecha_inicio" class="form-control"
                       value="{{ request('fecha_inicio') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="fecha_fin" class="form-control"
                       value="{{ request('fecha_fin') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_id" class="form-select">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedoresRanking as $proveedor)
                        <option value="{{ $proveedor->id }}"
                            {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos los clientes</option>
                    @foreach($clientesRanking as $cliente)
                        <option value="{{ $cliente->id }}"
                            {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('home') }}" class="btn btn-secondary">
                    Limpiar
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Proveedor</th>
                        <th>Cliente</th>
                        <th class="text-end">Toneladas vendidas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingToneladas as $index => $item)
                        <tr>
                            <td>
                                <span class="badge bg-dark">{{ $index + 1 }}</span>
                            </td>
                            <td>{{ $item->proveedor_nombre ?? 'Sin proveedor' }}</td>
                            <td>{{ $item->cliente_nombre ?? 'Sin cliente' }}</td>
                            <td class="text-end">
                                <strong>{{ number_format($item->total_toneladas, 2) }} TN</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No hay toneladas vendidas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('scripts')

<script>
const gastosExtrasMes = {
    pagado: {
        titulo: 'Gastos Extras Pagados',
        monto: 'Bs. {{ number_format($gastosExtrasPagadosMes, 2) }}',
        ayuda: 'Movimiento operativo mensual'
    },

    pendiente: {
        titulo: 'Gastos Extras Pendientes',
        monto: 'Bs. {{ number_format($gastosExtrasPendientesMes, 2) }}',
        ayuda: 'Pendientes operativos del mes'
    }
};

function cambiarGastosExtras(tipo, boton)
{
    document.getElementById('tituloGastoExtra').innerText = gastosExtrasMes[tipo].titulo;
    document.getElementById('montoGastoExtra').innerText = gastosExtrasMes[tipo].monto;
    document.getElementById('ayudaGastoExtra').innerText = gastosExtrasMes[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn => btn.classList.remove('active'));
    boton.classList.add('active');
}

const pagosProveedorMes = {
    pagado: {
        titulo: 'Pagos a Proveedores',
        monto: 'Bs. {{ number_format($pagosProveedorPagadosMes, 2) }}',
        ayuda: 'Pagos realizados este mes'
    },

    pendiente: {
        titulo: 'Pagos Pendientes',
        monto: 'Bs. {{ number_format($pagosProveedorPendientesMes, 2) }}',
        ayuda: 'Pendientes financieros con proveedores'
    }
};

function cambiarProveedor(tipo, boton)
{
    document.getElementById('tituloProveedor').innerText = pagosProveedorMes[tipo].titulo;
    document.getElementById('montoProveedor').innerText = pagosProveedorMes[tipo].monto;
    document.getElementById('ayudaProveedor').innerText = pagosProveedorMes[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn => btn.classList.remove('active'));
    boton.classList.add('active');
}

const camionesEstado = {
    transbordado: {
        titulo: 'Camiones Transbordados',
        cantidad: '{{ $camionesTransbordado }}',
        ayuda: 'Camiones actualmente en transbordo'
    },

    en_ruta: {
        titulo: 'Camiones en Ruta',
        cantidad: '{{ $camionesEnRuta }}',
        ayuda: 'Camiones actualmente en ruta'
    },

    descargado: {
        titulo: 'Camiones Descargados',
        cantidad: '{{ $camionesDescargado }}',
        ayuda: 'Camiones con descarga registrada'
    }
};

function cambiarCamiones(tipo, boton)
{
    document.getElementById('tituloCamiones').innerText = camionesEstado[tipo].titulo;
    document.getElementById('cantidadCamiones').innerText = camionesEstado[tipo].cantidad;
    document.getElementById('ayudaCamiones').innerText = camionesEstado[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn => btn.classList.remove('active'));
    boton.classList.add('active');
}
</script>
@endsection