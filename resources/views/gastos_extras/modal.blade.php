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
                                {"element":"#contrato","intro":"📄 <b>Contrato</b> al que pertenece este gasto.","position":"bottom"},
                                {"element":"#cuenta_bancaria","intro":"🏦 <b>Cuenta bancaria</b> desde la que se realizó o se realizará el pago.","position":"bottom"},
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
                            <label class="form-label">CONTRATO <strong class="text-danger">(*)</strong></label>
                            <select name="contrato_id" id="contrato" class="form-select" required>
                                <option value="">-- SELECCIONE --</option>
                                @foreach($contratos as $c)
                                    <option value="{{ $c->id }}">{{ $c->numero_contrato }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CUENTA BANCARIA <strong class="text-danger">(*)</strong></label>
                            <select name="cuenta_bancaria_id" id="cuenta_bancaria" class="form-select" required>
                                <option value="">-- SELECCIONE --</option>
                                @foreach($cuentas_banco as $cb)
                                    @php
                                        $cuenta = $cb->numero_cuenta;
                                        $visibleInicio = substr($cuenta, 0, 2);
                                        $visibleFinal = substr($cuenta, -4);
                                        $ocultos = str_repeat('*', max(strlen($cuenta) - 6, 0));
                                    @endphp
                                    <option value="{{ $cb->id }}">{{ $cb->banco->nombre }} {{ $visibleInicio . $ocultos . $visibleFinal }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CATEGORÍA <strong class="text-danger">(*)</strong></label>
                            <select name="categoria" id="categoria" class="form-select" required>
                                <option value="">-- SELECCIONE --</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->descripcion }}">{{ $cat->descripcion }}</option>
                                @endforeach
                                <option value="OTRO">OTRO</option>
                            </select>

                            <div id="contenedorNuevaCategoria" class="d-none mt-2">
                                <div class="input-group">
                                    <input type="text" name="nueva_categoria" id="nueva_categoria" class="form-control" placeholder="ESCRIBA LA NUEVA CATEGORÍA" onkeyup="this.value=this.value.toUpperCase();">
                                    <button type="button" class="btn btn-secondary" id="volverCategoria">
                                        <i class="bi bi-arrow-left"></i>
                                    </button>
                                </div>
                                <small id="mensaje_categoria" class="text-muted">Escriba una categoría nueva. Si ya existe, se usará la existente.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CONCEPTO <strong class="text-danger">(*)</strong></label>
                            <input type="text" name="concepto" id="concepto" class="form-control" onkeyup="this.value=this.value.toUpperCase();" required>
                            <small id="mensaje_concepto" class="text-muted">Mínimo 3 caracteres.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">MONTO <strong class="text-danger">(*)</strong></label>
                            <input type="text" inputmode="numeric" id="monto_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="monto" id="monto" value="">
                            <small id="mensaje_monto" class="text-muted">Ingrese un monto mayor a 0.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">MONEDA <strong class="text-danger">(*)</strong></label>
                            <select name="moneda" id="moneda" class="form-select" required>
                                <option value="BOB">BOB</option>
                                <option value="USD">USD</option>
                                <option value="BRL">BRL</option>
                            </select>
                        </div>

                        <div class="col-md-4" id="contenedor_tipo_cambio">
                            <label class="form-label">TIPO DE CAMBIO<span id="asterisco_tipo_cambio" class="text-danger d-none">(*)</span></label>
                            <input type="text" inputmode="numeric" id="tipo_cambio_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="tipo_cambio" id="tipo_cambio" value="">
                            <small id="mensaje_tipo_cambio" class="text-muted">No es necesario cuando la moneda es BOB.</small>
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
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                                <option value="">-- SELECCIONE --</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                <option value="QR">QR</option>
                            </select>
                            <small id="mensaje_metodo_pago" class="text-muted">Seleccione cómo se realizó el pago.</small>
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
                _initCajero('monto_display',      'monto',      function() { if(window.validarFormularioGastoExtra) validarFormularioGastoExtra(); });
                _initCajero('tipo_cambio_display', 'tipo_cambio', function() { if(window.validarFormularioGastoExtra) validarFormularioGastoExtra(); });

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
