window.gastosExtrasCache = {};
function valorSeguro(valor, reemplazo = '-') {
    return valor === null || valor === undefined || valor === '' ? reemplazo : valor;
}
function fechaSolo(fecha) {
    if (!fecha) return '-';
    return fecha.toString().split('T')[0];
}
function escapeHtml(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
function guardarGastoEnCache(gasto) {
    const key = String(gasto.uuid ?? gasto.id ?? Math.random().toString(36).substring(2));
    window.gastosExtrasCache[key] = gasto;
    return key;
}

function obtenerGastoCache(key) {
    return window.gastosExtrasCache[String(key)] ?? null;
}

function contenedorPrincipalCampo(idCampo) {
    const campo = document.getElementById(idCampo);
    if (!campo) return null;
    let actual = campo.parentElement;
    while (actual && actual !== document.body) {
        const clases = Array.from(actual.classList || []);
        const esColumna = clases.some(clase =>
            clase === 'col-12' ||
            clase.startsWith('col-md-') ||
            clase.startsWith('col-lg-') ||
            clase.startsWith('col-sm-') ||
            clase.startsWith('col-xl-')
        );

        if (esColumna) return actual;
        actual = actual.parentElement;
    }
    return campo.closest('.mb-3, .form-group') ?? campo.parentElement;
}

function ocultarGrupoCampo(idCampo) {
    const campo = document.getElementById(idCampo);
    const contenedor = contenedorPrincipalCampo(idCampo);
    if (campo) {
        if (campo.required) campo.dataset.requiredAntesMarcarPagado = '1';
        campo.required = false;
    }
    if (contenedor) {
        contenedor.classList.add('d-none');
        contenedor.dataset.ocultoMarcarPagado = '1';
    }
}

function mostrarGrupoCampo(idCampo) {
    const campo = document.getElementById(idCampo);
    const contenedor = contenedorPrincipalCampo(idCampo);

    if (contenedor) {
        contenedor.classList.remove('d-none');
        delete contenedor.dataset.ocultoMarcarPagado;
    }
    if (campo && campo.dataset.requiredAntesMarcarPagado === '1') {
        campo.required = true;
        delete campo.dataset.requiredAntesMarcarPagado;
    }
}

function restaurarCamposModalGasto() {
    document.querySelectorAll('[data-oculto-marcar-pagado="1"]').forEach(contenedor => {
        contenedor.classList.remove('d-none');
        delete contenedor.dataset.ocultoMarcarPagado;
    });
    document.querySelectorAll('[data-required-antes-marcar-pagado="1"]').forEach(campo => {
        campo.required = true;
        delete campo.dataset.requiredAntesMarcarPagado;
    });

    const campos = [
        'contrato',
        'cuenta_bancaria',
        'categoria',
        'nueva_categoria',
        'concepto',
        'monto',
        'moneda',
        'tipo_cambio',
        'fecha',
        'estado_switch',
        'metodo_pago',
        'nombre_titular',
        'comprobante'
    ];

    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (!campo) return;
        campo.disabled = false;
        campo.readOnly = false;
        campo.classList.remove('bg-light');
    });
}

function quitarBloqueoCamposGasto() {
    document.querySelectorAll('.mirror-pago-hidden').forEach(input => input.remove());
    restaurarCamposModalGasto();
}

function ocultarCamposParaMarcarPagado() {
    restaurarCamposModalGasto();
    const camposQueNoSeEditan = [
        'contrato',
        'cuenta_bancaria',
        'categoria',
        'nueva_categoria',
        'concepto',
        'monto',
        'moneda',
        'tipo_cambio',
        'estado_switch'
    ];

    camposQueNoSeEditan.forEach(id => ocultarGrupoCampo(id));
    const contenedorNuevaCategoria = document.getElementById('contenedorNuevaCategoria');
    if (contenedorNuevaCategoria) {
        contenedorNuevaCategoria.classList.add('d-none');
    }
    const categoria = document.getElementById('categoria');
    if (categoria) {
        categoria.classList.remove('d-none');
    }
    const camposEditables = ['fecha','metodo_pago', 'nombre_titular', 'comprobante'];
    camposEditables.forEach(id => {
        mostrarGrupoCampo(id);
        const campo = document.getElementById(id);
        if (!campo) return;
        campo.disabled = false;
        campo.readOnly = false;
    });

    const metodoPago = document.getElementById('metodo_pago');
    if (metodoPago) {
        metodoPago.required = true;
        setTimeout(() => metodoPago.focus(), 250);
    }
    const nombreTitular = document.getElementById('nombre_titular');
    if (nombreTitular) {
        nombreTitular.required = false;
    }
    const comprobante = document.getElementById('comprobante');
    if (comprobante) {
        comprobante.required = false;
    }
    const mensajeEstado = document.getElementById('mensaje_estado');
    if (mensajeEstado) {
        mensajeEstado.innerText = 'Complete solo los datos del pago.';
        mensajeEstado.className = 'text-success';
    }
}
window.limpiarFormularioGastoExtra = function () {
    quitarBloqueoCamposGasto();
    const form = document.getElementById('formGasto');
    if (form) form.reset();
    const categoria = document.getElementById('categoria');
    const contenedorNuevaCategoria = document.getElementById('contenedorNuevaCategoria');
    const nuevaCategoria = document.getElementById('nueva_categoria');
    const estadoSwitch = document.getElementById('estado_switch');
    const estado = document.getElementById('estado');
    const estadoLabel = document.getElementById('estado_label');
    if (categoria) categoria.classList.remove('d-none');
    if (contenedorNuevaCategoria) contenedorNuevaCategoria.classList.add('d-none');
    if (nuevaCategoria) {
        nuevaCategoria.required = false;
        nuevaCategoria.value = '';
    }
    if (estadoSwitch) estadoSwitch.checked = true;
    if (estado) estado.value = 'PAGADO';
    if (estadoLabel) estadoLabel.innerText = 'PAGADO';
    limpiarValidacionesVisuales();
    actualizarEstadoPago();
    actualizarTipoCambio();
};
function editarGastoPorKey(key) {
    const gasto = obtenerGastoCache(key);
    if (!gasto) {
        alert('No se pudo cargar la información del gasto. Vuelva a abrir el detalle del contrato.');
        return;
    }
    editarGasto(gasto);
}

function editarGasto(gasto) {
    quitarBloqueoCamposGasto();
    const baseUrl = window.gastosExtrasConfig.updateBaseUrl;
    document.getElementById('tituloGasto').innerText = 'Editar Gasto';
    document.getElementById('btnGasto').innerText = 'Actualizar';
    document.getElementById('methodGasto').value = 'PUT';
     document.getElementById('formGasto').action = baseUrl + '/' + gasto.uuid;
    document.getElementById('categoria').classList.remove('d-none');
    document.getElementById('contenedorNuevaCategoria').classList.add('d-none');
    document.getElementById('nueva_categoria').required = false;
    document.getElementById('nueva_categoria').value = '';
    document.getElementById('categoria').value = gasto.categoria ?? '';
    document.getElementById('concepto').value = gasto.concepto ?? '';
    document.getElementById('monto').value = gasto.monto ?? '';
    document.getElementById('moneda').value = gasto.moneda ?? '';
    document.getElementById('tipo_cambio').value = gasto.tipo_cambio ?? '';
    document.getElementById('nombre_titular').value = gasto.nombre_titular ?? '';
    document.getElementById('fecha').value = fechaSolo(gasto.fecha) === '-' ? '' : fechaSolo(gasto.fecha);
    document.getElementById('cuenta_bancaria').value = gasto.cuenta_bancaria_id ?? '';
    document.getElementById('contrato').value = gasto.contrato_id ?? '';
    document.getElementById('metodo_pago').value = gasto.metodo_pago ?? '';
    if (document.getElementById('tipo_pago')) {
        document.getElementById('tipo_pago').value = gasto.tipo_pago ?? '';
    }
    if (gasto.estado === 'PAGADO') {
        document.getElementById('estado_switch').checked = true;
        document.getElementById('estado').value = 'PAGADO';
        document.getElementById('estado_label').innerText = 'PAGADO';
    } else {
        document.getElementById('estado_switch').checked = false;
        document.getElementById('estado').value = 'PENDIENTE';
        document.getElementById('estado_label').innerText = 'PENDIENTE';
    }
    limpiarValidacionesVisuales();
    actualizarEstadoPago();
    actualizarTipoCambio();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGastoExtra')).show();
}
function marcarPagadoPorKey(key) {
    const gasto = obtenerGastoCache(key);
    if (!gasto) {
        alert('No se pudo cargar la información del gasto. Vuelva a abrir el detalle del contrato.');
        return;
    }
    marcarPagado(gasto);
}
function marcarPagado(gasto) {
    quitarBloqueoCamposGasto();
    const baseUrl = window.gastosExtrasConfig.updateBaseUrl;
    document.getElementById('tituloGasto').innerText = 'Marcar Gasto como PAGADO';
    document.getElementById('btnGasto').innerText = 'Marcar PAGADO';
    document.getElementById('methodGasto').value = 'PUT';
    document.getElementById('formGasto').action = baseUrl + '/' + gasto.uuid;
    document.getElementById('categoria').classList.remove('d-none');
    document.getElementById('contenedorNuevaCategoria').classList.add('d-none');
    document.getElementById('nueva_categoria').required = false;
    document.getElementById('nueva_categoria').value = '';
    document.getElementById('categoria').value = gasto.categoria ?? '';
    document.getElementById('concepto').value = gasto.concepto ?? '';
    document.getElementById('monto').value = gasto.monto ?? '';
    document.getElementById('moneda').value = gasto.moneda ?? '';
    document.getElementById('tipo_cambio').value = gasto.tipo_cambio ?? '';
    document.getElementById('nombre_titular').value = gasto.nombre_titular ?? '';
    document.getElementById('fecha').value = fechaSolo(gasto.fecha) === '-' ? '' : fechaSolo(gasto.fecha);
    document.getElementById('cuenta_bancaria').value = gasto.cuenta_bancaria_id ?? '';
    document.getElementById('contrato').value = gasto.contrato_id ?? '';
    document.getElementById('metodo_pago').value = gasto.metodo_pago ?? '';
    if (document.getElementById('tipo_pago')) {
        document.getElementById('tipo_pago').value = gasto.tipo_pago ?? '';
    }
    document.getElementById('estado_switch').checked = true;
    document.getElementById('estado').value = 'PAGADO';
    document.getElementById('estado_label').innerText = 'PAGADO';
    limpiarValidacionesVisuales();
    actualizarEstadoPago();
    actualizarTipoCambio();
    ocultarCamposParaMarcarPagado();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGastoExtra')).show();
}
function limpiarValidacionesVisuales() {
    const campos = ['concepto', 'monto', 'nombre_titular', 'fecha', 'comprobante', 'metodo_pago'];
    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) campo.classList.remove('is-valid', 'is-invalid');
    });
    const mensajes = {
        mensaje_concepto: ['Mínimo 3 caracteres.', 'text-muted'],
        mensaje_monto: ['Ingrese un monto mayor a 0.', 'text-muted'],
        mensaje_fecha: ['Seleccione la fecha del gasto.', 'text-muted'],
        mensaje_nombre_titular: ['Opcional. Nombre de la persona que realizó el pago.', 'text-muted'],
        mensaje_comprobante: ['Puede subir imagen o PDF del comprobante.', 'text-muted'],
        mensaje_metodo_pago: ['Seleccione cómo se realizó el pago.', 'text-muted']
    };
    Object.keys(mensajes).forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.innerText = mensajes[id][0];
            elemento.className = mensajes[id][1];
        }
    });
}
function mostrarContenedor(id, mostrar) {
    const elemento = document.getElementById(id);
    if (!elemento) return;
    if (mostrar) {
        elemento.classList.remove('d-none');
    } else {
        elemento.classList.add('d-none');
    }
}

function actualizarEstadoPago() {
    const estadoSwitch = document.getElementById('estado_switch');
    const estadoInput = document.getElementById('estado');
    const estadoLabel = document.getElementById('estado_label');
    const mensajeEstado = document.getElementById('mensaje_estado');
    const metodoPago = document.getElementById('metodo_pago');
    const nombreTitular = document.getElementById('nombre_titular');
    const comprobante = document.getElementById('comprobante');
    const asteriscoMetodoPago = document.getElementById('asterisco_metodo_pago');
    if (!estadoSwitch || !estadoInput || !estadoLabel) return;
    if (estadoSwitch.checked) {
        estadoInput.value = 'PAGADO';
        estadoLabel.innerText = 'PAGADO';
        if (mensajeEstado) {
            mensajeEstado.innerText = 'Está marcando este gasto como PAGADO.';
            mensajeEstado.className = 'text-success';
        }
        mostrarContenedor('contenedor_metodo_pago', true);
        mostrarContenedor('contenedor_nombre_titular', true);
        mostrarContenedor('contenedor_comprobante', true);
        if (metodoPago) {
            metodoPago.required = true;
            metodoPago.disabled = false;
        }
        if (nombreTitular) {
            nombreTitular.required = false;
            nombreTitular.disabled = false;
        }
        if (comprobante) {
            comprobante.required = false;
            comprobante.disabled = false;
        }
        if (asteriscoMetodoPago) {
            asteriscoMetodoPago.classList.remove('d-none');
        }
    } else {
        estadoInput.value = 'PENDIENTE';
        estadoLabel.innerText = 'PENDIENTE';
        if (mensajeEstado) {
            mensajeEstado.innerText = 'Está marcando este gasto como NO PAGADO / PENDIENTE.';
            mensajeEstado.className = 'text-warning';
        }
        mostrarContenedor('contenedor_metodo_pago', false);
        mostrarContenedor('contenedor_nombre_titular', false);
        mostrarContenedor('contenedor_comprobante', false);
        if (metodoPago) {
            metodoPago.required = false;
            metodoPago.value = '';
            metodoPago.disabled = true;
        }
        if (nombreTitular) {
            nombreTitular.required = false;
            nombreTitular.value = '';
            nombreTitular.disabled = true;
        }
        if (comprobante) {
            comprobante.required = false;
            comprobante.value = '';
            comprobante.disabled = true;
        }
        if (asteriscoMetodoPago) {
            asteriscoMetodoPago.classList.add('d-none');
        }
    }
}
function actualizarTipoCambio() {
    const moneda = document.getElementById('moneda');
    const monto = document.getElementById('monto');
    const tipoCambioInput = document.getElementById('tipo_cambio');
    const asteriscoTipoCambio = document.getElementById('asterisco_tipo_cambio');
    const mensajeTipoCambio = document.getElementById('mensaje_tipo_cambio');
    if (!moneda || !monto || !tipoCambioInput) return;
    const monedaValor = moneda.value;
    const montoValor = parseFloat(monto.value) || 0;
    const tipoCambioValor = parseFloat(tipoCambioInput.value) || 0;

    if (monedaValor === 'BOB') {
        tipoCambioInput.value = '';
        tipoCambioInput.disabled = true;
        tipoCambioInput.required = false;
        if (asteriscoTipoCambio) asteriscoTipoCambio.classList.add('d-none');
        mostrarContenedor('contenedor_tipo_cambio', false);
        if (mensajeTipoCambio) {
            mensajeTipoCambio.innerText = 'No es necesario ingresar tipo de cambio cuando la moneda es BOB.';
            mensajeTipoCambio.className = 'text-muted';
        }
        return;
    }

    mostrarContenedor('contenedor_tipo_cambio', true);
    tipoCambioInput.disabled = false;
    tipoCambioInput.required = true;
    if (asteriscoTipoCambio) asteriscoTipoCambio.classList.remove('d-none');
    if (!mensajeTipoCambio) return;
    if (montoValor > 0 && tipoCambioValor > 0) {
        const totalBob = montoValor * tipoCambioValor;
        mensajeTipoCambio.innerHTML = `${montoValor.toFixed(2)} ${monedaValor} = <strong>${totalBob.toFixed(2)} BOB</strong>`;
        mensajeTipoCambio.className = 'text-success';
    } else {
        mensajeTipoCambio.innerText = 'Ingrese el monto y el tipo de cambio para calcular el equivalente en BOB.';
        mensajeTipoCambio.className = 'text-warning';
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const categoria = document.getElementById('categoria');
    const contenedorNueva = document.getElementById('contenedorNuevaCategoria');
    const nuevaCategoria = document.getElementById('nueva_categoria');
    const volverCategoria = document.getElementById('volverCategoria');
    const estadoSwitch = document.getElementById('estado_switch');
    const estadoInput = document.getElementById('estado');
    const metodoPago = document.getElementById('metodo_pago');
    const nombreTitular = document.getElementById('nombre_titular');
    const comprobante = document.getElementById('comprobante');
    const monto = document.getElementById('monto');
    const moneda = document.getElementById('moneda');
    const tipoCambioInput = document.getElementById('tipo_cambio');
    const concepto = document.getElementById('concepto');
    const fecha = document.getElementById('fecha');
    if (categoria) {
        categoria.addEventListener('change', function () {
            if (this.value === 'OTRO') {
                categoria.classList.add('d-none');
                if (contenedorNueva) contenedorNueva.classList.remove('d-none');
                if (nuevaCategoria) {
                    nuevaCategoria.required = true;
                    nuevaCategoria.focus();
                }
            }
        });
    }

    if (volverCategoria) {
        volverCategoria.addEventListener('click', function () {
            if (categoria) {
                categoria.classList.remove('d-none');
                categoria.value = '';
            }
            if (contenedorNueva) contenedorNueva.classList.add('d-none');
            if (nuevaCategoria) {
                nuevaCategoria.value = '';
                nuevaCategoria.required = false;
            }
        });
    }

    if (estadoSwitch) estadoSwitch.addEventListener('change', actualizarEstadoPago);
    if (metodoPago) {
        metodoPago.addEventListener('change', function () {
            const mensaje = document.getElementById('mensaje_metodo_pago');
            if (!mensaje) return;
            if (estadoInput && estadoInput.value === 'PAGADO' && this.value === '') {
                mensaje.innerText = 'Debe seleccionar un método de pago si el gasto está pagado.';
                mensaje.className = 'text-danger';
            } else if (this.value !== '') {
                mensaje.innerText = 'Método de pago seleccionado correctamente.';
                mensaje.className = 'text-success';
            } else {
                mensaje.innerText = 'El método de pago no es obligatorio si está pendiente.';
                mensaje.className = 'text-muted';
            }
        });
    }

    if (nombreTitular) {
        nombreTitular.addEventListener('input', function () {
            const valor = this.value.trim().toUpperCase();
            const mensaje = document.getElementById('mensaje_nombre_titular');
            if (valor.length === 0) {
                this.classList.remove('is-invalid', 'is-valid');
                if (mensaje) {
                    mensaje.innerText = 'Opcional. Nombre de la persona que realizó el pago.';
                    mensaje.className = 'text-muted';
                }
                return;
            }
            const regex = /^[A-ZÁÉÍÓÚÑ ]+$/;
            if (!regex.test(valor) || valor.length < 5) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                if (mensaje) {
                    mensaje.innerText = 'Ingrese un nombre válido (mínimo 5 caracteres y solo letras).';
                    mensaje.className = 'text-danger';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');

                if (mensaje) {
                    mensaje.innerText = 'Nombre válido.';
                    mensaje.className = 'text-success';
                }
            }
        });
    }
    if (monto) {
        monto.addEventListener('input', function () {
            actualizarTipoCambio();
            const mensaje = document.getElementById('mensaje_monto');
            if (parseFloat(this.value) <= 0 || this.value === '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                if (mensaje) {
                    mensaje.innerText = 'El monto debe ser mayor a 0.';
                    mensaje.className = 'text-danger';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                if (mensaje) {
                    mensaje.innerText = 'Monto válido.';
                    mensaje.className = 'text-success';
                }
            }
        });
    }
    if (moneda) moneda.addEventListener('change', actualizarTipoCambio);
    if (tipoCambioInput) tipoCambioInput.addEventListener('input', actualizarTipoCambio);
    if (concepto) {
        concepto.addEventListener('input', function () {
            const mensaje = document.getElementById('mensaje_concepto');

            if (this.value.trim().length < 3) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');

                if (mensaje) {
                    mensaje.innerText = 'El concepto debe tener mínimo 3 caracteres.';
                    mensaje.className = 'text-danger';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');

                if (mensaje) {
                    mensaje.innerText = 'Concepto válido.';
                    mensaje.className = 'text-success';
                }
            }
        });
    }

    if (fecha) {
        fecha.addEventListener('change', function () {
            const mensaje = document.getElementById('mensaje_fecha');
            if (this.value === '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');

                if (mensaje) {
                    mensaje.innerText = 'Debe seleccionar una fecha.';
                    mensaje.className = 'text-danger';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                if (mensaje) {
                    mensaje.innerText = 'Fecha válida.';
                    mensaje.className = 'text-success';
                }
            }
        });
    }

    if (comprobante) {
        comprobante.addEventListener('change', function () {
            const archivo = this.files[0];
            const mensaje = document.getElementById('mensaje_comprobante');
            if (!archivo) {
                if (mensaje) {
                    mensaje.innerText = 'Puede subir imagen o PDF del comprobante.';
                    mensaje.className = 'text-muted';
                }
                return;
            }
            const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
            const extension = archivo.name.split('.').pop().toLowerCase();
            if (!extensionesPermitidas.includes(extension)) {
                this.value = '';
                if (mensaje) {
                    mensaje.innerText = 'Formato no permitido. Solo JPG, PNG o PDF.';
                    mensaje.className = 'text-danger';
                }
                return;
            }
            if (archivo.size > 2 * 1024 * 1024) {
                this.value = '';

                if (mensaje) {
                    mensaje.innerText = 'El comprobante no debe superar los 2MB.';
                    mensaje.className = 'text-danger';
                }
                return;
            }
            if (mensaje) {
                mensaje.innerText = 'Comprobante válido.';
                mensaje.className = 'text-success';
            }
        });
    }
    actualizarEstadoPago();
    actualizarTipoCambio();
});