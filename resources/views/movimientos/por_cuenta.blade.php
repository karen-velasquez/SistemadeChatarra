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
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"📒 Estás viendo el <b>detalle de una cuenta</b>: su saldo y el historial completo de movimientos. Aquí también puedes registrar movimientos manuales."},
                        {"element":"#cta-resumen","intro":"📊 Estas tarjetas muestran la empresa, el <b>saldo actual</b> de la cuenta y los totales de ingresos y egresos.","position":"bottom"},
                        {"element":"#cta-movimientos","intro":"📋 El historial de movimientos: las filas <b>verdes</b> son ingresos (entró dinero) y las <b>rojas</b> son egresos (salió dinero). Con el botón 👁 ves el detalle de cada uno.","position":"top"},
                        {"element":"#cta-nota","intro":"🔒 Los movimientos creados por <b>pagos</b> del sistema aparecen bloqueados: solo se anulan desde su módulo (Pagos a Clientes, Proveedores o Camiones).","position":"bottom"},
                        {"element":"#btnRegistrarMov","intro":"➕ Con <b>Registrar Movimiento</b> agregas un movimiento <b>manual</b> (un ajuste o gasto puntual). El formulario tiene su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('tesoreria.create')
            <button id="btnRegistrarMov" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
                <i class="bi bi-plus-lg"></i> Registrar Movimiento
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small" id="cta-nota">
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
    <div class="row mb-4" id="cta-resumen">
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
                        {{ $cuenta->moneda }} {{ number_format($cuenta->saldo_actual, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Total Ingresos</div>
                    <div class="fs-5 fw-bold text-success">BOB {{ number_format($totalIngresos, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body pt-4 d-flex flex-column justify-content-center">
                    <div class="text-muted small">Total Egresos</div>
                    <div class="fs-5 fw-bold text-danger">BOB {{ number_format($totalEgresos, 2, ',', '.') }}</div>
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
            &nbsp;|&nbsp; <strong>Saldo Inicial:</strong> {{ $cuenta->moneda }} {{ number_format($cuenta->saldo_inicial, 2, ',', '.') }}
        </span>
    </div>
    @endif

    {{-- Movimientos --}}
    <div class="card" id="cta-movimientos">
        <div class="card-body">
            <h5 class="card-title mb-3">Movimientos</h5>
            @include('movimientos._tabla', ['movimientos' => $movimientos, 'mostrarCuenta' => false, 'mostrarEliminar' => true, 'mostrarEditar' => true])
        </div>
    </div>
</section>

{{-- MODAL MOVIMIENTO MANUAL --}}
<div class="modal fade" id="modalMovimiento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Registrar Movimiento</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalMovimiento"
                            data-steps='[
                                {"intro":"📝 Registra un movimiento <b>manual</b> en esta cuenta (un ajuste o gasto puntual). Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"[name=\"tipo\"]","intro":"🔀 <b>Tipo</b>: <b>Ingreso</b> (suma al saldo) o <b>Egreso</b> (resta del saldo).","position":"bottom"},
                                {"element":"[name=\"categoria\"]","intro":"🏷️ <b>Categoría</b>: para movimientos manuales suele ser <b>Otro</b>. Los pagos a clientes/proveedores/camiones se registran desde sus propios módulos.","position":"bottom"},
                                {"element":"[name=\"fecha\"]","intro":"📅 <b>Fecha</b> del movimiento.","position":"bottom"},
                                {"element":"[name=\"monto\"]","intro":"💲 <b>Monto</b> del movimiento. La moneda es la de la cuenta (no se cambia).","position":"bottom"},
                                {"element":"[name=\"concepto\"]","intro":"✏️ <b>Concepto</b>: una descripción de para qué fue el movimiento.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formMovimiento" method="POST" action="{{ route('tesoreria.movimiento.store') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" value="{{ $idempotencyToken ?? '' }}">
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
                            <div class="input-group">
                                <select name="categoria" id="mov_sel_categoria" class="form-select" required>
                                    {{-- Las categorías propias del sistema (Pago de Cliente, Pago a Proveedor,
                                         Préstamo Otorgado, etc.) no se ofrecen aquí: se generan automáticamente
                                         desde sus propios módulos, no se eligen a mano en un movimiento manual. --}}
                                    <option value="otro" selected>Otro</option>
                                    @if($categoriasPersonalizadas->isNotEmpty())
                                    <optgroup label="Categorías personalizadas" id="mov_grp_categorias_personalizadas">
                                        @foreach($categoriasPersonalizadas as $cat)
                                            <option value="{{ $cat->valor }}">{{ $cat->valor }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endif
                                </select>
                                <button type="button" class="btn btn-outline-secondary" title="Nueva categoría personalizada"
                                        onclick="abrirModalParametroRapido('categoria_movimiento', 'mov_sel_categoria', 'Nueva Categoría de Movimiento')">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
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
                            <input type="text" inputmode="numeric" id="monto_mov_display" class="form-control"
                                   placeholder="0,00" autocomplete="off" required>
                            <input type="hidden" name="monto" id="monto_mov">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Moneda</label>
                            <input type="text" class="form-control" value="{{ $cuenta->moneda }}" readonly>
                            <input type="hidden" name="moneda" value="{{ $cuenta->moneda }}">
                        </div>
                        <div class="col-md-6" id="row_tipo_cambio" style="{{ $cuenta->moneda === 'BOB' ? 'display:none' : '' }}">
                            <label class="form-label">Tipo de Cambio <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" id="tc_mov_display" class="form-control"
                                   placeholder="1,0000" autocomplete="off" {{ $cuenta->moneda === 'BOB' ? '' : 'required' }}>
                            <input type="hidden" name="tipo_cambio" id="tc_mov" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código de Transacción</label>
                            <input type="text" name="codigo_seguimiento" class="form-control text-uppercase"
                                   style="text-transform:uppercase" maxlength="100" placeholder="Opcional: se genera uno si lo deja vacío">
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

{{-- MODAL EDITAR MOVIMIENTO MANUAL --}}
<div class="modal fade" id="modalEditarMovimiento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarMovimiento" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <div><span class="badge fs-6" id="edit_mov_tipo_badge"></span></div>
                            <div class="form-text">El tipo no se puede cambiar: elimina el movimiento y registra uno nuevo si lo necesitas distinto.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-danger">(*)</span></label>
                            <input type="date" name="fecha" id="edit_mov_fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto ({{ $cuenta->moneda }}) <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" id="edit_mov_monto_display" class="form-control"
                                   placeholder="0,00" autocomplete="off" required>
                            <input type="hidden" name="monto" id="edit_mov_monto">
                        </div>
                        <div class="col-md-6" style="{{ $cuenta->moneda === 'BOB' ? 'display:none' : '' }}">
                            <label class="form-label">Tipo de Cambio <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" id="edit_mov_tc_display" class="form-control"
                                   placeholder="1,0000" autocomplete="off" {{ $cuenta->moneda === 'BOB' ? '' : 'required' }}>
                            <input type="hidden" name="tipo_cambio" id="edit_mov_tc" value="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Concepto <span class="text-danger">(*)</span></label>
                            <input type="text" name="concepto" id="edit_mov_concepto" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="edit_mov_obs" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEditarMovimiento">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL PARÁMETRO RÁPIDO (Nueva Categoría de Movimiento) ===== --}}
<div class="modal fade" id="modalParametroRapido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloParametroRapido"><i class="bi bi-plus-circle"></i> Nuevo Parámetro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Valor <span class="text-danger">(*)</span></label>
                <input type="text" class="form-control" id="pr_valor" maxlength="255"
                       placeholder="Ej: ALQUILER DE OFICINA" autocomplete="off" style="text-transform:uppercase"
                       oninput="this.value=this.value.toUpperCase()">
                <div id="pr_feedback" class="small mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="pr_btn_guardar" onclick="guardarParametroRapido()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// La tabla ya llega ordenada por fecha desde el backend (orderByDesc en el controlador).
// DataTables se quitó: su envoltorio rompía el sticky de la cabecera al hacer scroll,
// igual que en Seguimiento de Cargas y en Movimientos. Sin filtro propio que lo
// reemplace, se pierde la búsqueda y el reordenar por columna, pero se gana la
// cabecera fija.

// ── Cajero monto y tipo_cambio (mismo formateo de dinero del resto del sistema) ──
(function() {
    function _txt2num(v) {
        return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0;
    }
    function _fmt2(n) {
        return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    }
    function _fmt4(n) {
        return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 4, maximumFractionDigits: 4 }).format(n);
    }
    function _initCajero(displayId, hiddenId, decimals) {
        var disp   = document.getElementById(displayId);
        var hidden = document.getElementById(hiddenId);
        if (!disp || !hidden) return;
        disp.addEventListener('input', function() {
            var raw    = this.value.replace(/[^0-9,]/g, '');
            var partes = raw.split(',');
            if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
            partes = raw.split(',');
            if (partes[1] !== undefined) partes[1] = partes[1].slice(0, decimals);
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
            this.value   = n > 0 ? (decimals === 4 ? _fmt4(n) : _fmt2(n)) : '';
            hidden.value = n > 0 ? n : '';
        });
    }
    window._cajeroFmt2 = _fmt2;
    window._cajeroFmt4 = _fmt4;

    _initCajero('monto_mov_display', 'monto_mov', 2);
    _initCajero('tc_mov_display',    'tc_mov',    4);
    _initCajero('edit_mov_monto_display', 'edit_mov_monto', 2);
    _initCajero('edit_mov_tc_display',    'edit_mov_tc',    4);
})();

function abrirEditarMovimiento(uuid, tipo, monto, tipoCambio, fecha, concepto, observaciones) {
    document.getElementById('formEditarMovimiento').action = url_global + '/tesoreria/movimiento/' + uuid;
    var badge = document.getElementById('edit_mov_tipo_badge');
    badge.textContent = tipo === 'ingreso' ? 'Ingreso' : 'Egreso';
    badge.className = 'badge fs-6 ' + (tipo === 'ingreso' ? 'bg-success' : 'bg-danger');
    document.getElementById('edit_mov_monto').value = monto;
    document.getElementById('edit_mov_monto_display').value = monto > 0 ? window._cajeroFmt2(parseFloat(monto)) : '';
    document.getElementById('edit_mov_tc').value = tipoCambio;
    document.getElementById('edit_mov_tc_display').value = tipoCambio > 0 ? window._cajeroFmt4(parseFloat(tipoCambio)) : '';
    document.getElementById('edit_mov_fecha').value = fecha;
    document.getElementById('edit_mov_concepto').value = concepto;
    document.getElementById('edit_mov_obs').value = observaciones;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarMovimiento')).show();
}

document.getElementById('formEditarMovimiento').addEventListener('submit', function () {
    var btn = document.getElementById('btnEditarMovimiento');
    if (btn.disabled) { return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
});

// ===== Parámetro rápido: nueva categoría personalizada de movimiento =====
function _normalizarValorParametroMov(valor) {
    return valor.trim().replace(/\s+/g, ' ');
}

document.getElementById('pr_valor')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); guardarParametroRapido(); }
});

// Inserta la opción en el optgroup de "Categorías personalizadas" (lo crea si
// todavía no existía), en orden alfabético.
function _insertarOpcionCategoriaPersonalizada(select, valor) {
    let grupo = document.getElementById('mov_grp_categorias_personalizadas');
    if (!grupo) {
        grupo = document.createElement('optgroup');
        grupo.id = 'mov_grp_categorias_personalizadas';
        grupo.label = 'Categorías personalizadas';
        select.appendChild(grupo);
    }
    const op = document.createElement('option');
    op.value = valor;
    op.textContent = valor;
    const siguiente = Array.from(grupo.children).find(
        o => o.textContent.localeCompare(valor, 'es', { sensitivity: 'base' }) > 0
    );
    if (siguiente) grupo.insertBefore(op, siguiente);
    else grupo.appendChild(op);
    return op;
}

let _prTipo = null;
let _prSelectId = null;

function abrirModalParametroRapido(tipo, selectId, titulo) {
    _prTipo = tipo;
    _prSelectId = selectId;
    document.getElementById('tituloParametroRapido').innerHTML = '<i class="bi bi-plus-circle"></i> ' + titulo;
    const input = document.getElementById('pr_valor');
    input.value = '';
    document.getElementById('pr_feedback').innerHTML = '';
    document.getElementById('pr_btn_guardar').disabled = false;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalParametroRapido')).show();
    setTimeout(() => input.focus(), 300);
}

function guardarParametroRapido() {
    const input = document.getElementById('pr_valor');
    const feedback = document.getElementById('pr_feedback');
    const btn = document.getElementById('pr_btn_guardar');
    const valor = _normalizarValorParametroMov(input.value);

    feedback.innerHTML = '';
    if (!valor) {
        feedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Ingrese un valor.</span>';
        return;
    }

    const select = document.getElementById(_prSelectId);
    const yaExisteLocal = Array.from(select.options).some(
        opt => opt.value && opt.textContent.trim().toUpperCase() === valor.toUpperCase()
    );
    if (yaExisteLocal) {
        feedback.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> "' + valor + '" ya está en la lista. Selecciónelo directamente.</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

    fetch('{{ route("parametros.store.ajax") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ tipo: _prTipo, valor: valor }),
    })
    .then(async r => ({ status: r.status, data: await r.json() }))
    .then(({ status, data }) => {
        if (status === 409) {
            feedback.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + data.message + '</span>';
            if (data.existente && !Array.from(select.options).some(o => o.value == data.existente.valor)) {
                _insertarOpcionCategoriaPersonalizada(select, data.existente.valor);
            }
            if (data.existente) select.value = data.existente.valor;
            return;
        }
        if (!data.ok) {
            feedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> ' + (data.message || 'No se pudo guardar.') + '</span>';
            return;
        }

        _insertarOpcionCategoriaPersonalizada(select, data.item.valor);
        select.value = data.item.valor;
        select.dispatchEvent(new Event('change'));

        bootstrap.Modal.getInstance(document.getElementById('modalParametroRapido')).hide();
    })
    .catch(() => {
        feedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Error de conexión. Intente de nuevo.</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
    });
}
</script>
@endsection
