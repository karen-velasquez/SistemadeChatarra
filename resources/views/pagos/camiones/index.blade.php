@extends('layouts.app')
@section('titulo', 'Historial de Pagos a Camiones')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>HISTORIAL DE PAGOS A CAMIONES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Historial Pagos Camiones</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                @if($pagos->isEmpty())
                data-steps='[
                    {"intro":"📜 Este es el <b>Historial de Pagos a Camiones</b>: aquí se listan todos los pagos de fletes a transportistas.<br><br>📭 Aún <b>no hay pagos registrados</b>. Los pagos se registran desde <b>Seguimiento de Cargas</b> (en las Opciones de cada camión); luego aparecerán aquí."}
                ]'
                @else
                data-steps='[
                    {"intro":"📜 Este es el <b>Historial de Pagos a Camiones</b>: todos los pagos de fletes, del más reciente al más antiguo. Es solo de <b>consulta</b>; los pagos se registran desde Seguimiento de Cargas."},
                    {"element":"#filtro_camion","intro":"🚛 Filtra los pagos por <b>camión</b> para ver solo los de una placa.","position":"bottom"},
                    {"element":"#filtro_tipo","intro":"🏷️ Filtra por <b>tipo de pago</b>: Adelanto, Flete o Pago Final.","position":"bottom"},
                    {"element":"#tabla_historial","intro":"📋 Cada fila es un pago: fecha, camión, contrato, a quién se le pagó y con qué método.","position":"top"},
                    {"element":"#tabla_historial thead th:nth-child(6)","intro":"💱 La columna <b>Monto</b> muestra la moneda original; <b>En BOB</b> muestra el equivalente en bolivianos (con su tipo de cambio) cuando el pago fue en otra moneda.","position":"bottom"},
                    {"element":"#tabla_historial thead th:nth-child(9)","intro":"👤 <b>Receptor</b>: a quién se pagó (conductor o propietario) y su cuenta. <b>Pagado por</b>: de qué cuenta de la empresa salió el dinero.","position":"bottom"}
                ]'
                @endif>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Historial de Pagos</h5>
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Registro de todos los pagos realizados a transportistas, ordenados del más reciente al más antiguo.
                    Para registrar nuevos pagos, hazlo desde el <a href="{{ route('seguimiento.index') }}">Seguimiento de Cargas</a>.
                </p>

                {{-- Filtro por camión --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-truck"></i> Filtrar por camión</label>
                        <select class="form-select" id="filtro_camion" onchange="filtrarPagos()">
                            <option value="">— Todos los camiones —</option>
                            @foreach($pagos->pluck('contratoCamion.camion')->unique('id')->filter()->sortBy('placa') as $cam)
                                <option value="{{ $cam->id }}">{{ $cam->placa }} — {{ $cam->marca }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-tag"></i> Tipo de pago</label>
                        <select class="form-select" id="filtro_tipo" onchange="filtrarPagos()">
                            <option value="">— Todos —</option>
                            <option value="adelanto">Adelanto</option>
                            <option value="flete">Flete</option>
                            <option value="pago_final">Pago Final</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <small class="text-muted">Mostrando <span id="lbl_count">{{ $pagos->count() }}</span> pago(s)</small>
                    </div>
                </div>

                @if($pagos->isEmpty())
                    <div class="alert alert-info py-2">
                        <small><i class="bi bi-info-circle"></i> No hay pagos registrados aún.</small>
                    </div>
                @else
                <div class="table-responsive">
                    <table id="tabla_historial" class="table table-hover table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Camión</th>
                                <th>Contrato</th>
                                <th>Conductor / Propietario</th>
                                <th>Tipo</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">En BOB</th>
                                <th>Método</th>
                                <th>Receptor</th>
                                <th>Pagado por</th>
                                <th>Código</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hoy = \Carbon\Carbon::today(); $ayer = \Carbon\Carbon::yesterday(); @endphp
                            @foreach($pagos as $p)
                            @php
                                $cc       = $p->contratoCamion;
                                $camion   = $cc->camion;
                                $esBob    = $p->moneda_pago === 'BOB';
                                $fechaPago = $p->fecha_pago;
                                if ($fechaPago->isSameDay($hoy)) {
                                    $badgeFecha = '<span class="badge bg-success">Hoy</span>';
                                } elseif ($fechaPago->isSameDay($ayer)) {
                                    $badgeFecha = '<span class="badge bg-info text-dark">Ayer</span>';
                                } else {
                                    $badgeFecha = '';
                                }
                                $badgeTipo = match($p->tipo_pago) {
                                    'adelanto'   => 'bg-warning text-dark',
                                    'flete'      => 'bg-info text-dark',
                                    'pago_final' => 'bg-success',
                                    default      => 'bg-secondary',
                                };
                            @endphp
                            <tr data-camion-id="{{ $camion->id }}" data-tipo="{{ $p->tipo_pago }}">
                                <td>
                                    <span class="small">{{ $fechaPago->format('d/m/Y') }}</span>
                                    {!! $badgeFecha !!}
                                </td>
                                <td>
                                    <strong>{{ $camion->placa }}</strong>
                                    <small class="text-muted d-block">{{ $camion->marca->valor ?? '-' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('contratos.camiones', $cc->contrato->uuid) }}" class="text-decoration-none small">
                                        {{ $cc->contrato->numero_contrato ?? 'Contrato #'.$cc->contrato_id }}
                                    </a>
                                </td>
                                <td>
                                    <small>{{ $cc->conductor?->nombre_completo ?? '—' }}</small>
                                    @if($cc->camion->propietario && $cc->conductor?->id !== $cc->camion->propietario?->id)
                                        <small class="text-muted d-block">{{ $cc->camion->propietario->nombre_completo }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $badgeTipo }}">{{ $p->tipo_pago_label }}</span>
                                </td>
                                <td class="text-end">
                                    @if(!$esBob)
                                        <span class="fw-semibold">{{ $p->moneda_pago }} {{ number_format($p->monto, 2, ',', '.') }}</span>
                                        <small class="text-muted d-block" style="font-size:.7rem;">TC: {{ number_format($p->tipo_cambio, 4, ',', '.') }}</small>
                                    @else
                                        <span class="fw-semibold">Bs {{ number_format($p->monto, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(!$esBob)
                                        <span class="fw-semibold text-primary">Bs {{ number_format($p->monto_en_bob, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ ucfirst($p->metodo_pago) }}</small>
                                </td>
                                <td>
                                    <small>{{ $p->nombre_receptor }}</small>
                                    @if($p->cuentaDestino)
                                        <small class="text-muted d-block">
                                            🏦 {{ $p->cuentaDestino->banco->nombre ?? '' }}
                                            @if($p->cuentaDestino->tipo_relacion)
                                                <span class="badge bg-secondary ms-1" style="font-size:.6rem">{{ $p->cuentaDestino->tipo_relacion }}</span>
                                            @endif
                                            @if($p->cuentaDestino->alias) ({{ $p->cuentaDestino->alias }}) @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($p->cuentaOrigen)
                                        <small>{{ $p->cuentaOrigen->empresa->nombre ?? '—' }}</small>
                                        @if($p->cuentaOrigen->alias)
                                            <small class="text-muted d-block">({{ $p->cuentaOrigen->alias }})</small>
                                        @endif
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $p->codigo_seguimiento ?? '—' }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
</section>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
function filtrarPagos() {
    const camionId = document.getElementById('filtro_camion').value;
    const tipo     = document.getElementById('filtro_tipo').value;
    const filas    = document.querySelectorAll('#tabla_historial tbody tr');
    let visibles   = 0;

    filas.forEach(function(fila) {
        const okCamion = !camionId || fila.dataset.camionId == camionId;
        const okTipo   = !tipo     || fila.dataset.tipo === tipo;
        const mostrar  = okCamion && okTipo;
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    document.getElementById('lbl_count').textContent = visibles;
}

function limpiarFiltros() {
    document.getElementById('filtro_camion').value = '';
    document.getElementById('filtro_tipo').value   = '';
    filtrarPagos();
}
</script>
@endsection
