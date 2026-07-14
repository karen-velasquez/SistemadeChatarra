@extends('layouts.app')
@section('titulo','Ficha de Unidad Propia')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>UNIDAD {{ $camion->placa }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('unidades.index') }}">Unidades Propias</a></li>
                    <li class="breadcrumb-item active">{{ $camion->placa }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('unidades.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<section class="section">

    {{-- DATOS DEL CAMIÓN --}}
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-truck-front me-1"></i> Datos del Camión</h5>
            <div class="row g-3">
                <div class="col-md-2"><small class="text-muted d-block">Placa</small><strong>{{ $camion->placa }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Marca</small><strong>{{ $camion->marca->valor ?? '-' }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Modelo / Año</small><strong>{{ $camion->modelo }} ({{ $camion->anio }})</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Capacidad</small><strong>{{ number_format($camion->capacidad_kg / 1000, 2) }} Tn</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Conductor actual</small><strong>{{ $camion->conductorActual->conductor->nombre ?? '—' }}</strong></div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Kilometraje actual</small>
                    <strong>{{ number_format($camion->kilometraje_actual, 0, ',', '.') }} km</strong>
                    @can('unidades.edit')
                    <button class="btn btn-sm btn-outline-primary ms-1" title="Actualizar kilometraje"
                            onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKm')).show()">
                        <i class="bi bi-pencil"></i>
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- DOCUMENTOS --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-file-earmark-lock me-1"></i> Documentación</h5>
                @can('unidades.edit')
                <button class="btn btn-outline-primary btn-sm"
                        onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDocumento')).show()">
                    <i class="bi bi-plus-lg"></i> Nuevo Documento
                </button>
                @endcan
            </div>
            <p class="text-muted small">RUAT, contrato, pagos de impuestos anuales, seguro del camión, póliza SOAT y otros.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Monto (Bs)</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($camion->documentos as $d)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $d->tipo }}</span></td>
                            <td>{{ $d->descripcion ?? '—' }}</td>
                            <td>{{ $d->fecha_emision?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $d->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end">{{ $d->monto ? number_format($d->monto, 2) : '—' }}</td>
                            <td class="text-center">
                                @if($d->estado_vencimiento === 'vencido')
                                    <span class="badge bg-danger">Vencido</span>
                                @elseif($d->estado_vencimiento === 'por_vencer')
                                    <span class="badge bg-warning text-dark">Por vencer</span>
                                @elseif($d->estado_vencimiento === 'vigente')
                                    <span class="badge bg-success">Vigente</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($d->archivo)
                                <a href="{{ route('unidades.documentos.ver', $d->uuid) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary" title="Ver archivo">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @can('unidades.destroy')
                                <a href="{{ route('unidades.documentos.destroy', $d->uuid) }}"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar este documento?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay documentos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PLAN DE MANTENIMIENTO POR KILOMETRAJE --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-speedometer2 me-1"></i> Plan de Mantenimiento por Kilometraje</h5>
                @can('unidades.edit')
                <button class="btn btn-outline-primary btn-sm"
                        onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPlan')).show()">
                    <i class="bi bi-plus-lg"></i> Nuevo Control
                </button>
                @endcan
            </div>
            <p class="text-muted small">
                Controles periódicos por kilometraje. Ej: cambio de aceite cada 5.000 km, revisión de frenos cada 20.000 km.
                Al actualizar el kilometraje del camión, el sistema avisa qué controles están próximos o pendientes.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th class="text-end">Cada (km)</th>
                            <th class="text-end">Último control (km)</th>
                            <th class="text-end">Recorridos desde entonces</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($camion->planMantenimientos as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->tarea }}</strong>
                                @if($p->notas)<small class="text-muted d-block">{{ $p->notas }}</small>@endif
                            </td>
                            <td class="text-end">{{ number_format($p->intervalo_km, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($p->ultimo_km, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($p->km_recorridos, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($p->estado === 'vencido')
                                    <span class="badge bg-danger">Pendiente</span>
                                @elseif($p->estado === 'proximo')
                                    <span class="badge bg-warning text-dark">Próximo</span>
                                @else
                                    <span class="badge bg-success">Al día</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('unidades.edit')
                                <a href="{{ route('unidades.plan.realizar', $p->uuid) }}"
                                   class="btn btn-sm btn-outline-success" title="Marcar como realizado al km actual"
                                   onclick="return confirm('¿Marcar esta tarea como realizada al kilometraje actual ({{ number_format($camion->kilometraje_actual, 0, ',', '.') }} km)?')">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                @endcan
                                @can('unidades.destroy')
                                <a href="{{ route('unidades.plan.destroy', $p->uuid) }}"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar este control del plan?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay controles definidos en el plan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- HISTORIAL DE MANTENIMIENTOS --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-wrench-adjustable me-1"></i> Historial de Mantenimientos y Gastos</h5>
                @can('unidades.edit')
                <button class="btn btn-outline-primary btn-sm"
                        onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMantenimiento')).show()">
                    <i class="bi bi-plus-lg"></i> Registrar Mantenimiento
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Taller</th>
                            <th class="text-end">Km</th>
                            <th class="text-end">Costo (Bs)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($camion->mantenimientos as $m)
                        <tr>
                            <td>{{ $m->fecha->format('d/m/Y') }}</td>
                            <td>{{ $m->tipo }}</td>
                            <td>{{ $m->descripcion ?? '—' }}</td>
                            <td>{{ $m->taller->nombre ?? '—' }}</td>
                            <td class="text-end">{{ $m->kilometraje ? number_format($m->kilometraje, 0, ',', '.') : '—' }}</td>
                            <td class="text-end">{{ number_format($m->costo, 2) }}</td>
                            <td class="text-center">
                                @if($m->comprobante)
                                <a href="{{ route('unidades.mantenimientos.comprobante', $m->uuid) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary" title="Ver comprobante">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                @endif
                                @can('unidades.destroy')
                                <a href="{{ route('unidades.mantenimientos.destroy', $m->uuid) }}"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar este registro de mantenimiento?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay mantenimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                    @if($camion->mantenimientos->count())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">Total gastado:</td>
                            <td class="text-end">{{ number_format($camion->mantenimientos->sum('costo'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</section>

@can('unidades.edit')
{{-- MODAL ACTUALIZAR KM --}}
<div class="modal fade" id="modalKm" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-speedometer2"></i> Actualizar Kilometraje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('unidades.km', $camion->uuid) }}">
                @csrf
                <div class="modal-body">
                    <label class="form-label">Kilometraje actual <span class="text-danger">*</span></label>
                    <input type="number" name="kilometraje_actual" class="form-control" required
                           min="{{ $camion->kilometraje_actual }}" value="{{ $camion->kilometraje_actual }}">
                    <div class="form-text">No puede ser menor al kilometraje registrado.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL NUEVO DOCUMENTO --}}
<div class="modal fade" id="modalDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus"></i> Nuevo Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('unidades.documentos.store', $camion->uuid) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="">-- Seleccione --</option>
                                <option value="RUAT">RUAT</option>
                                <option value="CONTRATO">Contrato</option>
                                <option value="IMPUESTO">Pago de impuesto anual</option>
                                <option value="SEGURO">Seguro del camión</option>
                                <option value="SOAT">SOAT (Póliza)</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" maxlength="255"
                                   placeholder="Ej: Impuesto gestión 2026">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de emisión</label>
                            <input type="date" name="fecha_emision" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                            <div class="form-text">Se avisará 30 días antes.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto pagado (Bs)</label>
                            <input type="number" step="0.01" min="0" name="monto" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Archivo (PDF o imagen, máx. 10 MB)</label>
                            <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control">
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

{{-- MODAL NUEVO MANTENIMIENTO --}}
<div class="modal fade" id="modalMantenimiento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-wrench-adjustable"></i> Registrar Mantenimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('unidades.mantenimientos.store', $camion->uuid) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de trabajo <span class="text-danger">*</span></label>
                            <input type="text" name="tipo" class="form-control" required maxlength="100"
                                   placeholder="Ej: Chapería, Cambio de aceite, Frenos">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taller</label>
                            <select name="taller_id" class="form-select">
                                <option value="">-- Sin taller / propio --</option>
                                @foreach($talleres as $t)
                                    <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Costo (Bs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="costo" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kilometraje al momento</label>
                            <input type="number" min="0" name="kilometraje" class="form-control"
                                   value="{{ $camion->kilometraje_actual }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Comprobante (PDF o imagen)</label>
                            <input type="file" name="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción del trabajo</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
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

{{-- MODAL NUEVO CONTROL DE PLAN --}}
<div class="modal fade" id="modalPlan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-speedometer2"></i> Nuevo Control por Kilometraje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('unidades.plan.store', $camion->uuid) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tarea <span class="text-danger">*</span></label>
                            <input type="text" name="tarea" class="form-control" required maxlength="150"
                                   placeholder="Ej: Cambio de aceite y filtros">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cada cuántos km <span class="text-danger">*</span></label>
                            <input type="number" name="intervalo_km" class="form-control" required min="1"
                                   placeholder="Ej: 5000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Km del último control</label>
                            <input type="number" name="ultimo_km" class="form-control" min="0"
                                   value="{{ $camion->kilometraje_actual }}">
                            <div class="form-text">Si se deja vacío, se usa el km actual.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notas" class="form-control" rows="2"
                                      placeholder="Ej: usar aceite 15W-40, revisar también filtro de aire"></textarea>
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
@endcan

@endsection
