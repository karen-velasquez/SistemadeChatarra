@extends('layouts.app')
@section('titulo','Ficha de Adquisición')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>{{ $adquisicion->descripcion }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('adquisiciones.index') }}">Créditos y Adquisiciones</a></li>
                    <li class="breadcrumb-item active">Ficha</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('adquisiciones.edit')
            <button class="btn btn-outline-primary btn-sm"
                    onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show()">
                <i class="bi bi-pencil"></i> Editar
            </button>
            @endcan
            @can('adquisiciones.destroy')
            <a href="{{ route('adquisiciones.destroy', $adquisicion->uuid) }}" class="btn btn-outline-danger btn-sm"
               onclick="return confirm('¿Eliminar esta adquisición y su plan de pagos? Solo es posible si no tiene pagos registrados.')">
                <i class="bi bi-trash"></i>
            </a>
            @endcan
            <a href="{{ route('adquisiciones.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<section class="section">

    {{-- RESUMEN --}}
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-1"></i> Datos de la Adquisición</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-muted d-block">Financiamiento</small>
                    <strong>
                        @if($adquisicion->financiamiento === 'CREDITO')
                            Crédito — {{ $adquisicion->entidad_financiera ?: 's/entidad' }}
                        @else
                            Capital de la empresa
                        @endif
                    </strong>
                </div>
                @if($adquisicion->financiamiento === 'CREDITO')
                <div class="col-md-3">
                    <small class="text-muted d-block">Interés</small>
                    <strong>
                        @if($adquisicion->tasa_interes)
                            {{ $adquisicion->tasa_interes }}% anual ({{ strtolower($adquisicion->tipo_interes ?? 'fijo') }})
                        @else — @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Contrato</small>
                    <strong>
                        {{ $adquisicion->fecha_contrato_inicio?->format('d/m/Y') ?? '—' }}
                        al {{ $adquisicion->fecha_contrato_fin?->format('d/m/Y') ?? '—' }}
                    </strong>
                </div>
                @endif
                <div class="col-md-3">
                    <small class="text-muted d-block">Origen</small>
                    <strong>{{ $adquisicion->origen === 'EXTERIOR' ? 'Exterior — ' . ($adquisicion->paisOrigen->descripcion ?? '') : 'Nacional' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Vendedor / Beneficiario</small>
                    <strong>{{ $adquisicion->vendedor ?: '—' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Fecha de adquisición</small>
                    <strong>{{ $adquisicion->fecha_adquisicion->format('d/m/Y') }}</strong>
                </div>
                @if($adquisicion->camion)
                <div class="col-md-3">
                    <small class="text-muted d-block">Unidad vinculada</small>
                    <strong><a href="{{ route('unidades.show', $adquisicion->camion->uuid) }}">{{ $adquisicion->camion->placa }}</a></strong>
                </div>
                @endif
                <div class="col-md-3">
                    <small class="text-muted d-block">Monto total</small>
                    <strong>{{ number_format($adquisicion->monto_total, 2) }} {{ $adquisicion->moneda }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Saldo pendiente</small>
                    <strong class="{{ $adquisicion->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($adquisicion->saldo_pendiente, 2) }} {{ $adquisicion->moneda }}
                    </strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Total pagado (Bs)</small>
                    <strong>{{ number_format($adquisicion->total_pagado_bob, 2) }} Bs</strong>
                </div>
            </div>
            @if($adquisicion->observaciones)
            <p class="text-muted small mt-3 mb-0"><i class="bi bi-chat-left-text me-1"></i>{{ $adquisicion->observaciones }}</p>
            @endif
        </div>
    </div>

    {{-- PLAN DE PAGOS --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-calendar-check me-1"></i> Plan de Pagos</h5>
                @can('adquisiciones.edit')
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm"
                            onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGenerarPlan')).show()">
                        <i class="bi bi-magic"></i> Generar Cuotas Mensuales
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="nuevaCuota()">
                        <i class="bi bi-plus-lg"></i> Agregar Cuota
                    </button>
                </div>
                @endcan
            </div>
            <p class="text-muted small">
                Los pagos se registran en <strong>bolivianos al tipo de cambio de cada pago</strong>.
                Las cuotas atrasadas muestran la deuda del mes con el interés acumulado por los días de atraso
                (tasa anual / 360 × días). Para interés variable, edite la tasa de la cuota antes de pagarla.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha programada</th>
                            <th class="text-end">Cuota ({{ $adquisicion->moneda }})</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Deuda con mora</th>
                            <th>Pago realizado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adquisicion->cuotas as $c)
                        <tr class="{{ $c->estado === 'ATRASADO' ? 'table-danger' : ($c->estado === 'PAGADO' ? 'table-success' : '') }}">
                            <td>{{ $c->nro }}</td>
                            <td>
                                {{ $c->fecha_programada->format('d/m/Y') }}
                                @if($c->observaciones)<small class="text-muted d-block">{{ $c->observaciones }}</small>@endif
                            </td>
                            <td class="text-end">
                                {{ number_format($c->monto, 2) }}
                                @if($c->tasa_aplicada !== null)
                                    <small class="text-muted d-block">tasa {{ $c->tasa_aplicada }}%</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($c->estado === 'PAGADO') <span class="badge bg-success">Pagado</span>
                                @elseif($c->estado === 'ATRASADO')
                                    <span class="badge bg-danger">Atrasado {{ $c->dias_atraso }} día(s)</span>
                                @else <span class="badge bg-secondary">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($c->estado === 'ATRASADO')
                                    <strong class="text-danger">{{ number_format($c->deuda, 2) }} {{ $adquisicion->moneda }}</strong>
                                    <small class="text-muted d-block">mora: {{ number_format($c->interes_mora, 2) }}</small>
                                @else — @endif
                            </td>
                            <td>
                                @if($c->fecha_pago)
                                    {{ $c->fecha_pago->format('d/m/Y') }} ·
                                    {{ number_format($c->monto_pagado, 2) }} {{ $adquisicion->moneda }}
                                    <small class="text-muted d-block">
                                        T.C. {{ rtrim(rtrim(number_format($c->tipo_cambio, 4), '0'), '.') }} =
                                        <strong>{{ number_format($c->monto_pagado_bob, 2) }} Bs</strong>
                                    </small>
                                @else — @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @if(!$c->fecha_pago)
                                    @can('adquisiciones.edit')
                                    <button class="btn btn-sm btn-success" title="Registrar pago"
                                            onclick='pagarCuota(@json($c->uuid), @json($c->nro), {{ $c->monto }})'>
                                        <i class="bi bi-cash-coin"></i> Pagar
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar cuota"
                                            onclick='editarCuota(@json($c))'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endcan
                                    @can('adquisiciones.destroy')
                                    <a href="{{ route('adquisiciones.cuotas.destroy', $c->uuid) }}"
                                       class="btn btn-sm btn-outline-danger" title="Eliminar cuota"
                                       onclick="return confirm('¿Eliminar la cuota #{{ $c->nro }}?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    @endcan
                                @else
                                    @if($c->comprobante)
                                    <a href="{{ route('adquisiciones.cuotas.comprobante', $c->uuid) }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary" title="Ver comprobante">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    @endif
                                    @can('adquisiciones.destroy')
                                    <a href="{{ route('adquisiciones.cuotas.anular', $c->uuid) }}"
                                       class="btn btn-sm btn-outline-warning" title="Anular pago"
                                       onclick="return confirm('¿Anular el pago de la cuota #{{ $c->nro }}? Volverá a estar pendiente.')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            Aún no hay plan de pagos. Use <strong>Generar Cuotas Mensuales</strong> o agregue cuotas una a una.
                        </td></tr>
                        @endforelse
                    </tbody>
                    @if($adquisicion->cuotas->count())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Totales:</td>
                            <td class="text-end">{{ number_format($adquisicion->cuotas->sum('monto'), 2) }}</td>
                            <td></td><td></td>
                            <td>{{ number_format($adquisicion->total_pagado_bob, 2) }} Bs pagados</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- FACTURAS DE COMPRA --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-receipt-cutoff me-1"></i> Facturas de Compra</h5>
                @can('adquisiciones.edit')
                <button class="btn btn-outline-primary btn-sm"
                        onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFactura')).show()">
                    <i class="bi bi-plus-lg"></i> Nueva Factura
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Emisor</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adquisicion->facturas as $f)
                        <tr>
                            <td>{{ $f->numero ?: '—' }}</td>
                            <td>{{ $f->fecha->format('d/m/Y') }}</td>
                            <td>{{ $f->emisor ?: '—' }}</td>
                            <td class="text-end">{{ number_format($f->monto, 2) }} {{ $f->moneda }}</td>
                            <td class="text-center">
                                @if($f->archivo)
                                <a href="{{ route('adquisiciones.facturas.ver', $f->uuid) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary" title="Ver factura">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @can('adquisiciones.destroy')
                                <a href="{{ route('adquisiciones.facturas.destroy', $f->uuid) }}"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar esta factura?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay facturas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@can('adquisiciones.edit')
{{-- MODAL EDITAR ADQUISICIÓN --}}
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Adquisición</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('adquisiciones.update', $adquisicion->uuid) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @include('adquisiciones.partials.form-adquisicion', ['a' => $adquisicion])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL GENERAR PLAN --}}
<div class="modal fade" id="modalGenerarPlan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-magic"></i> Generar Cuotas Mensuales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('adquisiciones.generar_plan', $adquisicion->uuid) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">N° de cuotas <span class="text-danger">*</span></label>
                            <input type="number" name="nro_cuotas" class="form-control" required min="1" max="360" id="gp_nro">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto por cuota <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto_cuota" class="form-control" required id="gp_monto">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Primera fecha <span class="text-danger">*</span></label>
                            <input type="date" name="primera_fecha" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info small mb-0">
                                Se crearán cuotas con vencimiento cada mes a partir de la primera fecha.
                                Total del plan: <strong id="gp_total">—</strong> {{ $adquisicion->moneda }}
                                (monto de la adquisición: {{ number_format($adquisicion->monto_total, 2) }} {{ $adquisicion->moneda }}).
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Generar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL CUOTA (nueva / editar) --}}
<div class="modal fade" id="modalCuota" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloCuota"><i class="bi bi-plus-circle"></i> Agregar Cuota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCuota" method="POST" action="{{ route('adquisiciones.cuotas.store', $adquisicion->uuid) }}">
                @csrf
                <input type="hidden" name="_method" id="cuotaMethod" value="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha programada <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_programada" id="cuota_fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto ({{ $adquisicion->moneda }}) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto" id="cuota_monto" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tasa % anual de esta cuota</label>
                            <input type="number" step="0.001" min="0" max="100" name="tasa_aplicada" id="cuota_tasa" class="form-control">
                            <div class="form-text">Solo para interés variable; si se deja vacío usa la tasa del crédito.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" id="cuota_obs" class="form-control" maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PAGAR CUOTA --}}
<div class="modal fade" id="modalPagar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloPagar"><i class="bi bi-cash-coin"></i> Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPagar" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha de pago <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_pago" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto pagado ({{ $adquisicion->moneda }}) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto_pagado" id="pago_monto" class="form-control" required>
                            <div class="form-text">Incluya la mora si corresponde.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de cambio (Bs por {{ $adquisicion->moneda }}) <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="tipo_cambio" id="pago_tc" class="form-control" required
                                   value="{{ $adquisicion->moneda === 'BOB' ? '1' : '' }}">
                            <div class="form-text">El cambio que le dio el librecambista en este pago.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total en bolivianos</label>
                            <input type="text" id="pago_bob" class="form-control" disabled placeholder="—">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Comprobante (PDF o imagen)</label>
                            <input type="file" name="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" maxlength="255"
                                   placeholder="Ej: depósito del librecambista en reales">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-cash-coin"></i> Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL NUEVA FACTURA --}}
<div class="modal fade" id="modalFactura" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Nueva Factura de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('adquisiciones.facturas.store', $adquisicion->uuid) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Número de factura</label>
                            <input type="text" name="numero" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emisor</label>
                            <input type="text" name="emisor" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monto <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Moneda</label>
                            <select name="moneda" class="form-select">
                                @foreach(['BOB','USD','BRL','EUR'] as $m)
                                    <option value="{{ $m }}" @selected($m === $adquisicion->moneda)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Archivo (PDF o imagen)</label>
                            <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" maxlength="255">
                        </div>
                    </div>
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

    // Total del plan generado
    const nro = document.getElementById('gp_nro'), monto = document.getElementById('gp_monto');
    function calcTotal() {
        const t = (parseFloat(nro.value) || 0) * (parseFloat(monto.value) || 0);
        document.getElementById('gp_total').textContent = t ? t.toLocaleString('es-BO', {minimumFractionDigits: 2}) : '—';
    }
    if (nro) { nro.addEventListener('input', calcTotal); monto.addEventListener('input', calcTotal); }

    // Total en Bs del pago
    const pm = document.getElementById('pago_monto'), tc = document.getElementById('pago_tc');
    function calcBob() {
        const t = (parseFloat(pm.value) || 0) * (parseFloat(tc.value) || 0);
        document.getElementById('pago_bob').value = t ? t.toLocaleString('es-BO', {minimumFractionDigits: 2}) + ' Bs' : '';
    }
    if (pm) { pm.addEventListener('input', calcBob); tc.addEventListener('input', calcBob); }
});

function nuevaCuota() {
    const f = document.getElementById('formCuota');
    f.reset();
    f.action = "{{ route('adquisiciones.cuotas.store', $adquisicion->uuid) }}";
    document.getElementById('cuotaMethod').value = 'POST';
    document.getElementById('tituloCuota').innerHTML = '<i class="bi bi-plus-circle"></i> Agregar Cuota';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCuota')).show();
}

function editarCuota(c) {
    const f = document.getElementById('formCuota');
    f.reset();
    f.action = url_global + '/adquisiciones/cuota/' + c.uuid;
    document.getElementById('cuotaMethod').value = 'PUT';
    document.getElementById('tituloCuota').innerHTML = '<i class="bi bi-pencil"></i> Editar Cuota #' + c.nro;
    document.getElementById('cuota_fecha').value = (c.fecha_programada || '').substring(0, 10);
    document.getElementById('cuota_monto').value = c.monto ?? '';
    document.getElementById('cuota_tasa').value  = c.tasa_aplicada ?? '';
    document.getElementById('cuota_obs').value   = c.observaciones ?? '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCuota')).show();
}

function pagarCuota(uuid, nro, monto) {
    const f = document.getElementById('formPagar');
    f.reset();
    f.action = url_global + '/adquisiciones/cuota/' + uuid + '/pagar';
    document.getElementById('tituloPagar').innerHTML = '<i class="bi bi-cash-coin"></i> Registrar Pago — Cuota #' + nro;
    document.getElementById('pago_monto').value = monto;
    document.getElementById('pago_bob').value = '';
    @if($adquisicion->moneda === 'BOB') document.getElementById('pago_tc').value = '1'; @endif
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPagar')).show();
}
</script>
@endsection
