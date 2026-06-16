@extends('layouts.app')
@section('titulo', 'Cuentas - ' . $empresa->nombre)
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>{{ strtoupper($empresa->nombre) }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('empresas.index') }}">Empresas</a></li>
                    <li class="breadcrumb-item active">Cuentas y Movimientos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"💼 Esta es la vista de <b>Cuentas y Movimientos</b> de esta empresa. Aquí ves todas sus cuentas bancarias, sus saldos y sus últimos movimientos."},
                        {"element":"#emp-nota","intro":"ℹ️ Los movimientos generados por pagos (a clientes, proveedores, camiones) aparecen <b>bloqueados</b>: solo se anulan desde su módulo de origen. Los movimientos manuales sí los registras y eliminas aquí.","position":"bottom"},
                        {"element":"#emp-cuentas","intro":"💳 Cada tarjeta es una <b>cuenta</b> de la empresa con su banco y saldo. Pulsa <b>Ver movimientos</b> para entrar al detalle de esa cuenta y registrar movimientos manuales.","position":"top"},
                        {"element":"#emp-movimientos","intro":"📋 Aquí ves los <b>últimos movimientos</b> de todas las cuentas de esta empresa juntas.","position":"top"},
                        {"element":"#btnNuevaCuentaEmp","intro":"➕ Con <b>Nueva Cuenta</b> agregas una cuenta bancaria a esta empresa. El formulario tiene su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('empresas.create')
            <button id="btnNuevaCuentaEmp" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCuenta">
                <i class="bi bi-plus-lg"></i> Nueva Cuenta
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small" id="emp-nota">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Cuentas y Movimientos</strong> — Vista de todas las cuentas de esta empresa y sus últimos movimientos consolidados.
            Haz clic en <strong>Ver movimientos</strong> dentro de cada cuenta para ver el detalle y registrar movimientos manuales en esa cuenta específica.
            Los movimientos generados por pagos a clientes, proveedores o camiones aparecen bloqueados y solo pueden eliminarse desde su módulo de origen.
        </div>
    </div>

    {{-- Resumen de cuentas --}}
    @php $saldoEmpresa = $empresa->cuentas->sum('saldo_actual'); @endphp
    <div class="row mb-4" id="emp-cuentas">
        @forelse($empresa->cuentas->sortByDesc('saldo_actual') as $cuenta)
        <div class="col-12 col-sm-6 col-xl-3 mb-3">
            <div class="card border-0 shadow-sm {{ !$cuenta->activo ? 'opacity-50' : '' }}">
                <div class="card-body pt-4">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="small fw-semibold text-dark d-flex align-items-center gap-1">
                            <i class="bi bi-wallet2 text-primary"></i>
                            {{ $cuenta->nombre_cuenta }}
                            @if(!$cuenta->activo)
                                <span class="badge bg-secondary" style="font-size:.6rem">Inactiva</span>
                            @endif
                        </div>
                    </div>
                    @if($cuenta->banco)
                    <div class="text-muted" style="font-size:.75rem">
                        <i class="bi bi-bank me-1"></i>{{ $cuenta->banco->nombre }}
                        @if($cuenta->numero_cuenta)
                            &nbsp;·&nbsp;<span class="font-monospace">{{ $cuenta->numero_cuenta }}</span>
                        @endif
                    </div>
                    @endif
                    <div class="fs-5 fw-bold {{ $cuenta->saldo_actual >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-2">
                        {{ $cuenta->moneda }} {{ number_format($cuenta->saldo_actual, 2, ',', '.') }}
                    </div>
                    <a href="{{ route('tesoreria.cuenta', $cuenta->uuid) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-list-ul"></i> Ver movimientos
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">Esta empresa no tiene cuentas registradas.</div>
        </div>
        @endforelse
    </div>

    {{-- Últimos movimientos de todas las cuentas --}}
    <div class="card" id="emp-movimientos">
        <div class="card-body">
            <h5 class="card-title">Últimos Movimientos</h5>
            @include('movimientos._tabla', [
                'movimientos'     => $movimientos,
                'mostrarCuenta'   => true,
                'mostrarEliminar' => false,
            ])
        </div>
    </div>

</section>

{{-- MODAL NUEVA CUENTA --}}
<div class="modal fade" id="modalCuenta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header position-relative">
                <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Nueva Cuenta</h5>
                <div class="d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-2 me-3">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalCuenta"
                            data-steps='[
                                {"intro":"📝 Registra una <b>cuenta bancaria</b> para esta empresa. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#nombre_cuenta_cuentas","intro":"🏷️ <b>Nombre de la Cuenta</b>: un nombre que la identifique (ej: CUENTA PRINCIPAL BOB).","position":"bottom"},
                                {"element":"#num_cuenta_cuentas","intro":"🔢 <b>N° de Cuenta</b>: solo dígitos, hasta 20.","position":"bottom"},
                                {"element":"[name=\"banco_id\"]","intro":"🏦 <b>Banco</b> de la cuenta (de los registrados en Bancos y Cuentas).","position":"bottom"},
                                {"element":"[name=\"moneda\"]","intro":"💱 <b>Moneda</b> de la cuenta.","position":"top"},
                                {"element":"[name=\"saldo_inicial\"]","intro":"💰 <b>Saldo Inicial</b>: el saldo con el que arranca la cuenta. A partir de aquí el sistema lo va actualizando con cada movimiento.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form method="POST" action="{{ route('empresas.cuentas.store', $empresa->uuid) }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Los campos marcados con <span class="text-danger">(*)</span> son obligatorios.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre de la Cuenta <span class="text-danger">(*)</span></label>
                            <input type="text" name="nombre_cuenta" id="nombre_cuenta_cuentas" class="form-control"
                                   required placeholder="Ej: CUENTA PRINCIPAL BOB"
                                   oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Banco <span class="text-danger">(*)</span></label>
                            <select name="banco_id" class="form-select" required>
                                <option value="">-- Seleccionar banco --</option>
                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">N° de Cuenta <span class="text-danger">(*)</span></label>
                            <input type="text" name="numero_cuenta" id="num_cuenta_cuentas" class="form-control"
                                   required maxlength="20" placeholder="Solo números"
                                   oninput="this.value=this.value.replace(/\D/g,'').slice(0,20); document.getElementById('cnt_num_cuentas').textContent=this.value.length+'/20'">
                            <div class="d-flex justify-content-between">
                                <div class="form-text">Solo dígitos numéricos.</div>
                                <div class="form-text text-muted" id="cnt_num_cuentas">0/20</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Moneda <span class="text-danger">(*)</span></label>
                            <select name="moneda" class="form-select" required>
                                @foreach($monedas as $moneda)
                                    <option value="{{ $moneda->valor }}" {{ $moneda->valor === 'BOB' ? 'selected' : '' }}>
                                        {{ $moneda->valor }} — {{ $moneda->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Saldo Inicial <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" name="saldo_inicial" class="form-control" value="0.00" required min="0"
                                   placeholder="0.00"
                                   oninput="if(this.value < 0) this.value = 0;"
                                   onblur="this.value = parseFloat(this.value || 0).toFixed(2)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar Cuenta</button>
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
    // Solo inicializar DataTables si hay filas reales (evita el error
    // "Requested unknown parameter" cuando la tabla muestra la fila vacía con colspan)
    var tabla = $('#tabla_movimientos');
    var tieneFilas = tabla.find('tbody tr').length > 0 && !tabla.find('tbody tr td[colspan]').length;

    if (tieneFilas) {
        tabla.DataTable({
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
    }
});
</script>
@endsection
