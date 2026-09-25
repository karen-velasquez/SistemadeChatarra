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
                una regla activa de esta lista (cliente y/o empresa facturadora) vigente en el
                <strong>rango de fechas</strong> que indiques, tomando como referencia la <strong>fecha de entrega</strong>
                de la venta. Deje un campo en blanco para que la regla aplique a cualquier cliente o cualquier empresa en
                ese campo. Si ninguna regla vigente aplica a una venta, el costo adicional es <strong>0</strong>.
                El monto puede variar en el tiempo: registra una regla nueva con el mismo cliente/empresa y otro rango de fechas.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($reglas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-cash-coin fs-1"></i>
                <p class="mt-2">No hay reglas de costo adicional registradas. El costo adicional será 0 en todas las ventas.</p>
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
                            <td>{{ $r->fecha_inicio?->format('d/m/Y') ?? '— Sin límite —' }}</td>
                            <td>{{ $r->fecha_fin?->format('d/m/Y') ?? '— Sin límite —' }}</td>
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
                            <input type="text" inputmode="numeric" id="regla_monto_display"
                                   class="form-control" required placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="monto_por_tramo" id="regla_monto">
                            <div class="form-text">Se suma como costo adicional en cada tramo que coincida con esta regla.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha inicio de vigencia</label>
                            <input type="date" name="fecha_inicio" id="regla_fecha_inicio" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha fin de vigencia</label>
                            <input type="date" name="fecha_fin" id="regla_fecha_fin" class="form-control">
                            <div class="form-text">Deja ambas fechas vacías para que aplique siempre.</div>
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
    document.getElementById('regla_monto_display').value = '';
    document.getElementById('regla_monto').value = '';
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
            document.getElementById('regla_monto_display').value = r.monto_por_tramo
                ? new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(r.monto_por_tramo)
                : '';
            document.getElementById('regla_fecha_inicio').value = r.fecha_inicio ? r.fecha_inicio.substring(0, 10) : '';
            document.getElementById('regla_fecha_fin').value = r.fecha_fin ? r.fecha_fin.substring(0, 10) : '';
            document.getElementById('regla_fecha_fin').min = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegla')).show();
        });
}

// La fecha de fin no puede ser anterior a la de inicio.
document.getElementById('regla_fecha_inicio')?.addEventListener('change', function () {
    const fin = document.getElementById('regla_fecha_fin');
    fin.min = this.value || '';
    if (this.value && fin.value && fin.value < this.value) {
        fin.value = '';
    }
});

// Cajero para monto por tramo (mismo patrón de miles/decimales del resto del sistema)
(function () {
    var fmt = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function textoANum(txt) { return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0; }

    var disp = document.getElementById('regla_monto_display');
    var hidd = document.getElementById('regla_monto');
    if (!disp) return;

    disp.addEventListener('input', function () {
        var raw = this.value.replace(/[^0-9,]/g, '');
        var p = raw.split(',');
        if (p.length > 2) raw = p[0] + ',' + p.slice(1).join('');
        if (p[1] !== undefined && p[1].length > 2) raw = p[0] + ',' + p[1].substring(0, 2);
        var partes = raw.split(',');
        var entF   = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var nuevo  = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
        var diff   = nuevo.length - this.value.length;
        var pos    = (this.selectionStart || 0) + diff;
        this.value = nuevo;
        try { this.setSelectionRange(pos, pos); } catch(_) {}
        hidd.value = nuevo ? textoANum(nuevo) : '';
    });

    disp.addEventListener('blur', function () {
        var n = textoANum(this.value);
        this.value = n > 0 ? fmt.format(n) : '';
        hidd.value = n >= 0 ? n : '';
    });
})();
</script>
@endsection
