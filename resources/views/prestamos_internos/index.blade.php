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
            @can('prestamos_internos.create')
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
                            <th>Código</th>
                            <th class="text-end">Monto Original</th>
                            <th class="text-end">Devuelto</th>
                            <th class="text-end">Pendiente</th>
                            <th class="text-center">Estado</th>
                            @canany(['prestamos_internos.create', 'prestamos_internos.edit', 'prestamos_internos.destroy'])
                            <th class="text-center">Acción</th>
                            @endcanany
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
                            <td>
                                @if($p->codigo_seguimiento)
                                    <span class="small text-muted d-block">{{ $p->metodo_pago }}</span>
                                    <code class="small">{{ $p->codigo_seguimiento }}</code>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">
                                {{ $p->moneda }} {{ number_format($p->monto_original, 2, ',', '.') }}
                            </td>
                            <td class="text-end text-success">
                                {{ $p->moneda }} {{ number_format($p->monto_devuelto, 2, ',', '.') }}
                            </td>
                            <td class="text-end text-danger fw-semibold">
                                {{ $p->moneda }} {{ number_format($p->monto_pendiente, 2, ',', '.') }}
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
                            @canany(['prestamos_internos.create', 'prestamos_internos.edit', 'prestamos_internos.destroy'])
                            <td class="text-center text-nowrap">
                                @can('prestamos_internos.create')
                                @if($p->estado !== 'pagado')
                                <button class="btn btn-sm btn-outline-success"
                                        onclick="abrirDevolucion('{{ $p->uuid }}', '{{ $p->concepto }}', {{ $p->monto_pendiente }}, '{{ $p->moneda }}')">
                                    <i class="bi bi-arrow-return-left"></i> Devolver
                                </button>
                                @endif
                                @endcan
                                @if($p->estado === 'pendiente')
                                    @can('prestamos_internos.edit')
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar"
                                            onclick="abrirEditarPrestamo('{{ $p->uuid }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endcan
                                    @can('prestamos_internos.destroy')
                                    <a class="btn btn-sm btn-outline-danger" title="Eliminar"
                                       href="{{ route('prestamos_internos.destroy', $p->uuid) }}"
                                       onclick="return confirm('¿Eliminar este préstamo? Se revertirá el efecto en los saldos de ambas cuentas.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    @endcan
                                @endif
                            </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted">Sin préstamos registrados</td></tr>
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
            <form id="formPrestamo" method="POST" action="{{ route('prestamos_internos.store') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenPrestamo" value="{{ $idempotencyToken ?? '' }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cuenta Origen (presta) <span class="text-danger">(*)</span></label>
                            <select name="cuenta_origen_id" id="cuentaOrigen" class="form-select" required onchange="filtrarDestino()">
                                <option value="">-- Seleccione --</option>
                                @foreach($empresas as $empresa)
                                    <optgroup label="{{ $empresa->nombre }}">
                                        @foreach($empresa->cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}" data-moneda="{{ $cuenta->moneda }}" data-saldo="{{ $cuenta->saldo_actual }}">{{ $cuenta->nombre_cuenta }} ({{ $cuenta->moneda }}) — Saldo: {{ number_format($cuenta->saldo_actual, 2, ',', '.') }}</option>
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
                                            <option value="{{ $cuenta->id }}" data-moneda="{{ $cuenta->moneda }}" data-saldo="{{ $cuenta->saldo_actual }}">{{ $cuenta->nombre_cuenta }} ({{ $cuenta->moneda }}) — Saldo: {{ number_format($cuenta->saldo_actual, 2, ',', '.') }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Monto <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" id="montoPrestamo_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="monto" id="montoPrestamo" value="">
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
                        <div class="col-md-6" id="contenedor_metodo_pago_prestamo">
                            <label class="form-label">Método de Pago<span id="asterisco_metodo_pago_prestamo" class="text-danger">(*)</span></label>
                            <select name="metodo_pago" id="metodo_pago_prestamo" class="form-select" required onchange="actualizarCodigoSeguimientoPrestamo()">
                                <option value="">-- SELECCIONE --</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                <option value="QR">QR</option>
                            </select>
                            <small class="text-muted">Cómo se transfirió el dinero entre cuentas.</small>
                        </div>
                        <div class="col-md-6 d-none" id="contenedor_codigo_seguimiento_prestamo">
                            <label class="form-label">Código de Transferencia<span class="text-danger">(*)</span></label>
                            <input type="text" name="codigo_seguimiento" id="codigo_seguimiento_prestamo" class="form-control" placeholder="CÓDIGO DEL BANCO" onkeyup="this.value=this.value.toUpperCase();">
                            <small class="text-muted">Ingrese el código de la transferencia.</small>
                        </div>
                        <div class="col-md-6 d-none" id="contenedor_codigo_qr_info_prestamo">
                            <label class="form-label">Código QR</label>
                            <input type="text" id="codigo_qr_info_prestamo" class="form-control" readonly>
                            <small class="text-muted">Código interno generado automáticamente.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto <span class="text-danger">(*)</span></label>
                            <input type="text" name="concepto" class="form-control" required placeholder="Motivo del préstamo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnPrestamo">Registrar Préstamo</button>
                </div>
            </form>
            <script>
            document.getElementById('formPrestamo').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnPrestamo');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });

            // Mismo patrón que Gastos Extra: QR genera código interno (el campo se
            // muestra de solo lectura con un placeholder), Transferencia exige código.
            function actualizarCodigoSeguimientoPrestamo() {
                var metodo = document.getElementById('metodo_pago_prestamo').value;
                var contCodigo = document.getElementById('contenedor_codigo_seguimiento_prestamo');
                var contQrInfo = document.getElementById('contenedor_codigo_qr_info_prestamo');
                var inpCodigo = document.getElementById('codigo_seguimiento_prestamo');

                contCodigo.classList.add('d-none');
                contQrInfo.classList.add('d-none');
                inpCodigo.required = false;

                if (metodo === 'TRANSFERENCIA') {
                    contCodigo.classList.remove('d-none');
                    inpCodigo.required = true;
                } else if (metodo === 'QR') {
                    contQrInfo.classList.remove('d-none');
                    document.getElementById('codigo_qr_info_prestamo').value = 'Se generará automáticamente al guardar';
                }
            }
            </script>
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

{{-- MODAL EDITAR PRÉSTAMO --}}
<div class="modal fade" id="modalEditarPrestamo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Préstamo Interno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarPrestamo" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" id="infoEditarPrestamo"></div>
                    <div class="row g-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Monto <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" id="montoEditarPrestamo_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="monto" id="montoEditarPrestamo" value="">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha_prestamo" id="fechaEditarPrestamo" class="form-control" required>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fechaVencimientoEditarPrestamo" class="form-control">
                        </div>
                        <div class="col-md-6" id="contenedor_metodo_pago_editar_prestamo">
                            <label class="form-label">Método de Pago<span class="text-danger">(*)</span></label>
                            <select name="metodo_pago" id="metodoPagoEditarPrestamo" class="form-select" required onchange="actualizarCodigoSeguimientoEditarPrestamo()">
                                <option value="">-- SELECCIONE --</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                <option value="QR">QR</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="contenedor_codigo_seguimiento_editar_prestamo">
                            <label class="form-label">Código de Transferencia<span class="text-danger">(*)</span></label>
                            <input type="text" name="codigo_seguimiento" id="codigoSeguimientoEditarPrestamo" class="form-control" placeholder="CÓDIGO DEL BANCO" onkeyup="this.value=this.value.toUpperCase();">
                        </div>
                        <div class="col-md-6 d-none" id="contenedor_codigo_qr_info_editar_prestamo">
                            <label class="form-label">Código QR</label>
                            <input type="text" id="codigoQrInfoEditarPrestamo" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto <span class="text-danger">(*)</span></label>
                            <input type="text" name="concepto" id="conceptoEditarPrestamo" class="form-control" required placeholder="Motivo del préstamo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
        ? 'Saldo disponible: <strong>' + moneda + ' ' + _fmtPI(saldo) + '</strong>'
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

function _fmtPI(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}
function _parsePI(v) {
    return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0;
}

function validarMontoPrestamo() {
    const origenSel = document.getElementById('cuentaOrigen');
    const saldo     = parseFloat(origenSel.selectedOptions[0]?.dataset.saldo ?? 0);
    const disp      = document.getElementById('montoPrestamo_display');
    const hidden    = document.getElementById('montoPrestamo');
    const moneda    = origenSel.selectedOptions[0]?.dataset.moneda ?? '';

    if (!origenSel.value || !hidden.value) return;

    if (_parsePI(disp.value) > saldo) {
        disp.value   = _fmtPI(saldo);
        hidden.value = saldo;
    }

    document.getElementById('saldoHint').innerHTML =
        'Saldo disponible: <strong>' + moneda + ' ' + _fmtPI(saldo) + '</strong>';
}

function abrirDevolucion(uuid, concepto, montoPendiente, moneda) {
    document.getElementById('formDevolucion').action = url_global + '/prestamos-internos/' + uuid + '/devolver';
    document.getElementById('montoDevolucion').max   = montoPendiente;
    document.getElementById('montoDevolucion').value = montoPendiente;
    document.getElementById('infoDevolucion').innerHTML =
        '<strong>' + concepto + '</strong><br>Pendiente: <strong>' + moneda + ' ' + _fmtPI(montoPendiente) + '</strong>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDevolucion')).show();
}

// ── Cajero monto préstamo ──
(function() {
    function _txt2num(v) { return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0; }
    var disp   = document.getElementById('montoPrestamo_display');
    var hidden = document.getElementById('montoPrestamo');
    if (!disp || !hidden) return;
    disp.addEventListener('input', function() {
        var raw    = this.value.replace(/[^0-9,]/g, '');
        var partes = raw.split(',');
        if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
        partes = raw.split(',');
        if (partes[1] !== undefined) partes[1] = partes[1].slice(0, 2);
        var entF  = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var nuevo = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
        var diff  = nuevo.length - this.value.length;
        var pos   = (this.selectionStart || 0) + diff;
        this.value = nuevo;
        try { this.setSelectionRange(pos, pos); } catch(_) {}
        hidden.value = _txt2num(nuevo) || '';
        validarMontoPrestamo();
    });
    disp.addEventListener('blur', function() {
        var n = _txt2num(this.value);
        this.value   = n > 0 ? _fmtPI(n) : '';
        hidden.value = n > 0 ? n : '';
        validarMontoPrestamo();
    });
})();

function abrirEditarPrestamo(uuid) {
    fetch(url_global + '/prestamos-internos/' + uuid + '/edit')
        .then(r => r.json())
        .then(p => {
            document.getElementById('formEditarPrestamo').action = url_global + '/prestamos-internos/' + uuid;
            document.getElementById('infoEditarPrestamo').innerHTML =
                'Editando préstamo: <strong>' + (p.concepto ?? '') + '</strong> (' + p.moneda + ')';
            document.getElementById('montoEditarPrestamo').value = p.monto_original ?? '';
            document.getElementById('montoEditarPrestamo_display').value = p.monto_original
                ? _fmtPI(p.monto_original) : '';
            document.getElementById('fechaEditarPrestamo').value = p.fecha_prestamo ? p.fecha_prestamo.substring(0, 10) : '';
            document.getElementById('fechaVencimientoEditarPrestamo').value = p.fecha_vencimiento ? p.fecha_vencimiento.substring(0, 10) : '';
            document.getElementById('conceptoEditarPrestamo').value = p.concepto ?? '';
            document.getElementById('metodoPagoEditarPrestamo').value = p.metodo_pago ?? '';
            document.getElementById('codigoSeguimientoEditarPrestamo').value = p.metodo_pago === 'TRANSFERENCIA' ? (p.codigo_seguimiento ?? '') : '';
            actualizarCodigoSeguimientoEditarPrestamo();
            if (p.metodo_pago === 'QR') {
                document.getElementById('codigoQrInfoEditarPrestamo').value = p.codigo_seguimiento ?? '';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarPrestamo')).show();
        });
}

function actualizarCodigoSeguimientoEditarPrestamo() {
    var metodo = document.getElementById('metodoPagoEditarPrestamo').value;
    var contCodigo = document.getElementById('contenedor_codigo_seguimiento_editar_prestamo');
    var contQrInfo = document.getElementById('contenedor_codigo_qr_info_editar_prestamo');
    var inpCodigo = document.getElementById('codigoSeguimientoEditarPrestamo');

    contCodigo.classList.add('d-none');
    contQrInfo.classList.add('d-none');
    inpCodigo.required = false;

    if (metodo === 'TRANSFERENCIA') {
        contCodigo.classList.remove('d-none');
        inpCodigo.required = true;
    } else if (metodo === 'QR') {
        contQrInfo.classList.remove('d-none');
    }
}

// ── Cajero monto editar préstamo ──
(function() {
    function _txt2num(v) { return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0; }
    var disp   = document.getElementById('montoEditarPrestamo_display');
    var hidden = document.getElementById('montoEditarPrestamo');
    if (!disp || !hidden) return;
    disp.addEventListener('input', function() {
        var raw    = this.value.replace(/[^0-9,]/g, '');
        var partes = raw.split(',');
        if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
        partes = raw.split(',');
        if (partes[1] !== undefined) partes[1] = partes[1].slice(0, 2);
        var entF  = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var nuevo = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
        var diff  = nuevo.length - this.value.length;
        var pos   = (this.selectionStart || 0) + diff;
        this.value = nuevo;
        try { this.setSelectionRange(pos, pos); } catch(_) {}
        hidden.value = _txt2num(nuevo) || '';
    });
    disp.addEventListener('blur', function() {
        var n = _txt2num(this.value);
        this.value   = n > 0 ? _fmtPI(n) : '';
        hidden.value = n > 0 ? n : '';
    });
})();
</script>
@endsection
