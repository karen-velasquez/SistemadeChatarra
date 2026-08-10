@extends('layouts.app')
@section('titulo','Créditos y Adquisiciones')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>CRÉDITOS Y ADQUISICIONES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Créditos y Adquisiciones</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    @if($adquisiciones->isEmpty())
                    data-steps='[
                        {"intro":"💳 Este es el módulo de <b>Créditos y Adquisiciones</b>. Aquí registras los bienes que compra la empresa —camiones, maquinaria, equipos— ya sea con <b>crédito bancario</b> o con <b>capital propio</b>, y llevas el control de sus cuotas.<br><br>📭 Todavía no hay adquisiciones registradas. Usa <b>Nueva Adquisición</b> para cargar la primera."}
                    ]'
                    @else
                    data-steps='[
                        {"intro":"💳 Este es el módulo de <b>Créditos y Adquisiciones</b>: los bienes que compró la empresa y el estado de sus pagos. Te muestro cómo leerlo."},
                        {"element":"#tabla_adquisiciones","intro":"📋 Cada fila es un bien adquirido. Verás cómo se financió, cuánto costó, cuánto falta pagar y en qué estado está.","position":"top"},
                        {"element":"#tabla_adquisiciones thead th:nth-child(2)","intro":"🏦 <b>Financiamiento</b>: <span style=\"color:#0dcaf0\"><b>CRÉDITO</b></span> si se compró con préstamo bancario (muestra la entidad y la tasa), o <b>CAPITAL</b> si se pagó con fondos propios.","position":"bottom"},
                        {"element":"#tabla_adquisiciones thead th:nth-child(5)","intro":"💰 El <b>Saldo pendiente</b> es lo que aún se debe. Si el bien se compró en el exterior, cada cuota se registra en bolivianos al tipo de cambio del día del pago.","position":"bottom"},
                        {"element":"#tabla_adquisiciones thead th:nth-child(6)","intro":"🔢 <b>Cuotas</b> muestra cuántas se pagaron sobre el total del plan (por ejemplo 3/12).","position":"bottom"},
                        {"element":"#tabla_adquisiciones thead th:nth-child(7)","intro":"🚦 El <b>Estado</b>: <span style=\"color:#198754\"><b>Pagado</b></span> si se saldó, <span style=\"color:#0d6efd\"><b>En curso</b></span> si se está pagando, <span style=\"color:#dc3545\"><b>Atrasado</b></span> si hay cuotas vencidas, y <b>Sin plan</b> si todavía no se cargaron las cuotas.","position":"bottom"},
                        {"element":"#tabla_adquisiciones tbody tr:first-child td:last-child","intro":"📂 El botón <b>Ficha</b> abre el detalle del bien: su plan de pagos completo, donde registras cada cuota pagada.","position":"left"},
                        {"element":"#btnNuevaAdquisicion","intro":"➕ Con <b>Nueva Adquisición</b> registras un bien nuevo: su descripción, cómo se financia y el plan de cuotas.","position":"left"}
                    ]'
                    @endif>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('adquisiciones.create')
            <button type="button" id="btnNuevaAdquisicion" class="btn btn-primary btn-sm"
                    onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAdquisicion')).show()">
                <i class="bi bi-plus-lg"></i> Nueva Adquisición
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-credit-card me-1"></i> Bienes Adquiridos</h5>
            <p class="text-muted small mb-3">
                Bienes obtenidos por <strong>crédito bancario</strong> o con <strong>capital de la empresa</strong>,
                incluyendo compras en el exterior pagadas en bolivianos al tipo de cambio de cada pago.
                Cada adquisición tiene su plan de pagos con control de cuotas pagadas, pendientes y atrasadas.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabla_adquisiciones">
                    <thead>
                        <tr>
                            <th>Bien</th>
                            <th>Financiamiento</th>
                            <th>Origen</th>
                            <th class="text-end">Monto total</th>
                            <th class="text-end">Saldo pendiente</th>
                            <th class="text-center">Cuotas</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adquisiciones as $a)
                        <tr>
                            <td>
                                <strong>{{ $a->descripcion }}</strong>
                                @if($a->camion)<small class="text-muted d-block">Unidad: {{ $a->camion->placa }}</small>@endif
                            </td>
                            <td>
                                @if($a->financiamiento === 'CREDITO')
                                    <span class="badge bg-info text-dark">CRÉDITO</span>
                                    <small class="d-block text-muted">{{ $a->entidad_financiera }}
                                        @if($a->tasa_interes) · {{ $a->tasa_interes }}% {{ strtolower($a->tipo_interes ?? '') }} @endif
                                    </small>
                                @else
                                    <span class="badge bg-secondary">CAPITAL</span>
                                @endif
                            </td>
                            <td>{{ $a->origen === 'EXTERIOR' ? ($a->paisOrigen->descripcion ?? 'Exterior') : 'Nacional' }}</td>
                            <td class="text-end">{{ number_format($a->monto_total, 2) }} {{ $a->moneda }}</td>
                            <td class="text-end">{{ number_format($a->saldo_pendiente, 2) }} {{ $a->moneda }}</td>
                            <td class="text-center">
                                {{ $a->cuotas->whereNotNull('fecha_pago')->count() }} / {{ $a->cuotas->count() }}
                            </td>
                            <td class="text-center">
                                @if($a->estado === 'PAGADO') <span class="badge bg-success">Pagado</span>
                                @elseif($a->estado === 'ATRASADO') <span class="badge bg-danger">Atrasado</span>
                                @elseif($a->estado === 'EN CURSO') <span class="badge bg-primary">En curso</span>
                                @else <span class="badge bg-warning text-dark">Sin plan</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('adquisiciones.show', $a->uuid) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-folder2-open"></i> Ficha
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-credit-card fs-2 d-block"></i>
                                No hay adquisiciones registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@can('adquisiciones.create')
{{-- MODAL NUEVA ADQUISICIÓN --}}
<div class="modal fade" id="modalAdquisicion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nueva Adquisición</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('adquisiciones.store') }}">
                @csrf
                <div class="modal-body">
                    @include('adquisiciones.partials.form-adquisicion')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script>
function toggleCredito(sel) {
    const esCredito = sel.value === 'CREDITO';
    document.querySelectorAll('.solo-credito').forEach(el => el.style.display = esCredito ? '' : 'none');
}
function toggleExterior(sel) {
    const esExterior = sel.value === 'EXTERIOR';
    document.querySelectorAll('.solo-exterior').forEach(el => el.style.display = esExterior ? '' : 'none');
}
function toggleCamionVinculado(sel) {
    const opt = sel.options[sel.selectedIndex];
    const esCamiones = opt && opt.dataset.valor === 'CAMIONES';
    const contenedor = sel.closest('form').querySelector('.solo-camiones');
    if (contenedor) {
        contenedor.style.display = esCamiones ? '' : 'none';
        if (!esCamiones) contenedor.querySelector('select[name="camion_id"]').value = '';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select[name="financiamiento"]').forEach(toggleCredito);
    document.querySelectorAll('select[name="origen"]').forEach(toggleExterior);
    document.querySelectorAll('select[name="tipo_bien_id"]').forEach(toggleCamionVinculado);
});
</script>
@endsection
