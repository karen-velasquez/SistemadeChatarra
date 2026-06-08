@extends('layouts.app')

@section('content')
<div class="dashboard-header mb-3 p-4 rounded-4 shadow-sm bg-white border">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-2"><i class="bi bi-speedometer2 me-1"></i> Dashboard Operativo</span>
            <h1 class="fw-bold mb-1">{{ ucfirst(now()->locale('es')->translatedFormat('F Y')) }}</h1>
            <p class="text-muted small mb-0">Gestión de contratos, tesorería, pagos, proveedores, clientes y seguimiento operativo.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="d-inline-block bg-light rounded-4 px-4 py-2">
                <div class="small text-muted">Periodo Actual</div>
                <div class="fw-bold fs-5 text-primary">{{ ucfirst(now()->locale('es')->translatedFormat('F')) }}</div>
                <div class="text-muted small">{{ now()->year }}</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm btn-iniciar-tour ms-2" data-steps='[
                {"intro":"👋 ¡Bienvenido al Sistema de Gestión de Chatarra!"},
                {"element":"#sidebar","intro":"📋 Este es el menú principal.","position":"right"},
                {"element":".dash-card","intro":"📊 Estas tarjetas resumen los indicadores clave.","position":"bottom"},
                {"element":".nav-profile","intro":"👤 Aquí ves tu usuario.","position":"left"}
            ]'><i class="bi bi-question-circle"></i></button>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Contratos</div>
                    <div class="metric-number mt-2">{{ $contratosActivos }}</div>
                    <div class="metric-help">Activos: {{ $contratosActivos }} | Concluidos: {{ $contratosConcluidos }}</div>
                </div>
                <div class="dash-icon icon-blue"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="metric-label" id="tituloGastoExtra">Gastos Extras Pagados</div>
                    <div class="metric-number mt-2" id="montoGastoExtra">Bs. {{ number_format($gastosExtrasPagadosMes, 2) }}</div>
                    <div class="metric-help" id="ayudaGastoExtra">Movimiento operativo mensual</div>
                </div>
                <div class="dash-icon icon-green"><i class="bi bi-cash-coin"></i></div>
            </div>
            <div class="mini-selector">
                <button type="button" class="mini-option active" onclick="cambiarGastosExtras('pagado', this)">Pagado</button>
                <button type="button" class="mini-option" onclick="cambiarGastosExtras('pendiente', this)">Pendiente</button>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="metric-label" id="tituloProveedor">Pagos a Proveedores</div>
                    <div class="metric-number mt-2" id="montoProveedor">Bs. {{ number_format($pagosProveedorPagadosMes, 2) }}</div>
                    <div class="metric-help" id="ayudaProveedor">Pagos realizados este mes</div>
                </div>
                <div class="dash-icon icon-purple"><i class="bi bi-cash-stack"></i></div>
            </div>
            <div class="mini-selector">
                <button type="button" class="mini-option active" onclick="cambiarProveedor('pagado', this)">Pagado</button>
                <button type="button" class="mini-option" onclick="cambiarProveedor('pendiente', this)">Pendiente</button>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="metric-label" id="tituloCamiones">Camiones Transbordados</div>
                    <div class="metric-number mt-2" id="cantidadCamiones">{{ $camionesTransbordado }}</div>
                    <div class="metric-help" id="ayudaCamiones">Según seguimiento de cargas</div>
                </div>
                <div class="dash-icon icon-orange"><i class="bi bi-truck"></i></div>
            </div>
            <div class="mini-selector">
                <button type="button" class="mini-option active" onclick="cambiarCamiones('transbordado', this)">Transbordado</button>
                <button type="button" class="mini-option" onclick="cambiarCamiones('en_ruta', this)">En ruta</button>
                <button type="button" class="mini-option" onclick="cambiarCamiones('descargado', this)">Descargado</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Saldo Tesorería</div>
                    <div class="metric-number mt-2">Bs. {{ number_format($saldoTesoreria, 2) }}</div>
                    <div class="metric-help">Dinero disponible</div>
                </div>
                <div class="dash-icon icon-blue"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Cobros Clientes Mes</div>
                    <div class="metric-number mt-2">Bs. {{ number_format($cobrosClientesMes, 2) }}</div>
                    <div class="metric-help">Ingresos por ventas</div>
                </div>
                <div class="dash-icon icon-green"><i class="bi bi-arrow-down-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Pagos Camiones Mes</div>
                    <div class="metric-number mt-2">Bs. {{ number_format($pagosCamionMes, 2) }}</div>
                    <div class="metric-help">Fletes y adelantos</div>
                </div>
                <div class="dash-icon icon-orange"><i class="bi bi-truck-front"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dash-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="metric-label">Utilidad del Mes</div>
                    <div class="metric-number mt-2 {{ $utilidadMes >= 0 ? 'text-success' : 'text-danger' }}">Bs. {{ number_format($utilidadMes, 2) }}</div>
                    <div class="metric-help">Cobros - costos</div>
                </div>
                <div class="dash-icon icon-purple"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3 justify-content-center">
    <div class="col-12 col-md-6 col-lg-3">
        <a href="{{ route('pagos.proveedores.index') }}" class="quick-access-card quick-access-blue">
            <div class="d-flex align-items-center gap-3">
                <div class="quick-access-icon"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="quick-access-title">Pagos a Proveedores</div>
                    <small class="quick-access-subtitle">Gestión de pagos</small>
                </div>
            </div>
            <i class="bi bi-arrow-right-short quick-access-arrow"></i>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <a href="{{ route('gastos_extras.index') }}" class="quick-access-card quick-access-green">
            <div class="d-flex align-items-center gap-3">
                <div class="quick-access-icon"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="quick-access-title">Gastos Extras</div>
                    <small class="quick-access-subtitle">Registro operativo</small>
                </div>
            </div>
            <i class="bi bi-arrow-right-short quick-access-arrow"></i>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <a href="{{ route('pagos.clientes.index') }}" class="quick-access-card quick-access-orange">
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
        <a href="{{ route('reportes.capital_utilidad') }}" class="quick-access-card quick-access-gray">
            <div class="d-flex align-items-center gap-3">
                <div class="quick-access-icon"><i class="bi bi-bar-chart-line"></i></div>
                <div>
                    <div class="quick-access-title">Reportes</div>
                    <small class="quick-access-subtitle">Capital y utilidad</small>
                </div>
            </div>
            <i class="bi bi-arrow-right-short quick-access-arrow"></i>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="section-card p-3 h-100">
            <h5 class="panel-title mb-3">Contratos Recientes</h5>
            @forelse($contratosRecientes as $contrato)
                <div class="item-box d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $contrato->numero_contrato ?? 'SIN NÚMERO' }}</h6>
                        <span class="text-muted">{{ $contrato->proveedor->nombre ?? 'Sin proveedor' }} / {{ $contrato->cliente->nombre ?? 'Sin cliente' }}</span>
                    </div>
                    <span class="badge badge-soft-primary px-3 py-2">{{ $contrato->estado ?? 'Registrado' }}</span>
                </div>
            @empty
                <div class="text-center text-muted py-3"><i class="bi bi-info-circle"></i> No hay contratos registrados.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="section-card p-3 h-100">
            <h5 class="panel-title mb-3">Gastos Extras Pendientes</h5>
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
                <div class="text-center text-muted py-3"><i class="bi bi-check-circle"></i> No hay gastos extras pendientes.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="section-card p-3 h-100">
            <h5 class="panel-title mb-3">Gastos Extras Pagados por Categoría</h5>
            @forelse($gastosPorCategoria as $categoria)
                <div class="item-box d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $categoria->categoria }}</h6>
                        <span class="text-muted">Total pagado acumulado</span>
                    </div>
                    <h6 class="fw-bold mb-0">Bs. {{ number_format($categoria->total, 2) }}</h6>
                </div>
            @empty
                <div class="text-center text-muted py-3"><i class="bi bi-bar-chart"></i> No hay gastos pagados por categoría.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="section-card p-3 h-100">
            <h5 class="panel-title mb-3">Tesorería por Cuenta</h5>
            @forelse($cuentasResumen as $cuenta)
                <div class="item-box d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $cuenta->nombre_cuenta }}</h6>
                        <span class="text-muted">{{ $cuenta->banco->nombre ?? 'Sin banco' }} - {{ $cuenta->moneda }}</span>
                    </div>
                    <div class="text-end">
                        <h6 class="fw-bold mb-1">Bs. {{ number_format($cuenta->saldo_actual, 2) }}</h6>
                        <span class="badge badge-soft-primary">Disponible</span>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3"><i class="bi bi-bank"></i> No hay cuentas empresa registradas.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-4">
        <div class="section-card p-3">
            <h5 class="panel-title mb-3">Alertas del Sistema</h5>
            @if($pagosPendientesCantidad > 0)
                <div class="alert alert-warning border-0"><i class="bi bi-exclamation-triangle me-1"></i>Tienes {{ $pagosPendientesCantidad }} gastos extras pendientes.</div>
            @else
                <div class="alert alert-success border-0"><i class="bi bi-check-circle me-1"></i>No tienes gastos extras pendientes.</div>
            @endif
            @if($gastosPendientesTotal > 0)
                <div class="alert alert-danger border-0"><i class="bi bi-cash-stack me-1"></i>Total pendiente: Bs. {{ number_format($gastosPendientesTotal, 2) }}</div>
            @endif
            @if($pagosProveedorPendientesMes > 0)
                <div class="alert alert-primary border-0"><i class="bi bi-box-seam me-1"></i>Proveedor pendiente: Bs. {{ number_format($pagosProveedorPendientesMes, 2) }}</div>
            @endif
            <div class="alert {{ $utilidadMes < 0 ? 'alert-danger' : 'alert-success' }} border-0">
                <i class="bi {{ $utilidadMes < 0 ? 'bi-graph-down-arrow' : 'bi-graph-up-arrow' }} me-1"></i>
                Utilidad del mes: Bs. {{ number_format($utilidadMes, 2) }}
            </div>
            <div class="alert {{ $saldoTesoreria <= 0 ? 'alert-danger' : 'alert-info' }} border-0">
                <i class="bi bi-wallet2 me-1"></i>Saldo tesorería: Bs. {{ number_format($saldoTesoreria, 2) }}
            </div>
            <div class="alert alert-secondary border-0"><i class="bi bi-box-seam me-1"></i>Declaradas: {{ number_format($toneladasDeclaradasMes, 3) }} TN</div>
            <div class="alert alert-secondary border-0 mb-0"><i class="bi bi-check2-circle me-1"></i>Entregadas: {{ number_format($toneladasEntregadasMes, 3) }} TN</div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="section-card p-3 h-100">
            <h5 class="panel-title mb-3">Resumen Operativo del Mes</h5>
            <div class="row g-3">
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Ingresos del mes</small><h5 class="fw-bold text-success mb-0">Bs. {{ number_format($ingresosMes, 2) }}</h5></div></div>
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Egresos del mes</small><h5 class="fw-bold text-danger mb-0">Bs. {{ number_format($egresosMes, 2) }}</h5></div></div>
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Capital inicial</small><h5 class="fw-bold mb-0">Bs. {{ number_format($capitalInicial, 2) }}</h5></div></div>
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Proveedores registrados</small><h5 class="fw-bold mb-0">{{ $proveedoresActivos }}</h5></div></div>
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Clientes registrados</small><h5 class="fw-bold mb-0">{{ $clientesActivos }}</h5></div></div>
                <div class="col-md-6"><div class="item-box"><small class="text-muted">Cuentas empresa activas</small><h5 class="fw-bold mb-0">{{ $cuentasActivas }}</h5></div></div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="section-card p-3">
            <h5 class="panel-title mb-3">Últimos Movimientos de Tesorería</h5>
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cuenta</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto Bs</th>
                            <th>Código</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientosRecientes as $mov)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $mov->cuentaEmpresa->nombre_cuenta ?? '-' }}<br><small class="text-muted">{{ $mov->cuentaEmpresa->banco->nombre ?? '' }}</small></td>
                                <td><span class="badge {{ $mov->tipo == 'ingreso' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($mov->tipo) }}</span></td>
                                <td>{{ \App\Models\Movimiento::categoriaLabel($mov->categoria) }}</td>
                                <td>{{ $mov->concepto }}</td>
                                <td class="text-end"><strong>Bs. {{ number_format($mov->monto_bolivianos, 2) }}</strong></td>
                                <td>{{ $mov->codigo_seguimiento ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No hay movimientos registrados.</td></tr>
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
const gastosExtrasMes={
    pagado:{titulo:'Gastos Extras Pagados',monto:'Bs. {{ number_format($gastosExtrasPagadosMes, 2) }}',ayuda:'Movimiento operativo mensual'},
    pendiente:{titulo:'Gastos Extras Pendientes',monto:'Bs. {{ number_format($gastosExtrasPendientesMes, 2) }}',ayuda:'Pendientes operativos del mes'}
};
function cambiarGastosExtras(tipo,boton){
    document.getElementById('tituloGastoExtra').innerText=gastosExtrasMes[tipo].titulo;
    document.getElementById('montoGastoExtra').innerText=gastosExtrasMes[tipo].monto;
    document.getElementById('ayudaGastoExtra').innerText=gastosExtrasMes[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn=>btn.classList.remove('active'));
    boton.classList.add('active');
}
const pagosProveedorMes={
    pagado:{titulo:'Pagos a Proveedores',monto:'Bs. {{ number_format($pagosProveedorPagadosMes, 2) }}',ayuda:'Pagos realizados este mes'},
    pendiente:{titulo:'Pagos Pendientes',monto:'Bs. {{ number_format($pagosProveedorPendientesMes, 2) }}',ayuda:'Pendientes financieros con proveedores'}
};
function cambiarProveedor(tipo,boton){
    document.getElementById('tituloProveedor').innerText=pagosProveedorMes[tipo].titulo;
    document.getElementById('montoProveedor').innerText=pagosProveedorMes[tipo].monto;
    document.getElementById('ayudaProveedor').innerText=pagosProveedorMes[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn=>btn.classList.remove('active'));
    boton.classList.add('active');
}
const camionesEstado={
    transbordado:{titulo:'Camiones Transbordados',cantidad:'{{ $camionesTransbordado }}',ayuda:'Camiones actualmente en transbordo'},
    en_ruta:{titulo:'Camiones en Ruta',cantidad:'{{ $camionesEnRuta }}',ayuda:'Camiones actualmente en ruta'},
    descargado:{titulo:'Camiones Descargados',cantidad:'{{ $camionesDescargado }}',ayuda:'Camiones con descarga registrada'}
};
function cambiarCamiones(tipo,boton){
    document.getElementById('tituloCamiones').innerText=camionesEstado[tipo].titulo;
    document.getElementById('cantidadCamiones').innerText=camionesEstado[tipo].cantidad;
    document.getElementById('ayudaCamiones').innerText=camionesEstado[tipo].ayuda;
    boton.parentElement.querySelectorAll('.mini-option').forEach(btn=>btn.classList.remove('active'));
    boton.classList.add('active');
}
</script>
@endsection