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

        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"💰 Aquí registras los <b>gastos extra</b> asociados a tus contratos: transporte, impuestos, peajes u otros costos adicionales a la compra de chatarra."},
                        {"element":"#ge-resumen","intro":"📊 Estas tarjetas resumen el <b>total</b> de gastos, cuánto está <b>pendiente</b>, cuánto <b>pagado</b> y cuántos contratos tienen gastos.","position":"bottom"},
                        {"element":"#ge-filtro","intro":"🔎 Puedes <b>filtrar por proveedor</b> para ver solo sus contratos y gastos.","position":"bottom"},
                        {"element":"#ge-contratos","intro":"📄 A la izquierda, la lista de <b>contratos</b> con su total de gastos. Pulsa <b>Ver Detalles</b> en uno para cargar sus gastos a la derecha.","position":"right"},
                        {"element":"#ge-detalle","intro":"🧾 A la derecha aparece el <b>detalle</b> de los gastos del contrato elegido: fecha, categoría, concepto, monto y estado (Pendiente/Pagado).","position":"left"},
                        {"element":"#btnNuevoGastoExtra","intro":"➕ Con <b>Nuevo Gasto Extra</b> registras un gasto. El formulario tiene su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('gastos_extras.create')
                <button type="button" id="btnNuevoGastoExtra" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGastoExtra" onclick="resetModalGastoExtra()">
                    <i class="bi bi-plus-lg"></i> Nuevo Gasto Extra
                </button>
            @endcan
        </div>
    </div>
</div>

<section class="section mt-3">
    <div class="row mb-3" id="ge-resumen">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-semibold">Total Gastos</small>
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($total,2,',','.') }}</h4>
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
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($pendientes,2,',','.') }}</h4>
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
                        <h4 class="fw-bold mt-2 mb-0">BOB {{ number_format($pagados,2,',','.') }}</h4>
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

    <div class="card mb-3" id="ge-filtro">
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
        <div class="col-md-4" id="ge-contratos">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Contratos</h5>
                    </div>
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Seleccione un contrato para visualizar todos sus gastos extras</p>
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
                                        <td><strong>BOB {{ number_format($c->total_gastos,2,',','.') }}</strong></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick='verDetalles(@json($c->gastos_detalle), "{{ $c->numero_contrato }}")'><i class="bi bi-eye"></i> Ver Detalles</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Sin contratos registrados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8" id="ge-detalle">
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
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="detalleGastos">
                                <tr><td colspan="8" class="text-center text-muted">Sin información</td></tr>
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

<script>window.gastosExtrasConfig = {updateBaseUrl: "{{ url('/gastos_extras') }}"};</script>
<script src="{{ asset('assets/js/forms/validacionGastos.js') }}"></script>
<script>
function _fmtG(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}

function verDetalles(gastos, contrato) {
    let html = '';
    document.getElementById('tituloDetalle').innerText = 'Contrato: ' + contrato;
    if (!gastos || gastos.length === 0) {
        html = `
            <tr>
                <td colspan="8" class="text-center text-muted">Sin gastos registrados</td>
            </tr>
        `;
    } else {
        gastos.forEach(g => {
            const key = guardarGastoEnCache(g);
            const keySeguro = escapeHtml(key);
            let comprobante = '';
            if (g.estado === 'PAGADO') {
                if (g.comprobante_pago) {
                    comprobante = `
                        <li>
                            <a class="dropdown-item" href="#" onclick="window.open('{{ asset('comprobantes_pago') }}/${escapeHtml(g.comprobante_pago)}','comprobante','width=700,height=500,resizable=yes,scrollbars=yes'); return false;"><i class="bi bi-receipt"></i>Ver Comprobante</a></li>
                    `;
                } else {
                    comprobante = `
                        <li><a class="dropdown-item text-muted"><i class="bi bi-receipt"></i>Sin Comprobante</a></li>
                    `;
                }
            }
            let btnMarcarPagado = '';
            if (g.estado === 'PENDIENTE') {
                btnMarcarPagado = `
                    <li><a class="dropdown-item" href="#" onclick="marcarPagadoPorKey('${keySeguro}'); return false;"><i class="bi bi-cash"></i>Marcar PAGADO</a></li>
                `;
            }
            html += `
                <tr>
                    <td>${fechaSolo(g.fecha)}</td>
                    <td><span class="badge bg-primary">${escapeHtml(valorSeguro(g.categoria))}</span></td>
                    <td>${escapeHtml(valorSeguro(g.concepto))}</td>
                    <td>

    <div class="fw-bold">
        ${escapeHtml(valorSeguro(g.moneda))}
        ${_fmtG(g.monto)}
    </div>

    ${
        g.moneda !== 'BOB'
        ? `
            <div class="small text-muted mt-1">
                TC:
                <strong>${_fmtG(g.tipo_cambio)}</strong>
                <br>
                BOB:
                <strong class="text-success">${_fmtG(g.monto_bolivianos)}</strong>
            </div>
        `
        : `
            <div class="small text-success mt-1">
                BOB:
                <strong>${_fmtG(g.monto_bolivianos || g.monto)}</strong>
            </div>
        `
    }

</td>
            
                   <td>${g.estado === 'PAGADO'? `<div>
                <span class="badge bg-success">PAGADO</span>
                <div class="small text-muted mt-1">${escapeHtml(valorSeguro(g.metodo_pago))}</div>
            </div>` : ` <span class="badge bg-warning text-dark">PENDIENTE</span>`}</td>
                    
                    <td class="text-center">
                        <div class="btn-group">
                            <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                            <ul class="dropdown-menu">
                                ${
                                    g.estado === 'PENDIENTE' ? `
                                    @can('gastos_extras.edit')
                                    <li><a class="dropdown-item" href="#" onclick="editarGastoPorKey('${keySeguro}'); return false;"><i class="bi bi-pencil"></i> Modificar</a></li>
                                    @endcan
                                    `
                                    : `
                                    <li><a class="dropdown-item text-muted"><i class="bi bi-lock"></i>Gasto Pagado</a></li>
                                    `
                                }
                                ${comprobante}
                                ${btnMarcarPagado}
                                ${
                                    g.estado === 'PENDIENTE'
                                    ? `
                                    @can('gastos_extras.destroy')
                                    <li><a class="dropdown-item text-danger" href="{{ url('/gastos_extras') }}/${escapeHtml(g.uuid)}/destroy" onclick="return confirm('¿Eliminar este gasto extra?')"><i class="bi bi-trash"></i>Eliminar</a></li>
                                    @endcan
                                    `
                                    : ''
                                }
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    document.getElementById('detalleGastos').innerHTML = html;
}
function resetModalGastoExtra() {
    quitarBloqueoCamposGasto();
    document.getElementById('tituloGasto').innerHTML = '<i class="bi bi-cash-coin"></i> Registrar Gasto Extra';
    document.getElementById('btnGasto').innerText = 'Registrar';
    document.getElementById('methodGasto').value = 'POST';
    document.getElementById('formGasto').action = '{{ route("gastos_extras.store") }}';
    limpiarFormularioGastoExtra();
}
</script>
@endsection