@extends('layouts.app')
@section('titulo','Reglas de Costo Adicional')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>REGLAS DE COSTO ADICIONAL</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Reglas de Costo Adicional</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"💲 Las <b>Reglas de Costo Adicional</b> definen un monto fijo por tramo que se suma en el Excel de Contratos, cuando la venta coincide con el cliente y/o la empresa facturadora que definas aquí."},
                        {"element":"#regla-tabla","intro":"📋 Cada regla indica: a qué cliente aplica (o cualquiera), a qué empresa facturadora aplica (o cualquiera), y el monto por tramo. Debe cumplirse cliente Y empresa a la vez si ambos están definidos.","position":"top"},
                        {"element":"#btnNuevaRegla","intro":"➕ Con <b>Nueva Regla</b> agregas una combinación nueva.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('reglas_costo_adicional.create')
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
            <h5 class="card-title">Costo Adicional especial por Cliente + Empresa Facturadora</h5>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Al descargar el Excel de Contratos, se suma un costo adicional por cada tramo cuando la venta coincide con
                una regla activa de esta lista (cliente y/o empresa facturadora). Deje un campo en blanco para que la regla
                aplique a cualquier cliente o cualquier empresa en ese campo.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($reglas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-cash-coin fs-1"></i>
                <p class="mt-2">No hay reglas de costo adicional registradas.</p>
            </div>
            @else
            <div class="table-responsive">
                <table id="regla-tabla" class="table table-hover table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Empresa Facturadora</th>
                            <th class="text-end">Monto por Tramo</th>
                            <th>Estado</th>
                            <th style="width:160px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reglas as $r)
                        <tr>
                            <td>{{ $r->cliente->nombre ?? '— Cualquier cliente —' }}</td>
                            <td>{{ $r->empresaFacturadora->nombre ?? '— Cualquier empresa —' }}</td>
                            <td class="text-end">BOB {{ number_format($r->monto_por_tramo, 2, ',', '.') }} / tramo</td>
                            <td>
                                @if($r->activo)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @can('reglas_costo_adicional.edit')
                                <button class="btn btn-sm btn-outline-secondary" title="Editar"
                                        onclick="editarRegla('{{ $r->uuid }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a class="btn btn-sm btn-outline-warning" title="{{ $r->activo ? 'Desactivar' : 'Activar' }}"
                                   href="{{ route('reglas_costo_adicional.toggle_activo', $r->uuid) }}"
                                   onclick="event.preventDefault(); document.getElementById('form_toggle_{{ $r->uuid }}').submit();">
                                    <i class="bi bi-toggle-{{ $r->activo ? 'on' : 'off' }}"></i>
                                </a>
                                <form id="form_toggle_{{ $r->uuid }}" method="POST" action="{{ route('reglas_costo_adicional.toggle_activo', $r->uuid) }}" class="d-none">
                                    @csrf
                                </form>
                                @endcan
                                @can('reglas_costo_adicional.destroy')
                                <a class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   href="{{ route('reglas_costo_adicional.destroy', $r->uuid) }}"
                                   onclick="return confirm('¿Eliminar esta regla de costo adicional?')">
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
                <h5 class="modal-title" id="tituloRegla"><i class="bi bi-cash-coin"></i> Nueva Regla de Costo Adicional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRegla" method="POST" action="{{ route('reglas_costo_adicional.store') }}">
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
                            <label class="form-label">Monto por Tramo (BOB) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto_por_tramo" id="regla_monto"
                                   class="form-control" required placeholder="Ej: 348.00">
                            <div class="form-text">Se suma como costo adicional en cada tramo que coincida con esta regla.</div>
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
    document.getElementById('formRegla').action = "{{ route('reglas_costo_adicional.store') }}";
    document.getElementById('methodRegla').value = 'POST';
    document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-cash-coin"></i> Nueva Regla de Costo Adicional';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
}

function editarRegla(uuid) {
    fetch(url_global + '/reglas-costo-adicional/' + uuid + '/edit')
        .then(r => r.json())
        .then(r => {
            document.getElementById('formRegla').action = url_global + '/reglas-costo-adicional/' + uuid;
            document.getElementById('methodRegla').value = 'PUT';
            document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-pencil"></i> Editar Regla de Costo Adicional';
            document.getElementById('regla_cliente_id').value = r.cliente_id ?? '';
            document.getElementById('regla_empresa_id').value = r.empresa_facturadora_id ?? '';
            document.getElementById('regla_monto').value = r.monto_por_tramo ?? '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
        });
}
</script>
@endsection
