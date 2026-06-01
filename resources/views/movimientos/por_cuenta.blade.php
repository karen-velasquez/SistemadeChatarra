@extends('layouts.app')
@section('titulo', $cuenta->empresa->nombre . ' — ' . $cuenta->nombre_cuenta)
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>{{ strtoupper($cuenta->empresa->nombre) }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('empresas.index') }}">Empresas</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('empresas.cuentas', $cuenta->empresa->uuid) }}">Cuentas y Movimientos</a></li>
                    <li class="breadcrumb-item active">{{ $cuenta->nombre_cuenta }}</li>
                </ol>
            </nav>
        </div>
        @can('empresas.create')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
            <i class="bi bi-plus-lg"></i> Registrar Movimiento
        </button>
        @endcan
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Movimientos de cuenta</strong> — Historial completo de ingresos y egresos de esta cuenta.
            Las filas en <span class="badge bg-success">verde</span> son ingresos y las <span class="badge bg-danger">rojas</span> son egresos.
            Los movimientos generados automáticamente por pagos del sistema no se pueden eliminar desde aquí;
            debes hacerlo desde el módulo correspondiente (Pagos a Clientes, Proveedores o Camiones).
            Para registrar un movimiento manual (ajuste, gasto puntual, etc.) usa el botón <strong>Registrar Movimiento</strong>.
        </div>
    </div>

    {{-- Info de cuenta --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Empresa</div>
                    <div class="fw-bold">{{ $cuenta->empresa->nombre }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Saldo Actual</div>
                    <div class="fs-5 fw-bold {{ $cuenta->saldo_actual >= 0 ? 'text-primary' : 'text-danger' }}">
                        {{ $cuenta->moneda }} {{ number_format($cuenta->saldo_actual, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Total Ingresos</div>
                    <div class="fs-5 fw-bold text-success">BOB {{ number_format($totalIngresos, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Total Egresos</div>
                    <div class="fs-5 fw-bold text-danger">BOB {{ number_format($totalEgresos, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($cuenta->banco)
    <div class="alert alert-light border mb-4 py-2">
        <span class="text-muted small">
            <i class="bi bi-bank me-1"></i><strong>Banco:</strong> {{ $cuenta->banco->nombre }}
            @if($cuenta->numero_cuenta)
                &nbsp;|&nbsp; <strong>N° Cuenta:</strong> {{ $cuenta->numero_cuenta }}
            @endif
            &nbsp;|&nbsp; <strong>Moneda:</strong> {{ $cuenta->moneda }}
            &nbsp;|&nbsp; <strong>Saldo Inicial:</strong> {{ $cuenta->moneda }} {{ number_format($cuenta->saldo_inicial, 2) }}
        </span>
    </div>
    @endif

    {{-- Movimientos --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Movimientos</h5>
            @include('movimientos._tabla', ['movimientos' => $movimientos, 'mostrarCuenta' => false, 'mostrarEliminar' => false])
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
            <form method="POST" action="{{ route('tesoreria.movimiento.store') }}">
                @csrf
                <input type="hidden" name="cuenta_empresa_id" value="{{ $cuenta->id }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Cuenta: <strong>{{ $cuenta->nombre_cuenta }}</strong>
                                ({{ $cuenta->empresa->nombre }} — {{ $cuenta->moneda }})
                            </div>
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
                                <option value="otro" selected>Otro</option>
                            </select>
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Los pagos a clientes, proveedores y camiones se registran desde sus módulos correspondientes.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" name="monto" class="form-control" required min="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Moneda</label>
                            <input type="text" class="form-control" value="{{ $cuenta->moneda }}" readonly>
                            <input type="hidden" name="moneda" value="{{ $cuenta->moneda }}">
                        </div>
                        <div class="col-md-6" id="row_tipo_cambio" style="{{ $cuenta->moneda === 'BOB' ? 'display:none' : '' }}">
                            <label class="form-label">Tipo de Cambio <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.0001" name="tipo_cambio" id="tipo_cambio_mov" class="form-control"
                                   value="1" {{ $cuenta->moneda === 'BOB' ? '' : 'required' }} min="0.0001">
                        </div>
                        @if($cuenta->moneda === 'BOB')
                        <input type="hidden" name="tipo_cambio" value="1">
                        @endif
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
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Plugin para ordenar fechas en formato dd/mm/yyyy
$.fn.dataTable.ext.type.order['date-eu-pre'] = function (d) {
    if (!d || d === '') return 0;
    var parts = d.split('/');
    return (parts[2] * 10000) + (parts[1] * 100) + (parts[0] * 1);
};

$(document).ready(function() {
    $('#tabla_movimientos').DataTable({
        language: {
            processing:  "Procesando...",
            lengthMenu:  'Mostrar <select><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="-1">Todos</option></select> registros',
            search:      "Buscar:",
            zeroRecords: "No se encontraron resultados",
            info:        "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty:   "Mostrando 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            paginate: {
                first:    "Primero",
                last:     "Último",
                next:     "Siguiente",
                previous: "Anterior"
            }
        },
        columnDefs: [
            { type: 'date-eu', targets: 0 }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true,
        autoWidth: false
    });
});
</script>
@endsection
