<div class="modal fade" id="modalGastoExtra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="tituloGasto"><i class="bi bi-cash-coin"></i> Registrar Gasto Extra</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalGastoExtra"
                            data-steps='[
                                {"intro":"📝 Registra un gasto extra de un contrato. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#contrato","intro":"📄 <b>Contrato</b> al que pertenece este gasto (opcional). Déjelo vacío para gastos generales como luz o alquiler.","position":"bottom"},
                                {"element":"#cuenta_empresa","intro":"🏦 <b>Cuenta de empresa</b> desde la que se realizó o se realizará el pago. El monto se descuenta de su saldo al marcar el gasto como PAGADO.","position":"bottom"},
                                {"element":"#categoria","intro":"🏷️ <b>Categoría</b> del gasto (transporte, impuestos, etc.). Si eliges <b>OTRO</b>, podrás escribir una nueva.","position":"bottom"},
                                {"element":"#concepto","intro":"✏️ <b>Concepto</b>: una descripción breve del gasto (mínimo 3 caracteres).","position":"bottom"},
                                {"element":"#monto","intro":"💲 <b>Monto</b> del gasto.","position":"bottom"},
                                {"element":"#moneda","intro":"💱 <b>Moneda</b>. Si no es BOB, deberás indicar el tipo de cambio al lado.","position":"bottom"},
                                {"element":"#fecha","intro":"📅 <b>Fecha</b> del gasto.","position":"top"},
                                {"element":"#estado_switch","intro":"🔀 <b>Estado del pago</b>: marca si el gasto ya está PAGADO o queda PENDIENTE. Si está pagado, pide método de pago y comprobante.","position":"top"},
                                {"element":"#btnGasto","intro":"💾 Pulsa <b>Registrar</b> para guardar el gasto.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <form id="formGasto" method="POST" action="{{ route('gastos_extras.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodGasto" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenGasto" value="{{ $idempotencyToken ?? '' }}">

                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">CONTRATO</label>
                            <select name="contrato_id" id="contrato" class="form-select">
                                <option value="">-- SIN CONTRATO (gasto general) --</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}">{{ $c->numero_contrato }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Déjelo vacío para gastos generales no ligados a un contrato (luz, alquiler, etc.).</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CUENTA DE EMPRESA <strong class="text-danger">(*)</strong></label>
                            <select name="cuenta_empresa_id" id="cuenta_empresa" class="form-select" required>
                                <option value="">-- SELECCIONE --</option>
                                @foreach($cuentasEmpresa as $ce)
                                    <option value="{{ $ce->id }}" data-saldo="{{ $ce->saldo_actual }}">{{ $ce->nombre_cuenta }} (Bs {{ number_format($ce->saldo_actual, 2) }})</option>
                                @endforeach
                            </select>
                            <small id="mensaje_cuenta_empresa" class="text-muted"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CATEGORÍA <strong class="text-danger">(*)</strong></label>
                            <div class="input-group">
                                <select name="categoria" id="categoria" class="form-select" required>
                                    <option value="">-- SELECCIONE --</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->valor }}">{{ $cat->valor }}</option>
                                    @endforeach
                                </select>
                                @can('gastos_extras.create')
                                <button type="button" class="btn btn-outline-secondary" title="Agregar nueva categoría"
                                        onclick="abrirModalParametroRapido('categoria_gasto_extra', 'categoria', 'Nueva Categoría de Gasto Extra')">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                @endcan
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CONCEPTO <strong class="text-danger">(*)</strong></label>
                            <input type="text" name="concepto" id="concepto" class="form-control" maxlength="255" onkeyup="this.value=this.value.toUpperCase();" required>
                            <small id="mensaje_concepto" class="text-muted">Mínimo 3 caracteres, máximo 255.</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">MONEDA <strong class="text-danger">(*)</strong></label>
                            <select name="moneda" id="moneda" class="form-select" required onchange="toggleTipoCambioGasto(this.value)">
                                <option value="BOB">🇧🇴 BOB</option>
                                <option value="USD">🇺🇸 USD</option>
                                <option value="BRL">🇧🇷 BRL</option>
                                <option value="ARS">🇦🇷 ARS</option>
                                <option value="EUR">🇪🇺 EUR</option>
                                <option value="PEN">🇵🇪 PEN</option>
                                <option value="CLP">🇨🇱 CLP</option>
                                <option value="PYG">🇵🇾 PYG</option>
                                <option value="COP">🇨🇴 COP</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">MONTO <strong class="text-danger">(*)</strong></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold" id="lbl_moneda_monto_gasto">BOB</span>
                                <input type="text" inputmode="numeric" id="monto_display" class="form-control" placeholder="0,00" autocomplete="off">
                            </div>
                            <input type="hidden" name="monto" id="monto" value="">
                            <small id="mensaje_monto" class="text-muted">Ingrese un monto mayor a 0.</small>
                        </div>

                        <div class="col-md-6 d-none" id="contenedor_tipo_cambio_gasto">
                            <label class="form-label">
                                TIPO DE CAMBIO <strong class="text-danger">(*)</strong>
                                <small class="text-muted fw-normal">— 1 <span id="lbl_moneda_tc_gasto"></span> equivale a:</small>
                            </label>
                            <div class="input-group">
                                <input type="text" inputmode="numeric" id="tipo_cambio_display" class="form-control" placeholder="0,0000" autocomplete="off">
                                <input type="hidden" name="tipo_cambio" id="tipo_cambio" value="">
                                <span class="input-group-text">BOB</span>
                            </div>
                        </div>

                        <div class="col-12 d-none" id="contenedor_equivalente_gasto">
                            <div class="d-flex align-items-center gap-2 rounded-2 px-3 py-2" style="background:#e8f4fd;border:1px solid #b8d9f5;">
                                <i class="bi bi-arrow-left-right text-primary"></i>
                                <span class="text-muted small">Equivalente en bolivianos:</span>
                                <strong class="text-primary fs-6" id="lbl_equivalente_gasto">—</strong>
                                <span class="text-muted small">BOB</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">FECHA <strong class="text-danger">(*)</strong></label>
                            <input type="date" name="fecha" id="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                            <small id="mensaje_fecha" class="text-muted">Seleccione la fecha del gasto.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">ESTADO DEL PAGO <strong class="text-danger">(*)</strong></label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="estado_switch" checked>
                                <label class="form-check-label fw-bold" id="estado_label" for="estado_switch">PAGADO</label>
                            </div>
                            <input type="hidden" name="estado" id="estado" value="PAGADO">
                            <small id="mensaje_estado" class="text-success">Está marcando este gasto como PAGADO.</small>
                        </div>

                        <div class="col-md-6" id="contenedor_metodo_pago">
                            <label class="form-label">MÉTODO DE PAGO<span id="asterisco_metodo_pago" class="text-danger">(*)</span></label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required onchange="actualizarCodigoSeguimientoGasto()">
                                <option value="">-- SELECCIONE --</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                <option value="QR">QR</option>
                            </select>
                            <small id="mensaje_metodo_pago" class="text-muted">Seleccione cómo se realizó el pago.</small>
                        </div>

                        <div class="col-md-6 d-none" id="contenedor_codigo_seguimiento">
                            <label class="form-label">CÓDIGO DE TRANSFERENCIA<span class="text-danger">(*)</span></label>
                            <input type="text" name="codigo_seguimiento" id="codigo_seguimiento" class="form-control" placeholder="CÓDIGO DEL BANCO" onkeyup="this.value=this.value.toUpperCase();">
                            <small id="mensaje_codigo_seguimiento" class="text-muted">Ingrese el código de la transferencia.</small>
                        </div>

                        <div class="col-md-6 d-none" id="contenedor_codigo_qr_info">
                            <label class="form-label">CÓDIGO QR</label>
                            <input type="text" id="codigo_qr_info" class="form-control" readonly>
                            <small class="text-muted">Código interno generado automáticamente.</small>
                        </div>


                        <div class="col-md-6" id="contenedor_nombre_titular">
                            <label class="form-label">NOMBRE DEL TITULAR</label>
                            <input type="text" name="nombre_titular" id="nombre_titular" class="form-control" placeholder="NOMBRE DE QUIEN REALIZÓ EL PAGO" onkeyup="this.value=this.value.toUpperCase();">
                            <small id="mensaje_nombre_titular" class="text-muted">Opcional. Nombre de la persona que realizó el pago.</small>
                        </div>

                        <div class="col-md-6" id="contenedor_comprobante">
                            <label class="form-label">COMPROBANTE<span id="asterisco_comprobante" class="text-danger d-none">(*)</span></label>
                            <input type="file" name="comprobante_pago" id="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <small id="mensaje_comprobante" class="text-muted">Puede subir imagen o PDF del comprobante.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGasto">Registrar</button>
                </div>
            </form>
            <script>
            document.getElementById('formGasto').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnGasto');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });

            // ── Cajero monto y tipo_cambio ──
            (function() {
                function _txt2num(v) {
                    return parseFloat((v || '').replace(/\./g, '').replace(',', '.')) || 0;
                }
                function _fmt(n) {
                    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
                }
                function _initCajero(displayId, hiddenId, onChangeCb) {
                    var disp   = document.getElementById(displayId);
                    var hidden = document.getElementById(hiddenId);
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
                        if (onChangeCb) onChangeCb();
                    });
                    disp.addEventListener('blur', function() {
                        var n = _txt2num(this.value);
                        this.value   = n > 0 ? _fmt(n) : '';
                        hidden.value = n > 0 ? n : '';
                        if (onChangeCb) onChangeCb();
                    });
                }
                _initCajero('monto_display',      'monto',      function() { if(window.validarFormularioGastoExtra) validarFormularioGastoExtra(); actualizarSaldosCuentaGasto(); calcEquivalenteGasto(); });
                _initCajero('tipo_cambio_display', 'tipo_cambio', function() { if(window.validarFormularioGastoExtra) validarFormularioGastoExtra(); actualizarSaldosCuentaGasto(); calcEquivalenteGasto(); });

                // Igual patrón que Pago a Proveedores: si la moneda no es BOB, pide
                // tipo de cambio y muestra el equivalente en bolivianos en vivo.
                window.toggleTipoCambioGasto = function(moneda) {
                    var contTC     = document.getElementById('contenedor_tipo_cambio_gasto');
                    var contEquiv  = document.getElementById('contenedor_equivalente_gasto');
                    var tcDisp     = document.getElementById('tipo_cambio_display');
                    var tcHidden   = document.getElementById('tipo_cambio');
                    var lblMonto   = document.getElementById('lbl_moneda_monto_gasto');
                    var lblTC      = document.getElementById('lbl_moneda_tc_gasto');

                    lblMonto.textContent = moneda;

                    if (moneda === 'BOB') {
                        contTC.classList.add('d-none');
                        contEquiv.classList.add('d-none');
                        tcDisp.disabled = true;
                        tcDisp.value    = '';
                        tcHidden.value  = '';
                    } else {
                        contTC.classList.remove('d-none');
                        tcDisp.disabled = false;
                        lblTC.textContent = moneda;
                        calcEquivalenteGasto();
                    }
                    actualizarSaldosCuentaGasto();
                };

                window.calcEquivalenteGasto = function() {
                    var moneda = document.getElementById('moneda').value;
                    var contEquiv = document.getElementById('contenedor_equivalente_gasto');
                    if (moneda === 'BOB') { contEquiv.classList.add('d-none'); return; }
                    var monto = _txt2num(document.getElementById('monto').value);
                    var tc    = _txt2num(document.getElementById('tipo_cambio').value);
                    var lblEquiv = document.getElementById('lbl_equivalente_gasto');
                    if (monto > 0 && tc > 0) {
                        lblEquiv.textContent = 'Bs ' + _fmt(monto * tc);
                        contEquiv.classList.remove('d-none');
                    } else {
                        contEquiv.classList.add('d-none');
                    }
                };

                // ponytail: bloqueo de saldo insuficiente comentado a pedido del cliente,
                // mientras se ponen al día con los pagos de este mes. Restaurar cuando corresponda.
                // Antes: deshabilitaba en el select de Cuenta de Empresa las cuentas cuyo
                // saldo_actual (siempre en BOB) fuera menor al monto del gasto convertido
                // a bolivianos, y limpiaba la selección si dejaba de alcanzar.
                window.actualizarSaldosCuentaGasto = function() {
                    var selectCuenta = document.getElementById('cuenta_empresa');
                    var monto        = _txt2num(document.getElementById('monto_display').value);
                    var moneda       = document.getElementById('moneda').value;
                    var tipoCambio   = moneda === 'BOB' ? 1 : (_txt2num(document.getElementById('tipo_cambio_display').value) || 0);
                    var montoBs      = monto * tipoCambio;
                    if (!selectCuenta) return;

                    Array.from(selectCuenta.options).forEach(function(opt) {
                        if (!opt.value) return;
                        opt.disabled = false;
                    });

                    var mensaje = document.getElementById('mensaje_cuenta_empresa');
                    if (mensaje) {
                        mensaje.textContent = '';
                    }
                };

                // Buscador en el select de Contrato: se inicializa al abrir el modal.
                // Este bloque corre inline al parsear el HTML del contenido de la
                // página, antes de que jQuery/Select2 carguen al final del body
                // (layouts.partials.scripts). Se espera a DOMContentLoaded para
                // registrar el listener recién cuando "$" ya existe.
                document.addEventListener('DOMContentLoaded', function () {
                    $('#modalGastoExtra').on('shown.bs.modal', function () {
                        if (!$('#contrato').data('select2')) {
                            $('#contrato').select2({
                                dropdownParent: $('#modalGastoExtra'),
                                placeholder: '-- SELECCIONE --',
                                allowClear: true,
                                width: '100%',
                                language: {
                                    noResults: () => 'No se encontró ningún contrato.',
                                    searching: () => 'Buscando...'
                                }
                            });
                        }
                    });
                });

                // Exponer función para cargar valores al editar
                window._cargarGastoEnModal = window._cargarGastoEnModal || function() {};
                var _origCargar = window._cargarGastoEnModal;
                window._cargarCajeroGasto = function(monto, tipoCambio) {
                    var dm = document.getElementById('monto_display');
                    var hm = document.getElementById('monto');
                    var dt = document.getElementById('tipo_cambio_display');
                    var ht = document.getElementById('tipo_cambio');
                    if (dm && hm && monto > 0) { dm.value = _fmt(monto); hm.value = monto; }
                    if (dt && ht && tipoCambio > 0) { dt.value = _fmt(tipoCambio); ht.value = tipoCambio; }
                };
            })();
            </script>
        </div>
    </div>
</div>

{{-- ===== MODAL PARÁMETRO RÁPIDO (Nueva Categoría de Gasto Extra) ===== --}}
@can('gastos_extras.create')
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
                       placeholder="Ej: PEAJE" autocomplete="off" style="text-transform:uppercase"
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
<script>
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

function _normalizarValorParametroGasto(valor) {
    return valor.trim().replace(/\s+/g, ' ');
}

document.getElementById('pr_valor')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); guardarParametroRapido(); }
});

function guardarParametroRapido() {
    const input = document.getElementById('pr_valor');
    const feedback = document.getElementById('pr_feedback');
    const btn = document.getElementById('pr_btn_guardar');
    const valor = _normalizarValorParametroGasto(input.value);

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
            if (data.existente && !Array.from(select.options).some(o => o.value == data.existente.id)) {
                const op = document.createElement('option');
                op.value = data.existente.valor;
                op.textContent = data.existente.valor;
                select.appendChild(op);
            }
            if (data.existente) select.value = data.existente.valor;
            return;
        }
        if (!data.ok) {
            feedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> ' + (data.message || 'No se pudo guardar.') + '</span>';
            return;
        }

        const op = document.createElement('option');
        op.value = data.item.valor;
        op.textContent = data.item.valor;
        select.appendChild(op);
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
@endcan
