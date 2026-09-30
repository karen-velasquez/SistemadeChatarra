@extends('layouts.app')
@section('titulo','Reglas de Comisión 2')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>REGLAS DE COMISIÓN 2</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Reglas de Comisión 2</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"💲 Las <b>Reglas de Comisión 2</b> definen la Comisión 2 ZPL (en el Excel de Contratos) como un % del total de venta, cuando la venta coincide con el cliente y/o la empresa facturadora que definas aquí, dentro de su rango de vigencia."},
                        {"element":"#regla-tabla","intro":"📋 Cada regla indica: a qué cliente aplica (o cualquiera), a qué empresa facturadora aplica (o cualquiera), el % de comisión, y el rango de fechas en que rige. Debe cumplirse cliente Y empresa a la vez si ambos están definidos. Si ninguna regla vigente aplica a una venta, su Comisión 2 es 0.","position":"top"},
                        {"element":"#btnNuevaRegla","intro":"➕ Con <b>Nueva Regla</b> agregas una combinación nueva.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('reglas_comision2.create')
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
            <h5 class="card-title">Comisión 2 especial por Cliente + Empresa Facturadora</h5>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Al descargar el Excel de Contratos, la Comisión 2 ZPL de cada venta se calcula como
                <strong>% × total de venta (toneladas × precio)</strong>, usando la regla de esta lista (cliente y/o empresa
                facturadora) cuyo rango de vigencia incluya la <strong>fecha de entrega</strong> de la venta.
                Deje un campo en blanco para que la regla aplique a cualquier cliente o cualquier empresa en ese campo.
                Si ninguna regla vigente aplica a una venta, su Comisión 2 es <strong>0</strong>.
                El % puede variar en el tiempo: registra una regla nueva con el mismo cliente/empresa y otro rango de fechas.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($reglas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-percent fs-1"></i>
                <p class="mt-2">No hay reglas de comisión 2 registradas. La Comisión 2 será 0 en todas las ventas.</p>
            </div>
            @else
            <div class="table-responsive">
                <table id="regla-tabla" class="table table-hover table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Empresa Facturadora</th>
                            <th>Fecha inicio</th>
                            <th>Fecha fin</th>
                            <th class="text-end">Porcentaje</th>
                            <th>Estado</th>
                            <th style="width:160px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reglas as $r)
                        <tr>
                            <td>{{ $r->cliente->nombre ?? '— Cualquier cliente —' }}</td>
                            <td>{{ $r->empresaFacturadora->nombre ?? '— Cualquier empresa —' }}</td>
                            <td>{{ $r->fecha_inicio?->format('d/m/Y') ?? '— Sin límite —' }}</td>
                            <td>{{ $r->fecha_fin?->format('d/m/Y') ?? '— Sin límite —' }}</td>
                            <td class="text-end">{{ number_format($r->porcentaje, 2, ',', '.') }} %</td>
                            <td>
                                @if($r->activo)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @can('reglas_comision2.edit')
                                <button class="btn btn-sm btn-outline-secondary" title="Editar"
                                        onclick="editarRegla('{{ $r->uuid }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a class="btn btn-sm btn-outline-warning" title="{{ $r->activo ? 'Desactivar' : 'Activar' }}"
                                   href="{{ route('reglas_comision2.toggle_activo', $r->uuid) }}"
                                   onclick="event.preventDefault(); document.getElementById('form_toggle_{{ $r->uuid }}').submit();">
                                    <i class="bi bi-toggle-{{ $r->activo ? 'on' : 'off' }}"></i>
                                </a>
                                <form id="form_toggle_{{ $r->uuid }}" method="POST" action="{{ route('reglas_comision2.toggle_activo', $r->uuid) }}" class="d-none">
                                    @csrf
                                </form>
                                @endcan
                                @can('reglas_comision2.destroy')
                                <a class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   href="{{ route('reglas_comision2.destroy', $r->uuid) }}"
                                   onclick="return confirm('¿Eliminar esta regla de comisión 2?')">
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
                <h5 class="modal-title" id="tituloRegla"><i class="bi bi-percent"></i> Nueva Regla de Comisión 2</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRegla" method="POST" action="{{ route('reglas_comision2.store') }}">
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
                            <label class="form-label">Porcentaje (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="porcentaje" id="regla_porcentaje"
                                       class="form-control" required min="0" max="100" step="0.01" placeholder="0,00">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Reemplaza el 1,1% de Comisión 2 ZPL por este % × total de venta (toneladas × precio).</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha inicio de vigencia</label>
                            <input type="date" name="fecha_inicio" id="regla_fecha_inicio" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha fin de vigencia</label>
                            <input type="date" name="fecha_fin" id="regla_fecha_fin" class="form-control">
                            <div class="form-text" id="regla_fecha_fin_ayuda">Deja ambas fechas vacías para que aplique siempre.</div>
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
    document.getElementById('formRegla').action = "{{ route('reglas_comision2.store') }}";
    document.getElementById('methodRegla').value = 'POST';
    document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-percent"></i> Nueva Regla de Comisión 2';
    document.getElementById('regla_porcentaje').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
}

function editarRegla(uuid) {
    fetch(url_global + '/reglas-comision2/' + uuid + '/edit')
        .then(r => r.json())
        .then(r => {
            document.getElementById('formRegla').action = url_global + '/reglas-comision2/' + uuid;
            document.getElementById('methodRegla').value = 'PUT';
            document.getElementById('tituloRegla').innerHTML = '<i class="bi bi-pencil"></i> Editar Regla de Comisión 2';
            document.getElementById('regla_cliente_id').value = r.cliente_id ?? '';
            document.getElementById('regla_empresa_id').value = r.empresa_facturadora_id ?? '';
            document.getElementById('regla_porcentaje').value = r.porcentaje ?? '';
            document.getElementById('regla_fecha_inicio').value = r.fecha_inicio ? r.fecha_inicio.substring(0, 10) : '';
            document.getElementById('regla_fecha_fin').value = r.fecha_fin ? r.fecha_fin.substring(0, 10) : '';
            document.getElementById('regla_fecha_fin').min = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
        });
}

// La fecha de fin no puede ser anterior a la de inicio: se limita con el
// atributo min y se avisa si el usuario deja un valor inválido antes de eso.
document.getElementById('regla_fecha_inicio')?.addEventListener('change', function () {
    const fin = document.getElementById('regla_fecha_fin');
    fin.min = this.value || '';
    if (this.value && fin.value && fin.value < this.value) {
        fin.value = '';
    }
});
</script>
@endsection
