<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="tituloCliente">
                        <i class="bi bi-person"></i> Nuevo Cliente
                    </span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalCliente"
                            data-steps='[
                                {"intro":"📝 Este es el formulario para registrar un <b>cliente</b>. Te explico cada campo. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#cli_nombre","intro":"🏢 <b>Nombre / Razón Social</b>: el nombre del cliente o de su empresa. Se escribe en mayúsculas automáticamente. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#cli_nit","intro":"🔢 <b>NIT / CI / RUC</b>: el documento tributario o de identidad del cliente. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#cli_pais_id","intro":"🌎 <b>País</b> del cliente. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#cli_email","intro":"✉️ <b>Email</b> de contacto (opcional). Sirve para enviar comprobantes o comunicarse.","position":"bottom"},
                                {"element":"#telefonos-container","intro":"📞 <b>Teléfonos</b>: puedes agregar <b>varios</b> con el botón +.","position":"top"},
                                {"element":"#direcciones-container","intro":"📍 <b>Direcciones</b>: igual que los teléfonos, puedes registrar varias.","position":"top"},
                                {"element":"#btnCliente","intro":"💾 Cuando termines, pulsa <b>Registrar</b> para guardar el cliente.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <form id="formCliente" method="POST" action="{{ route('clientes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodCliente" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenCliente" value="{{ $idempotencyToken ?? '' }}">
                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NOMBRE / RAZON SOCIAL <strong class="text-danger">(*)</strong></label>
                            <input type="text" class="form-control" name="nombre" id="cli_nombre" onkeyup="this.value=this.value.toUpperCase();" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">NIT / CI / RUC <strong class="text-danger">(*)</strong></label>
                            <input type="number" class="form-control validar-nit {{ $errors->has('nit') ? 'is-invalid' : '' }}" name="nit" id="cli_nit" required value="{{ old('nit') }}">
                            @if($errors->has('nit'))
                                <div class="invalid-feedback d-block">{{ $errors->first('nit') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PAIS <strong class="text-danger">(*)</strong></label>
                            <select class="form-select" name="pais_id" id="cli_pais_id" required>
                                <option value="">-- SELECCIONE UN PAIS --</option>
                                @foreach($paises as $pais)
                                    <option value="{{ $pais->id }}">{{ $pais->valor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">EMAIL</label>
                            <input type="email" class="form-control validar-email" name="email" id="cli_email" placeholder="Ej. ejemplo@dominio.com">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>TELEFONOS</label>
                            <div id="telefonos-container"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">DIRECCIONES <strong class="text-danger">(*)</strong></label>
                            <div id="direcciones-container"></div>
                            <div id="direcciones-error" class="invalid-feedback d-none" style="display:none!important">
                                Debe registrar al menos una dirección.
                            </div>
                            @if($errors->has('direcciones') || $errors->has('direcciones.*'))
                                <div class="text-danger small mt-1">
                                    {{ $errors->first('direcciones') ?: $errors->first('direcciones.*') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnCliente">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>