<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="tituloProveedor">
                        <i class="bi bi-person"></i> Nuevo Proveedor
                    </span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalProveedor"
                            data-steps='[
                                {"intro":"📝 Este es el formulario para registrar un <b>proveedor</b>. Te explico cada campo. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#prov_nombre","intro":"🏢 <b>Nombre / Razón Social</b>: el nombre del proveedor o de su empresa. Se escribe en mayúsculas automáticamente. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#prov_nit","intro":"🔢 <b>NIT / CI / RUC</b>: el documento tributario o de identidad del proveedor. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#prov_pais","intro":"🌎 <b>País</b> del proveedor. Útil sobre todo para compras internacionales. <b>Obligatorio.</b>","position":"bottom"},
                                {"element":"#prov_email","intro":"✉️ <b>Email</b> de contacto (opcional). Sirve para enviar comprobantes o comunicarse.","position":"bottom"},
                                {"element":"#prov_tipo_producto","intro":"♻️ <b>Tipo de Producto</b>: qué tipo de chatarra o material suministra (ej: HIERRO, COBRE, ALUMINIO).","position":"bottom"},
                                {"element":"#telefonos-container","intro":"📞 <b>Teléfonos</b>: puedes agregar <b>varios</b> con el botón +. Útil para tener más de un contacto.","position":"top"},
                                {"element":"#direcciones-container","intro":"📍 <b>Direcciones</b>: igual que los teléfonos, puedes registrar varias.","position":"top"},
                                {"element":"#btnProveedor","intro":"💾 Cuando termines, pulsa <b>Registrar</b> para guardar el proveedor.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <form id="formProveedor" method="POST" action="{{ route('proveedores.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodProveedor" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenProveedor" value="{{ $idempotencyToken ?? '' }}">
                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">NOMBRE / RAZON SOCIAL <strong class="text-danger">(*)</strong></label>
                            <input type="text" class="form-control" name="nombre" id="prov_nombre" onkeyup="this.value=this.value.toUpperCase();" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">NIT / CI / RUC <strong class="text-danger">(*)</strong></label>
                            <input type="number" class="form-control validar-nit" name="nit" id="prov_nit" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">PAIS <strong class="text-danger">(*)</strong></label>
                            <select class="form-select" name="pais_id" id="prov_pais" required>
                                <option value="">-- SELECCIONE UN PAIS --</option>
                                @foreach($paises as $pais)
                                    <option value="{{ $pais->id }}">{{ $pais->valor ?? $pais->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">EMAIL</label>
                            <input type="email" class="form-control validar-email" name="email" id="prov_email" placeholder="Ej. ejemplo@dominio.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">TIPO DE PRODUCTO</label>
                            <input type="text" class="form-control" name="tipo_producto" id="prov_tipo_producto" onkeyup="this.value=this.value.toUpperCase();">
                        </div>

                         <div class="col-md-6 mt-2">
                            <label>TELEFONOS</label>
                            <div id="telefonos-container"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">DIRECCIONES</label>
                            <div id="direcciones-container"></div>                        
                        </div>
                </div>
            </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnProveedor">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>