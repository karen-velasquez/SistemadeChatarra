@extends('layouts.app')
@section('titulo','Reglas de Comisión')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>REGLAS DE COMISIÓN</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Reglas de Comisión</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"💲 Las <b>Reglas de Comisión</b> reemplazan el 3% normal de Comisión 1 (en el Excel de Contratos) por un monto fijo por tonelada, cuando la venta coincide con el cliente y/o la empresa facturadora que definas aquí."},
                        {"element":"#regla-tabla","intro":"📋 Cada regla indica: a qué cliente aplica (o cualquiera), a qué empresa facturadora aplica (o cualquiera), y el monto por tonelada que reemplaza al 3%. Debe cumplirse cliente Y empresa a la vez si ambos están definidos.","position":"top"},
                        {"element":"#btnNuevaRegla","intro":"➕ Con <b>Nueva Regla</b> agregas una combinación nueva.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('reglas_comision.create')
            <button type="button" id="btnNuevaRegla" class="btn btn-primary btn-sm" onclick="nuevaRegla()">
                <i class="bi bi-plus-lg"></i> Nueva Regla
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Comisión 1 especial por Cliente + Empresa Facturadora</h5>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Al descargar el Excel de Contratos, la Comisión 1 de cada venta se calcula normalmente como Total Ventas × 3%.
                Si la venta coincide con una regla activa de esta lista (cliente y/o empresa facturadora) vigente en el
                <strong>mes de la fecha de entrega</strong>, se usa en su lugar <strong>monto por tonelada × toneladas entregadas</strong>.
                Deje un campo en blanco para que la regla aplique a cualquier cliente o cualquier empresa en ese campo.
                El monto puede variar cada mes: registra una regla nueva con el mismo cliente/empresa y el mes desde el que rige.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($reglas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-percent fs-1"></i>
                <p class="mt-2">No hay reglas de comisión registradas. Se usará el 3% por defecto en todas las ventas.</p>
            </div>
            @else
            <div class="table-responsive">
                <table id="regla-tabla" class="table table-hover table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Empresa Facturadora</th>
                            <th>Vigente desde</th>
                            <th class="text-end">Monto por Tonelada</th>
                            <th>Estado</th>
                            <th style="width:160px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reglas as $r)
                        <tr>
                            <td>{{ $r->cliente->nombre ?? '— Cualquier cliente —' }}</td>
                            <td>{{ $r->empresaFacturadora->nombre ?? '— Cualquier empresa —' }}</td>
                            <td>{{ $r->vigente_desde ? ucfirst($r->vigente_desde->translatedFormat('F Y')) : '— Siempre —' }}</td>
                            <td class="text-end">BOB {{ number_format($r->monto_por_tonelada, 2, ',', '.') }} / t</td>
                            <td>
                                @if($r->activo)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @can('reglas_comision.edit')
                                <button class="btn btn-sm btn-outline-secondary" title="Editar"
                                        onclick="editarRegla('{{ $r->uuid }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a class="btn btn-sm btn-outline-warning" title="{{ $r->activo ? 'Desactivar' : 'Activar' }}"
                                   href="{{ route('reglas_comision.toggle_activo', $r->uuid) }}"
                                   onclick="event.preventDefault(); document.getElementById('form_toggle_{{ $r->uuid }}').submit();">
                                    <i class="bi bi-toggle-{{ $r->activo ? 'on' : 'off' }}"></i>
                                </a>
                                <form id="form_toggle_{{ $r->uuid }}" method="POST" action="{{ route('reglas_comision.toggle_activo', $r->uuid) }}" class="d-none">
                                    @csrf
                                </form>
                                @endcan
                                @can('reglas_comision.destroy')
                                <a class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   href="{{ route('reglas_comision.destroy', $r->uuid) }}"
                                   onclick="return confirm('¿Eliminar esta regla de comisión?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- MODAL NUEVA/EDITAR REGLA --}}
<div class="modal fade" id="modalRegla" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloRegla"><i class="bi bi-percent"></i> Nueva Regla de Comisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRegla" method="POST" action="{{ route('reglas_comision.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodRegla" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenRegla" value="{{ $idempotencyToken ?? '' }}">
                <div class="modal-body">
                    <p class="text-muted small mb-3">Debe seleccionar al menos un cliente o una empresa. Deje el otro en blanco para que aplique a cualquiera.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Cliente</label>
                            <select name="cliente_id" id="regla_cliente_id" class="form-select">
                                <option value="">— Cualquier cliente —</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Empresa Facturadora</label>
                            <select name="empresa_facturadora_id" id="regla_empresa_id" class="form-select">
                                <option value="">— Cualquier empresa —</option>
                                @foreach($empresas as $e)
                                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Monto por Tonelada (BOB) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="monto_por_tonelada" id="regla_monto"
                                   class="form-control" required placeholder="Ej: 350.00">
                            <div class="form-text">Reemplaza el 3% de Comisión 1 por este monto fijo × toneladas entregadas.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Vigente desde (mes)</label>
                            <select name="mes" id="regla_mes" class="form-select">
                                <option value="">— Siempre —</option>
                                @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombreMes)
                                    <option value="{{ $i + 1 }}">{{ $nombreMes }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Año</label>
                            <input type="number" name="anio" id="regla_anio" class="form-control" min="2000" max="2100" value="{{ now()->year }}">
                            <div class="form-text">Deja "Vigente desde" en — Siempre — para que aplique a cualquier mes.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnRegla"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function nuevaRegla() {
    document.getElementById('formRegla').reset();
    document.getElementById('formRegla').action = "{{ route('reglas_comision.store') }}";
    document.getElementById('methodRegla').value = 'POST';
    document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-percent"></i> Nueva Regla de Comisión';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
}

function editarRegla(uuid) {
    fetch(url_global + '/reglas-comision/' + uuid + '/edit')
        .then(r => r.json())
        .then(r => {
            document.getElementById('formRegla').action = url_global + '/reglas-comision/' + uuid;
            document.getElementById('methodRegla').value = 'PUT';
            document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-pencil"></i> Editar Regla de Comisión';
            document.getElementById('regla_cliente_id').value = r.cliente_id ?? '';
            document.getElementById('regla_empresa_id').value = r.empresa_facturadora_id ?? '';
            document.getElementById('regla_monto').value = r.monto_por_tonelada ?? '';
            if (r.vigente_desde) {
                const [anio, mes] = r.vigente_desde.split('-');
                document.getElementById('regla_mes').value = parseInt(mes, 10);
                document.getElementById('regla_anio').value = anio;
            } else {
                document.getElementById('regla_mes').value = '';
                document.getElementById('regla_anio').value = new Date().getFullYear();
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
        });
}
</script>
@endsection
