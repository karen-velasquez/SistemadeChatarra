@extends('layouts.app')
@section('titulo','Tesorería General')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>TESORERÍA GENERAL</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Tesorería</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                data-steps='[
                    {"intro":"💼 <b>Tesorería General</b> es la vista consolidada del dinero de <b>todas</b> las empresas y cuentas. Los movimientos se generan solos al registrar pagos y cobros."},
                    {"element":"#teso-resumen","intro":"📊 Estas tarjetas muestran el <b>saldo general</b> de todo el negocio, el total de <b>ingresos</b> (dinero que entró) y el total de <b>egresos</b> (dinero que salió).","position":"bottom"},
                    {"element":"#teso-empresas","intro":"🏢 Cada tarjeta es una <b>empresa</b> con su saldo y número de cuentas. Pulsa <b>Ver cuentas</b> para entrar a una empresa y ver el detalle de sus cuentas.","position":"top"},
                    {"element":"#teso-movimientos","intro":"📋 Los <b>últimos movimientos</b> de todas las cuentas: cada fila es un ingreso o egreso con su fecha, concepto, cuenta y monto.","position":"top"},
                    {"element":"#teso-nota","intro":"✍️ ¿Quieres registrar un movimiento <b>manual</b> (un gasto o ajuste)? Entra a la cuenta específica desde <b>Empresas y Cuentas</b> y usa ahí el botón Registrar Movimiento.","position":"bottom"}
                ]'>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small" id="teso-nota">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Tesorería General</strong> — Vista consolidada del dinero de todas las empresas y cuentas registradas.
            Los movimientos de ingresos y egresos se generan automáticamente al registrar pagos a clientes, proveedores y camiones.
            Para registrar un movimiento manual (gastos varios, ajustes, etc.), ingresa a la cuenta específica desde
            <a href="{{ route('empresas.index') }}">Gestión de Empresas</a> y usa el botón <strong>Registrar Movimiento</strong> dentro de la cuenta.
        </div>
    </div>

    {{-- Cards resumen --}}
    <div class="row g-3 mb-4" id="teso-resumen">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-wallet2 fs-5 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Saldo General</div>
                        <div class="fs-5 fw-bold text-primary">BOB {{ number_format($saldoGeneral, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-arrow-down-circle fs-5 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Ingresos</div>
                        <div class="fs-5 fw-bold text-success">BOB {{ number_format($totalIngresos, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-arrow-up-circle fs-5 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Egresos</div>
                        <div class="fs-5 fw-bold text-danger">BOB {{ number_format($totalEgresos, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Empresas --}}
    <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:0.75rem;letter-spacing:.08em">
        <i class="bi bi-building me-1"></i> Empresas
    </h6>
    <div class="row g-3 mb-4" id="teso-empresas">
        @forelse($empresas as $empresa)
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-building text-secondary"></i>
                            <span class="fw-bold">{{ $empresa->nombre }}</span>
                        </div>
                        @if($empresa->nit)
                        <small class="text-muted">NIT: {{ $empresa->nit }}</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Saldo total</div>
                        <div class="fs-4 fw-bold {{ $empresa->cuentas->sum('saldo_actual') >= 0 ? 'text-success' : 'text-danger' }}">
                            BOB {{ number_format($empresa->cuentas->sum('saldo_actual'), 2, ',', '.') }}
                        </div>
                        <small class="text-muted">{{ $empresa->cuentas->count() }} {{ $empresa->cuentas->count() === 1 ? 'cuenta' : 'cuentas' }}</small>
                    </div>
                    <a href="{{ route('empresas.cuentas', $empresa->uuid) }}" class="btn btn-outline-primary btn-sm w-100">
                        Ver cuentas <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                    No hay empresas registradas.
                    <a href="{{ route('empresas.index') }}" class="d-block mt-2">Ir a Empresas</a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Últimos movimientos --}}
    <div class="card border-0 shadow-sm" id="teso-movimientos">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h5 class="card-title mb-0">Últimos Movimientos</h5>
                <a href="{{ route('prestamos_internos.index') }}" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-arrow-left-right"></i> Préstamos Internos
                </a>
            </div>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Resumen general de todas las cuentas. Para ver el detalle de una cuenta, ingresa desde las cards de empresa.
            </p>
            @include('movimientos._tabla', ['movimientos' => $ultimosMovimientos, 'mostrarCuenta' => true, 'mostrarEliminar' => false])
        </div>
    </div>

</section>

{{-- MODAL MOVIMIENTO MANUAL --}}
<div class="modal fade" id="modalMovimiento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Registrar Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMovimiento" method="POST" action="{{ route('tesoreria.movimiento.store') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenMovimiento" value="{{ $idempotencyToken ?? '' }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Empresa / Cuenta <span class="text-danger">(*)</span></label>
                            <select name="cuenta_empresa_id" class="form-select" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}">{{ $cuenta->nombre_cuenta }} ({{ $cuenta->moneda }})</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo <span class="text-danger">(*)</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría <span class="text-danger">(*)</span></label>
                            <select name="categoria" class="form-select" required>
                                <option value="pago_cliente">Pago de Cliente</option>
                                <option value="pago_proveedor">Pago a Proveedor</option>
                                <option value="pago_camion">Pago a Camión</option>
                                <option value="gasto_extra">Gasto Extra</option>
                                <option value="pago_sueldo">Pago de Sueldo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" name="monto" class="form-control" required min="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda <span class="text-danger">(*)</span></label>
                            <select name="moneda" class="form-select" required>
                                <option value="BOB" selected>BOB</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="BRL">BRL</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Cambio</label>
                            <input type="number" step="0.0001" name="tipo_cambio" class="form-control" value="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto <span class="text-danger">(*)</span></label>
                            <input type="text" name="concepto" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnMovimiento">Registrar</button>
                </div>
            </form>
            <script>
            document.getElementById('formMovimiento').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnMovimiento');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });
            </script>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// La tabla ya llega ordenada por fecha desde el backend (orderByDesc en el controlador).
// DataTables se quitó: su envoltorio rompía el sticky de la cabecera al hacer scroll,
// igual que en Seguimiento de Cargas. Sin filtro propio que lo reemplace, se pierde la
// búsqueda y el reordenar por columna, pero se gana la cabecera fija.
</script>
@endsection
