@extends('layouts.app')
@section('titulo','Préstamos Internos')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PRÉSTAMOS INTERNOS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tesoreria.index') }}">Tesorería</a></li>
                    <li class="breadcrumb-item active">Préstamos Internos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"🔁 Los <b>Préstamos Internos</b> son transferencias de dinero <b>entre cuentas de la empresa</b> (no a terceros). Al registrar uno, baja el saldo de la cuenta origen y sube el de la destino."},
                        {"element":"#pi-resumen","intro":"📊 Resumen: total de préstamos, cuántos siguen <b>pendientes</b> de devolver y cuántos ya están <b>pagados</b>.","position":"bottom"},
                        {"element":"#pi-tabla","intro":"📋 El historial: origen, destino, monto, cuánto se ha devuelto y cuánto queda pendiente.","position":"top"},
                        {"element":"#pi-tabla thead th:nth-child(8)","intro":"🚦 El <b>Estado</b>: Pendiente, Parcial (devuelto en parte) o Pagado (devuelto del todo).","position":"bottom"},
                        {"element":"#pi-tabla tbody tr:first-child td:last-child","intro":"↩️ Con el botón <b>Devolver</b> registras una devolución parcial o total de un préstamo pendiente.","position":"left"},
                        {"element":"#btnNuevoPrestamo","intro":"➕ Con <b>Nuevo Préstamo</b> registras una transferencia entre cuentas. El formulario tiene su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('empresas.create')
            <button id="btnNuevoPrestamo" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                <i class="bi bi-plus-lg"></i> Nuevo Préstamo
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Préstamos Internos</strong> — Módulo para registrar transferencias de dinero entre cuentas de la empresa.
            Solo se permiten préstamos entre cuentas de la <strong>misma moneda</strong> y el monto no puede superar el saldo disponible de la cuenta origen.
            Al registrar un préstamo, el saldo de la cuenta origen disminuye y el de la cuenta destino aumenta automáticamente.
            Usa el botón <strong>Devolver</strong> en la tabla para registrar devoluciones parciales o totales.
        </div>
    </div>

    {{-- Resumen --}}
    <div class="row mb-4" id="pi-resumen">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body pt-4">
                    <div class="text-muted small">Total Préstamos</div>
                    <div class="fs-4 fw-bold text-primary">{{ $prestamos->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body pt-4">
                    <div class="text-muted small">Pendientes</div>
                    <div class="fs-4 fw-bold text-warning">{{ $prestamos->where('estado', '!=', 'pagado')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body pt-4">
                    <div class="text-muted small">Pagados</div>
                    <div class="fs-4 fw-bold text-success">{{ $prestamos->where('estado', 'pagado')->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de préstamos --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Historial de Préstamos</h5>
            <div class="table-responsive">
                <table id="pi-tabla" class="table table-hover table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto Original</th>
                            <th class="text-end">Devuelto</th>
                            <th class="text-end">Pendiente</th>
                            <th class="text-center">Estado</th>
                            @can('empresas.create')
                            <th class="text-center">Acción</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prestamos as $p)
                        <tr>
                            <td>{{ $p->fecha_prestamo->format('d/m/Y') }}</td>
                            <td>
                                <span class="small">{{ $p->cuentaOrigen->empresa->nombre ?? '-' }}</span><br>
                                <span class="text-muted small">{{ $p->cuentaOrigen->nombre_cuenta ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="small">{{ $p->cuentaDestino->empresa->nombre ?? '-' }}</span><br>
                                <span class="text-muted small">{{ $p->cuentaDestino->nombre_cuenta ?? '-' }}</span>
                            </td>
                            <td>{{ $p->concepto }}</td>
                            <td class="text-end fw-semibold">
                                {{ $p->moneda }} {{ number_format($p->monto_original, 2) }}
                            </td>
                            <td class="text-end text-success">
                                {{ $p->moneda }} {{ number_format($p->monto_devuelto, 2) }}
                            </td>
                            <td class="text-end text-danger fw-semibold">
                                {{ $p->moneda }} {{ number_format($p->monto_pendiente, 2) }}
                            </td>
                            <td class="text-center">
                                @if($p->estado === 'pagado')
                                    <span class="badge bg-success">Pagado</span>
                                @elseif($p->estado === 'pagado_parcial')
                                    <span class="badge bg-warning text-dark">Parcial</span>
                                @else
                                    <span class="badge bg-danger">Pendiente</span>
                                @endif
                            </td>
                            @can('empresas.create')
                            <td class="text-center">
                                @if($p->estado !== 'pagado')
                                <button class="btn btn-sm btn-outline-success"
                                        onclick="abrirDevolucion('{{ $p->uuid }}', '{{ $p->concepto }}', {{ $p->monto_pendiente }}, '{{ $p->moneda }}')">
                                    <i class="bi bi-arrow-return-left"></i> Devolver
                                </button>
                                @endif
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">Sin préstamos registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $prestamos->links() }}
        </div>
    </div>
</section>

{{-- MODAL NUEVO PRÉSTAMO --}}
<div class="modal fade" id="modalPrestamo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right"></i> Nuevo Préstamo Interno</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalPrestamo"
                            data-steps='[
                                {"intro":"📝 Registra una transferencia entre dos cuentas de la empresa. Solo entre cuentas de la <b>misma moneda</b>. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#cuentaOrigen","intro":"📤 <b>Cuenta Origen</b>: la que <b>presta</b> el dinero. Muestra su saldo disponible. Elígela primero.","position":"bottom"},
                                {"element":"#cuentaDestino","intro":"📥 <b>Cuenta Destino</b>: la que <b>recibe</b>. Solo aparecen cuentas de la misma moneda que la origen.","position":"bottom"},
                                {"element":"#montoPrestamo","intro":"💲 <b>Monto</b> a prestar. No puede superar el saldo disponible de la cuenta origen.","position":"bottom"},
                                {"element":"[name=\"fecha_prestamo\"]","intro":"📅 <b>Fecha</b> del préstamo. Opcionalmente, una fecha de vencimiento al lado.","position":"top"},
                                {"element":"[name=\"concepto\"]","intro":"✏️ <b>Concepto</b>: el motivo del préstamo.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form method="POST" action="{{ route('prestamos_internos.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cuenta Origen (presta) <span class="text-danger">(*)</span></label>
                            <select name="cuenta_origen_id" id="cuentaOrigen" class="form-select" required onchange="filtrarDestino()">
                                <option value="">-- Seleccione --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}" data-moneda="{{ $cuenta->moneda }}" data-saldo="{{ $cuenta->saldo_actual }}">{{ $cuenta->nombre_cuenta }} ({{ $cuenta->moneda }}) — Saldo: {{ number_format($cuenta->saldo_actual, 2) }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cuenta Destino (recibe) <span class="text-danger">(*)</span></label>
                            <select name="cuenta_destino_id" id="cuentaDestino" class="form-select" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}" data-moneda="{{ $cuenta->moneda }}">{{ $cuenta->nombre_cuenta }} ({{ $cuenta->moneda }})</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Monto <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" name="monto" id="montoPrestamo" class="form-control" required min="0.01" oninput="validarMontoPrestamo()" onkeydown="return limitarDigitosMonto(event, this)">
                            <div class="form-text" id="saldoHint"></div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Moneda</label>
                            <input type="text" id="monedaPrestamo" class="form-control" value="—" readonly>
                            <input type="hidden" name="moneda" id="monedaPrestamoHidden" value="">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha_prestamo" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto <span class="text-danger">(*)</span></label>
                            <input type="text" name="concepto" class="form-control" required placeholder="Motivo del préstamo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DEVOLUCIÓN --}}
<div class="modal fade" id="modalDevolucion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Registrar Devolución</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDevolucion" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" id="infoDevolucion"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Monto a Devolver <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.01" name="monto" id="montoDevolucion" class="form-control" required min="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto</label>
                            <input type="text" name="concepto" class="form-control" placeholder="Devolución parcial / total">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar Devolución</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function filtrarDestino() {
    const origenSel = document.getElementById('cuentaOrigen');
    const origenId  = origenSel.value;
    const moneda    = origenSel.selectedOptions[0]?.dataset.moneda ?? '';
    const destino   = document.getElementById('cuentaDestino');

    // Actualizar campo moneda y límite de monto
    document.getElementById('monedaPrestamo').value       = moneda || '—';
    document.getElementById('monedaPrestamoHidden').value = moneda;
    const saldo = parseFloat(origenSel.selectedOptions[0]?.dataset.saldo ?? 0);
    const inputMonto = document.getElementById('montoPrestamo');
    inputMonto.max = saldo > 0 ? saldo : '';
    document.getElementById('saldoHint').innerHTML = origenId
        ? 'Saldo disponible: <strong>' + moneda + ' ' + saldo.toFixed(2) + '</strong>'
        : '';
    validarMontoPrestamo();

    // Limpiar selección si la opción activa ya no es válida
    const destinoOpt = destino.selectedOptions[0];
    if (destinoOpt && (destinoOpt.value === origenId || destinoOpt.dataset.moneda !== moneda)) {
        destino.value = '';
    }

    Array.from(destino.options).forEach(opt => {
        if (opt.value === '') return; // placeholder siempre visible
        const mismaMoneda = opt.dataset.moneda === moneda;
        const mismaCuenta = opt.value === origenId;
        opt.hidden   = !mismaMoneda || mismaCuenta;
        opt.disabled = !mismaMoneda || mismaCuenta;
    });
}

function limitarDigitosMonto(e, input) {
    const teclaPermitida = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','.'].includes(e.key);
    if (teclaPermitida) return true;
    if (!/\d/.test(e.key)) return false;
    const partes = input.value.split('.');
    const enteros = partes[0] ?? '';
    const decimales = partes[1] ?? null;
    // Si el cursor está en la parte decimal, limitar a 2 dígitos
    const cursorPos = input.selectionStart;
    const puntoIdx  = input.value.indexOf('.');
    if (puntoIdx !== -1 && cursorPos > puntoIdx) {
        if (decimales && decimales.length >= 2) return false;
    } else {
        // Parte entera: máximo 10 dígitos
        if (enteros.replace(/[^0-9]/g,'').length >= 10) return false;
    }
    return true;
}

function validarMontoPrestamo() {
    const origenSel = document.getElementById('cuentaOrigen');
    const saldo     = parseFloat(origenSel.selectedOptions[0]?.dataset.saldo ?? 0);
    const input     = document.getElementById('montoPrestamo');
    const moneda    = origenSel.selectedOptions[0]?.dataset.moneda ?? '';

    if (!origenSel.value || !input.value) return;

    if (parseFloat(input.value) > saldo) {
        input.value = saldo.toFixed(2);
    }

    document.getElementById('saldoHint').innerHTML =
        'Saldo disponible: <strong>' + moneda + ' ' + saldo.toFixed(2) + '</strong>';
}

function abrirDevolucion(uuid, concepto, montoPendiente, moneda) {
    document.getElementById('formDevolucion').action = '/prestamos-internos/' + uuid + '/devolver';
    document.getElementById('montoDevolucion').max   = montoPendiente;
    document.getElementById('montoDevolucion').value = montoPendiente;
    document.getElementById('infoDevolucion').innerHTML =
        '<strong>' + concepto + '</strong><br>Pendiente: <strong>' + moneda + ' ' + parseFloat(montoPendiente).toFixed(2) + '</strong>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDevolucion')).show();
}
</script>
@endsection
