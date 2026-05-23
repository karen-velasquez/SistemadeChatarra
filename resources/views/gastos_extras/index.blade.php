@extends('layouts.app')
@section('titulo','Gastos Extras')

@section('content')
<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>GASTOS EXTRAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active">Gastos Extras</li>
                </ol>
            </nav>
        </div>

        @can('gastos_extras.create')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGastoExtra" onclick="resetModalGastoExtra()">
                <i class="bi bi-plus-lg"></i> Nuevo Gasto Extra
            </button>
        @endcan
    </div>
</div>

<section class="section mt-3">
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold">Total Gastos</small>
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($total,2) }}</h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-cash-stack text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold">Pendientes</small>
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($pendientes,2) }}</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-hourglass-split text-warning fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold">Pagados</small>
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($pagados,2) }}</h4>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-check-circle text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold">Contratos</small>
                        <h4 class="fw-bold mt-2 mb-0">{{ $contratosFiltrados->count() }}</h4>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-file-earmark-text text-info fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body pt-3">
            <form method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por proveedor</label>
                        <select name="proveedor_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- TODOS LOS PROVEEDORES --</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ request('proveedor_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        En esta sección puedes registrar y gestionar gastos adicionales relacionados con tus contratos de compra de chatarra. Estos gastos pueden incluir gastos de transporte, impuestos u otros costos asociados.
    </p>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Contratos</h5>
                    </div>

                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Seleccione un contrato para visualizar todos sus gastos extras
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Total BOB</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contratosFiltrados as $c)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $c->numero_contrato }}</div>
                                            <small class="text-muted">{{ $c->proveedor->nombre ?? '-' }}</small>
                                        </td>
                                        <td><strong>BOB {{ number_format($c->total_gastos,2) }}</strong></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick='verDetalles(@json($c->gastos_detalle), "{{ $c->numero_contrato }}")'>
                                                <i class="bi bi-eye"></i> Ver Detalles
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Sin contratos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Detalle de Gastos</h5>
                            <small id="tituloDetalle" class="text-muted">Seleccione un contrato</small>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoría</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Método de Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="detalleGastos">
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Sin información</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('gastos_extras.modal')
@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}"></script>
<script>
function valorSeguro(valor, reemplazo = '-') {
    return valor === null || valor === undefined || valor === '' ? reemplazo : valor;
}
function fechaSolo(fecha) {
    if (!fecha) {
        return '-';
    }
    return fecha.toString().split('T')[0];
}

function verDetalles(gastos, contrato) {
    let html = '';
    document.getElementById('tituloDetalle').innerText = 'Contrato: ' + contrato;
    if (gastos.length === 0) {
        html = `<tr><td colspan="8" class="text-center text-muted">Sin gastos registrados</td></tr>`;
    } else {
        gastos.forEach(g => {
            let comprobante = '';
            if (g.estado === 'PAGADO') {
                if (g.comprobante_pago) {
                    comprobante = `<li><a class="dropdown-item" href="#" onclick="window.open('comprobantes_pago/${g.comprobante_pago}','comprobante','width=700,height=500,resizable=yes,scrollbars=yes'); return false;"><i class="bi bi-receipt"></i> Ver Comprobante</a></li>`;
                } else {
                    comprobante = `<li><a class="dropdown-item text-muted"><i class="bi bi-receipt"></i> Sin Comprobante</a></li>`;
                }
            }
            let marcarPagado = '';
            if (g.estado === 'PENDIENTE') {
                marcarPagado = `<li><a class="dropdown-item"><i class="bi bi-cash"></i> Marcar PAGADO</a></li>`;
            }
            html += `
                <tr>
                    <td>${fechaSolo(g.fecha)}</td>
                    <td><span class="badge bg-primary">${valorSeguro(g.categoria)}</span></td>
                    <td>${valorSeguro(g.concepto)}</td>
                    <td>${valorSeguro(g.moneda)} ${parseFloat(g.monto || 0).toFixed(2)}</td>
                    <td>${g.estado === 'PAGADO' ? '<span class="badge bg-success">PAGADO</span>' : '<span class="badge bg-warning text-dark">PENDIENTE</span>'}</td>
                    <td>${g.estado === 'PAGADO' ? valorSeguro(g.metodo_pago) : '-'}</td>
                    <td class="text-center">
                        <div class="btn-group"><button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button><ul class="dropdown-menu">

    ${
        g.estado === 'PENDIENTE' ? `
        @can('gastos_extras.edit')
        <li><a class="dropdown-item" href="#" onclick='editarGasto(${JSON.stringify(g)})'><i class="bi bi-pencil"></i> Modificar</a></li>
        @endcan` : `
        <li><a class="dropdown-item text-muted"><i class="bi bi-lock"></i> Gasto Pagado</a></li>`
    }
        ${comprobante} ${marcarPagado}
    ${
        g.estado === 'PENDIENTE' ? `
        @can('gastos_extras.destroy')
        <li><a class="dropdown-item text-danger" href="/gastos_extras/${g.uuid}/destroy" onclick="return confirm('¿Eliminar este gasto extra?')"><i class="bi bi-trash"></i> Eliminar</a></li>
        @endcan`:''}
                        </ul>
                        </div>
                    </td>
                </tr>`;
        });
    }

    document.getElementById('detalleGastos').innerHTML = html;
}

window.limpiarFormularioGastoExtra = function () {
    const form = document.getElementById('formGasto');
    form.reset();
    document.getElementById('categoria').classList.remove('d-none');
    document.getElementById('contenedorNuevaCategoria').classList.add('d-none');
    document.getElementById('nueva_categoria').required = false;
    document.getElementById('nueva_categoria').value = '';
    document.getElementById('estado_switch').checked = true;
    document.getElementById('estado').value = 'PAGADO';
    document.getElementById('estado_label').innerText = 'PAGADO';
    limpiarValidacionesVisuales();
    actualizarEstadoPago();
    actualizarTipoCambio();
};

function resetModalGastoExtra() {
    document.getElementById('tituloGasto').innerHTML = '<i class="bi bi-cash-coin"></i> Registrar Gasto Extra';
    document.getElementById('btnGasto').innerText = 'Registrar';
    document.getElementById('methodGasto').value = 'POST';
    document.getElementById('formGasto').action = '{{ route("gastos_extras.store") }}';
    limpiarFormularioGastoExtra();
}

function editarGasto(gasto) {
    const baseUrl = "{{ url('/') }}";

    document.getElementById('tituloGasto').innerText = 'Editar Gasto';
    document.getElementById('btnGasto').innerText = 'Actualizar';
    document.getElementById('methodGasto').value = 'PUT';
    document.getElementById('formGasto').action = baseUrl + '/gastos_extras/' + gasto.id;
    document.getElementById('categoria').classList.remove('d-none');
    document.getElementById('contenedorNuevaCategoria').classList.add('d-none');
    document.getElementById('nueva_categoria').required = false;
    document.getElementById('nueva_categoria').value = '';
    document.getElementById('categoria').value = gasto.categoria;
    document.getElementById('concepto').value = gasto.concepto;
    document.getElementById('monto').value = gasto.monto;
    document.getElementById('moneda').value = gasto.moneda;
    document.getElementById('tipo_cambio').value = gasto.tipo_cambio ?? '';
    document.getElementById('nombre_titular').value = gasto.nombre_titular ?? '';
    document.getElementById('fecha').value = fechaSolo(gasto.fecha);
    document.getElementById('cuenta_bancaria').value = gasto.cuenta_bancaria_id;
    document.getElementById('contrato').value = gasto.contrato_id;
    document.getElementById('metodo_pago').value = gasto.metodo_pago ?? '';
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

function limpiarValidacionesVisuales() {
    const campos = ['concepto', 'monto', 'nombre_titular', 'fecha', 'comprobante'];

    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.classList.remove('is-valid', 'is-invalid');
        }
    });

    document.getElementById('mensaje_concepto').innerText = 'Mínimo 3 caracteres.';
    document.getElementById('mensaje_concepto').className = 'text-muted';
    document.getElementById('mensaje_monto').innerText = 'Ingrese un monto mayor a 0.';
    document.getElementById('mensaje_monto').className = 'text-muted';
    document.getElementById('mensaje_fecha').innerText = 'Seleccione la fecha del gasto.';
    document.getElementById('mensaje_fecha').className = 'text-muted';
    document.getElementById('mensaje_nombre_titular').innerText = 'Opcional. Nombre de la persona que realizó el pago.';
    document.getElementById('mensaje_nombre_titular').className = 'text-muted';
    document.getElementById('mensaje_comprobante').innerText = 'Puede subir imagen o PDF del comprobante.';
    document.getElementById('mensaje_comprobante').className = 'text-muted';
    document.getElementById('mensaje_metodo_pago').innerText = 'Seleccione cómo se realizó el pago.';
    document.getElementById('mensaje_metodo_pago').className = 'text-muted';
  }

function mostrarContenedor(id, mostrar) {
    const elemento = document.getElementById(id);
    if (!elemento) {
        return;
    }

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

    if (estadoSwitch.checked) {
        estadoInput.value = 'PAGADO';
        estadoLabel.innerText = 'PAGADO';
        mensajeEstado.innerText = 'Está marcando este gasto como PAGADO.';
        mensajeEstado.className = 'text-success';

        mostrarContenedor('contenedor_metodo_pago', true);
        mostrarContenedor('contenedor_nombre_titular', true);
        mostrarContenedor('contenedor_comprobante', true);

        metodoPago.required = true;
        metodoPago.disabled = false;

        nombreTitular.required = false;
        nombreTitular.disabled = false;
        comprobante.required = false;
        comprobante.disabled = false;
        asteriscoMetodoPago.classList.remove('d-none');
    } else {
        estadoInput.value = 'PENDIENTE';
        estadoLabel.innerText = 'PENDIENTE';
        mensajeEstado.innerText = 'Está marcando este gasto como NO PAGADO / PENDIENTE.';
        mensajeEstado.className = 'text-warning';

        mostrarContenedor('contenedor_metodo_pago', false);
        mostrarContenedor('contenedor_nombre_titular', false);
        mostrarContenedor('contenedor_comprobante', false);

        metodoPago.required = false;
        metodoPago.value = '';
        metodoPago.disabled = true;

        nombreTitular.required = false;
        nombreTitular.value = '';
        nombreTitular.disabled = true;

        comprobante.required = false;
        comprobante.value = '';
        comprobante.disabled = true;

        asteriscoMetodoPago.classList.add('d-none');

        document.getElementById('mensaje_metodo_pago').innerText = 'El método de pago no es obligatorio si está pendiente.';
        document.getElementById('mensaje_metodo_pago').className = 'text-muted';
        document.getElementById('mensaje_nombre_titular').innerText = 'Solo se solicita cuando el gasto está pagado.';
        document.getElementById('mensaje_nombre_titular').className = 'text-muted';
        document.getElementById('mensaje_comprobante').innerText = 'Solo se solicita cuando el gasto está pagado.';
        document.getElementById('mensaje_comprobante').className = 'text-muted';
    }
}

function actualizarTipoCambio() {
    const moneda = document.getElementById('moneda');
    const monto = document.getElementById('monto');
    const tipoCambioInput = document.getElementById('tipo_cambio');
    const asteriscoTipoCambio = document.getElementById('asterisco_tipo_cambio');
    const mensajeTipoCambio = document.getElementById('mensaje_tipo_cambio');

    const monedaValor = moneda.value;
    const montoValor = parseFloat(monto.value) || 0;
    const tipoCambioValor = parseFloat(tipoCambioInput.value) || 0;

    if (monedaValor === 'BOB') {
        tipoCambioInput.value = '';
        tipoCambioInput.disabled = true;
        tipoCambioInput.required = false;
        asteriscoTipoCambio.classList.add('d-none');
        mostrarContenedor('contenedor_tipo_cambio', false);
        mensajeTipoCambio.innerText = 'No es necesario ingresar tipo de cambio cuando la moneda es BOB.';
        mensajeTipoCambio.className = 'text-muted';
        return;
    }

    mostrarContenedor('contenedor_tipo_cambio', true);
    tipoCambioInput.disabled = false;
    tipoCambioInput.required = true;
    asteriscoTipoCambio.classList.remove('d-none');

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

    categoria.addEventListener('change', function () {
        if (this.value === 'OTRO') {
            categoria.classList.add('d-none');
            contenedorNueva.classList.remove('d-none');
            nuevaCategoria.required = true;
            nuevaCategoria.focus();
        }
    });

    volverCategoria.addEventListener('click', function () {
        categoria.classList.remove('d-none');
        contenedorNueva.classList.add('d-none');
        categoria.value = '';
        nuevaCategoria.value = '';
        nuevaCategoria.required = false;
    });

    estadoSwitch.addEventListener('change', actualizarEstadoPago);

    metodoPago.addEventListener('change', function () {
        if (estadoInput.value === 'PAGADO' && this.value === '') {
            document.getElementById('mensaje_metodo_pago').innerText = 'Debe seleccionar un método de pago si el gasto está pagado.';
            document.getElementById('mensaje_metodo_pago').className = 'text-danger';
        } else if (this.value !== '') {
            document.getElementById('mensaje_metodo_pago').innerText = 'Método de pago seleccionado correctamente.';
            document.getElementById('mensaje_metodo_pago').className = 'text-success';
        } else {
            document.getElementById('mensaje_metodo_pago').innerText = 'El método de pago no es obligatorio si está pendiente.';
            document.getElementById('mensaje_metodo_pago').className = 'text-muted';
        }
    });

    nombreTitular.addEventListener('input', function () {
        const valor = this.value.trim();

        if (valor.length === 0) {
            this.classList.remove('is-invalid', 'is-valid');
            document.getElementById('mensaje_nombre_titular').innerText = 'Opcional. Nombre de la persona que realizó el pago.';
            document.getElementById('mensaje_nombre_titular').className = 'text-muted';
            return;
        }

        const regex = /^[A-ZÁÉÍÓÚÑ ]+$/;
        if (!regex.test(valor) || valor.length < 5) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            document.getElementById('mensaje_nombre_titular').innerText = 'Ingrese un nombre válido (mínimo 5 caracteres y solo letras).';
            document.getElementById('mensaje_nombre_titular').className = 'text-danger';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('mensaje_nombre_titular').innerText = 'Nombre válido.';
            document.getElementById('mensaje_nombre_titular').className = 'text-success';
        }
    });

    monto.addEventListener('input', function () {
        actualizarTipoCambio();

        if (parseFloat(this.value) <= 0 || this.value === '') {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            document.getElementById('mensaje_monto').innerText = 'El monto debe ser mayor a 0.';
            document.getElementById('mensaje_monto').className = 'text-danger';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('mensaje_monto').innerText = 'Monto válido.';
            document.getElementById('mensaje_monto').className = 'text-success';
        }
    });

    moneda.addEventListener('change', actualizarTipoCambio);
    tipoCambioInput.addEventListener('input', actualizarTipoCambio);

    concepto.addEventListener('input', function () {
        if (this.value.trim().length < 3) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            document.getElementById('mensaje_concepto').innerText = 'El concepto debe tener mínimo 3 caracteres.';
            document.getElementById('mensaje_concepto').className = 'text-danger';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('mensaje_concepto').innerText = 'Concepto válido.';
            document.getElementById('mensaje_concepto').className = 'text-success';
        }
    });

    fecha.addEventListener('change', function () {
        if (this.value === '') {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            document.getElementById('mensaje_fecha').innerText = 'Debe seleccionar una fecha.';
            document.getElementById('mensaje_fecha').className = 'text-danger';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('mensaje_fecha').innerText = 'Fecha válida.';
            document.getElementById('mensaje_fecha').className = 'text-success';
        }
    });

    comprobante.addEventListener('change', function () {
        const archivo = this.files[0];
        if (!archivo) {
            document.getElementById('mensaje_comprobante').innerText = 'Puede subir imagen o PDF del comprobante.';
            document.getElementById('mensaje_comprobante').className = 'text-muted';
            return;
        }
        const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
        const extension = archivo.name.split('.').pop().toLowerCase();
        if (!extensionesPermitidas.includes(extension)) {
            this.value = '';
            document.getElementById('mensaje_comprobante').innerText = 'Formato no permitido. Solo JPG, PNG o PDF.';
            document.getElementById('mensaje_comprobante').className = 'text-danger';
            return;
        }
        if (archivo.size > 2 * 1024 * 1024) {
            this.value = '';
            document.getElementById('mensaje_comprobante').innerText = 'El comprobante no debe superar los 2MB.';
            document.getElementById('mensaje_comprobante').className = 'text-danger';
            return;
        }
        document.getElementById('mensaje_comprobante').innerText = 'Comprobante válido.';
        document.getElementById('mensaje_comprobante').className = 'text-success';
    });

    actualizarEstadoPago();
    actualizarTipoCambio();
});
</script>
@endsection
