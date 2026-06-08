@extends('layouts.app')
@section('titulo', 'Reportes')
@section('content')

<div class="pagetitle">
    <h1>Reporte de Capital y Utilidad</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item">Reportes</li>
            <li class="breadcrumb-item active">Capital y Utilidad</li>
        </ol>
    </nav>
</div>

<section class="section">
    <form method="GET" action="{{ route('reportes.capital_utilidad') }}" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Fecha inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Cuenta empresa</label>
            <select name="cuenta_empresa_id" class="form-select">
                <option value="todas" {{ $cuentaId == 'todas' ? 'selected' : '' }}>Todas las cuentas</option>
                @foreach($cuentas as $cuenta)
                    <option value="{{ $cuenta->id }}" {{ $cuentaId == $cuenta->id ? 'selected' : '' }}>{{ $cuenta->nombre_cuenta }} - {{ $cuenta->banco->nombre ?? 'Sin banco' }} - {{ $cuenta->moneda }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar </button>
        </div>
        <div class="col-md-12">
            <a href="{{ route('reportes.capital_utilidad.excel', request()->query()) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Descargar Excel</a>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Capital inicial</small>
                    <h4 class="fw-bold mb-0">Bs {{ number_format($capitalInicial, 2) }}</h4>
                    <small class="text-muted">Saldo inicial de cuentas empresa</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Después de pagar proveedores</small>
                    <h4 class="fw-bold text-warning mb-0">Bs {{ number_format($capitalDespuesProveedores, 2) }}</h4>
                    <small class="text-muted">Capital inicial - proveedores</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Capital final calculado</small>
                    <h4 class="fw-bold text-primary mb-0">Bs {{ number_format($capitalFinalCalculado, 2) }}</h4>
                    <small class="text-muted">Inicial + ingresos - egresos</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Saldo actual en cuentas</small>
                    <h4 class="fw-bold text-success mb-0">Bs {{ number_format($capitalActual, 2) }}</h4>
                    <small class="text-muted">Según tesorería</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Ventas / cobros clientes</small>
                    <h4 class="fw-bold text-success mb-0">Bs {{ number_format($ventasClientes, 2) }}</h4>
                    <small class="text-muted">Pagos y anticipos de clientes</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Pagos a proveedores</small>
                    <h4 class="fw-bold text-danger mb-0">Bs {{ number_format($pagosProveedores, 2) }}</h4>
                    <small class="text-muted">Capital usado para compra</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Gastos operativos</small>
                    <h4 class="fw-bold text-warning mb-0">Bs {{ number_format($pagosCamiones + $gastosExtras, 2) }}</h4>
                    <small class="text-muted">Camiones + gastos extras</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Utilidad operativa</small>
                    <h4 class="fw-bold {{ $utilidadOperativa >= 0 ? 'text-success' : 'text-danger' }} mb-0">Bs {{ number_format($utilidadOperativa, 2) }}</h4>
                    <small class="text-muted">Ventas - costos - gastos</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Otros ingresos</small>
                    <h5 class="fw-bold text-success mb-0">Bs {{ number_format($otrosIngresos, 2) }}</h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Otros egresos</small>
                    <h5 class="fw-bold text-danger mb-0">Bs {{ number_format($otrosEgresos, 2) }}</h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <small class="text-muted">Utilidad neta</small>
                    <h5 class="fw-bold {{ $utilidadNeta >= 0 ? 'text-success' : 'text-danger' }} mb-0">Bs {{ number_format($utilidadNeta, 2) }}</h5>
                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-header bg-white"><strong>Resumen por cuenta de empresa</strong></div>

        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th>Banco</th>
                        <th>Moneda</th>
                        <th class="text-end">Capital inicial</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Egresos</th>
                        <th class="text-end">Utilidad</th>
                        <th class="text-end">Saldo actual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumenPorCuenta as $r)
                        <tr>
                            <td>{{ $r['cuenta'] }}</td>
                            <td>{{ $r['banco'] }}</td>
                            <td>{{ $r['moneda'] }}</td>
                            <td class="text-end">Bs {{ number_format($r['saldo_inicial'], 2) }}</td>
                            <td class="text-end text-success">Bs {{ number_format($r['ingresos'], 2) }}</td>
                            <td class="text-end text-danger">Bs {{ number_format($r['egresos'], 2) }}</td>
                            <td class="text-end {{ $r['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">Bs {{ number_format($r['utilidad'], 2) }}</td>
                            <td class="text-end fw-bold">Bs {{ number_format($r['saldo_actual'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay información para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>  
</section>
@endsection