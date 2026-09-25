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
                        {"element":"#ge-filtro","intro":"🔎 Puedes <b>buscar un contrato</b> para filtrar solo sus gastos, o dejarlo vacío para ver todos.","position":"bottom"},
                        {"element":"#ge-detalle","intro":"🧾 Aquí aparecen todos los <b>gastos extra</b> registrados: contrato, fecha, categoría, concepto, monto y estado (Pendiente/Pagado).","position":"top"},
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
                        <h4 class="fw-bold mt-2 mb-0">{{ $gastos->pluck('contrato_id')->unique()->count() }}</h4>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-file-earmark-text text-info fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="ge-detalle">
                <div class="card-body">
                    <h5 class="card-title">Gastos Extra</h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        En esta sección puedes registrar y gestionar gastos adicionales relacionados con tus contratos de compra de chatarra. Estos gastos pueden incluir gastos de transporte, impuestos u otros costos asociados.
                    </p>

                    {{-- ===== FILTROS ===== --}}
                    <div class="row g-2 align-items-end mb-3" id="ge-filtro">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1"><i class="bi bi-file-earmark-text"></i> Buscar contrato</label>
                            <select id="filtro_contrato" class="form-select">
                                <option value="">— Todos los contratos —</option>
                                @foreach($contratosParaFiltro as $c)
                                    <option value="{{ $c->id }}">{{ $c->numero_contrato }} — {{ $c->proveedor->nombre ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltroGastoExtra()">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                        <div class="col-auto ms-auto">
                            <small class="text-muted">Mostrando <span id="lbl_count_gasto">{{ $gastos->count() }}</span> gasto(s)</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datos" class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Fecha</th>
                                    <th>Categoría</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="detalleGastos">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal de confirmación para eliminar un gasto extra --}}
<div class="modal fade" id="modalEliminarGasto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar gasto extra</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Estás seguro de eliminar este gasto extra?</p>
        <div id="del_gasto_body" class="small"></div>
        <div class="alert alert-warning small py-2 mb-0 mt-3" id="del_gasto_aviso_movimiento">
          <i class="bi bi-info-circle me-1"></i>
          Este gasto está PAGADO: se revertirá su movimiento en tesorería. Esta acción no se puede deshacer desde aquí.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btn_confirmar_eliminar_gasto" class="btn btn-danger">
          <i class="bi bi-trash me-1"></i>Sí, eliminar
        </a>
      </div>
    </div>
  </div>
</div>

@include('gastos_extras.modal')
@endsection

@section('scripts')

<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>window.gastosExtrasConfig = {updateBaseUrl: "{{ url('/gastos_extras') }}"};</script>
<script src="{{ asset('assets/js/forms/validacionGastos.js') }}"></script>
<script>
function _fmtG(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}

const _todosLosGastos = @json($gastosJson);

function _filaGasto(g) {
    const key = guardarGastoEnCache(g);
    const keySeguro = escapeHtml(key);
    let comprobante = '';
    if (g.estado === 'PAGADO') {
        if (g.comprobante_pago) {
            comprobante = `<li><a class="dropdown-item" href="#" onclick="window.open('{{ asset('storage/comprobantes_pago') }}/${escapeHtml(g.comprobante_pago)}','comprobante','width=700,height=500,resizable=yes,scrollbars=yes'); return false;"><i class="bi bi-receipt"></i>Ver Comprobante</a></li>`;
        } else {
            comprobante = `<li><a class="dropdown-item text-muted"><i class="bi bi-receipt"></i>Sin Comprobante</a></li>`;
        }
    }
    let btnMarcarPagado = '';
    if (g.estado === 'PENDIENTE') {
        btnMarcarPagado = `<li><a class="dropdown-item" href="#" onclick="marcarPagadoPorKey('${keySeguro}'); return false;"><i class="bi bi-cash"></i>Marcar PAGADO</a></li>`;
    }
    return `
        <tr data-contrato-id="${g.contrato_id}">
            <td>
                <div class="fw-bold">${escapeHtml(valorSeguro(g.contrato_numero))}</div>
                <small class="text-muted">${escapeHtml(valorSeguro(g.proveedor_nombre))}</small>
            </td>
            <td>${fechaSolo(g.fecha)}</td>
            <td><span class="badge bg-primary">${escapeHtml(valorSeguro(g.categoria))}</span></td>
            <td>${escapeHtml(valorSeguro(g.concepto))}</td>
            <td>
<div class="fw-bold">${escapeHtml(valorSeguro(g.moneda))} ${_fmtG(g.monto)}</div>
${
    g.moneda !== 'BOB' ? `
        <div class="small text-muted mt-1">TC: <strong>${_fmtG(g.tipo_cambio)}</strong> <br>BOB: <strong class="text-success">${_fmtG(g.monto_bolivianos)}</strong>
        </div>` : ` <div class="small text-success mt-1"> BOB: <strong>${_fmtG(g.monto_bolivianos || g.monto)}</strong></div>`
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
                        @can('gastos_extras.edit')
                        ${
                            g.estado === 'PENDIENTE'
                            ? `<li><a class="dropdown-item" href="#" onclick="editarGastoPorKey('${keySeguro}'); return false;"><i class="bi bi-pencil"></i> Modificar</a></li>`
                            : `<li><a class="dropdown-item" href="#" onclick="editarComprobantePorKey('${keySeguro}'); return false;"><i class="bi bi-paperclip"></i> Editar Comprobante</a></li>`
                        }
                        @endcan
                        ${comprobante}
                        ${btnMarcarPagado}
                        @can('gastos_extras.destroy')
                        <li><a class="dropdown-item text-danger" href="#" onclick="abrirModalEliminarGasto('${keySeguro}'); return false;"><i class="bi bi-trash"></i>Eliminar</a></li>
                        @endcan
                    </ul>
                </div>
            </td>
        </tr>`;
}

const tablaGastos = $('#datos').DataTable();

function renderGastosExtra() {
    const contratoId = document.getElementById('filtro_contrato').value;
    const filtrados = contratoId ? _todosLosGastos.filter(g => String(g.contrato_id) === contratoId) : _todosLosGastos;

    tablaGastos.clear();
    filtrados.forEach(g => tablaGastos.row.add($(_filaGasto(g))));
    tablaGastos.draw();

    document.getElementById('lbl_count_gasto').textContent = filtrados.length;
}

function limpiarFiltroGastoExtra() {
    $('#filtro_contrato').val('').trigger('change.select2');
    renderGastosExtra();
}

$('#filtro_contrato').select2({
    placeholder: '— Todos los contratos —',
    allowClear: true,
    width: '100%',
    language: {
        noResults: () => 'No se encontró ningún contrato.',
        searching: () => 'Buscando...'
    }
});
$('#filtro_contrato').on('change', renderGastosExtra);

renderGastosExtra();

function abrirModalEliminarGasto(key) {
    const gasto = obtenerGastoCache(key);
    if (!gasto) {
        alert('No se pudo cargar la información del gasto. Vuelva a intentarlo.');
        return;
    }

    document.getElementById('del_gasto_body').innerHTML = `
        <ul class="mb-0 ps-3">
            <li><strong>Contrato:</strong> ${escapeHtml(valorSeguro(gasto.contrato_numero))}</li>
            <li><strong>Concepto:</strong> ${escapeHtml(valorSeguro(gasto.concepto))}</li>
            <li><strong>Monto:</strong> ${escapeHtml(valorSeguro(gasto.moneda))} ${_fmtG(gasto.monto)}</li>
            <li><strong>Estado:</strong> ${escapeHtml(valorSeguro(gasto.estado))}</li>
        </ul>`;

    document.getElementById('del_gasto_aviso_movimiento').classList.toggle('d-none', gasto.estado !== 'PAGADO');
    document.getElementById('btn_confirmar_eliminar_gasto').href = '{{ url('/gastos_extras') }}/' + encodeURIComponent(gasto.uuid) + '/destroy';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminarGasto')).show();
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