@extends('layouts.app')
@section('titulo','Unidades Propias')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>UNIDADES PROPIAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Unidades Propias</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('unidades.create')
            <button type="button" class="btn btn-primary btn-sm"
                    onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMarcar')).show()">
                <i class="bi bi-plus-lg"></i> Agregar Unidad Propia
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">

    {{-- UNIDADES --}}
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-truck-flatbed me-1"></i> Camiones de la Empresa</h5>
            <p class="text-muted small mb-3">
                Camiones propios de la empresa. Aquí se controla su documentación (RUAT, contrato, impuestos, seguros, SOAT),
                los mantenimientos realizados y el plan de mantenimiento por kilometraje. Estos camiones también pueden
                asignarse a contratos y viajar llevando carga como cualquier otro camión del sistema.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Marca / Modelo</th>
                            <th>Conductor actual</th>
                            <th class="text-end">Km actual</th>
                            <th class="text-center">Documentos</th>
                            <th class="text-center">Mantenimiento</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unidades as $u)
                        @php
                            $docsVencidos  = $u->documentos->filter(fn($d) => $d->estado_vencimiento === 'vencido')->count();
                            $docsPorVencer = $u->documentos->filter(fn($d) => $d->estado_vencimiento === 'por_vencer')->count();
                            $planVencidos  = $u->planMantenimientos->filter(fn($p) => $p->estado === 'vencido')->count();
                            $planProximos  = $u->planMantenimientos->filter(fn($p) => $p->estado === 'proximo')->count();
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $u->placa }}</td>
                            <td>{{ $u->marca->valor ?? '-' }} {{ $u->modelo }} ({{ $u->anio }})</td>
                            <td>{{ $u->conductorActual->conductor->nombre ?? '—' }}</td>
                            <td class="text-end">{{ number_format($u->kilometraje_actual, 0, ',', '.') }} km</td>
                            <td class="text-center">
                                @if($docsVencidos) <span class="badge bg-danger">{{ $docsVencidos }} vencido(s)</span> @endif
                                @if($docsPorVencer) <span class="badge bg-warning text-dark">{{ $docsPorVencer }} por vencer</span> @endif
                                @if(!$docsVencidos && !$docsPorVencer) <span class="badge bg-success">Al día</span> @endif
                            </td>
                            <td class="text-center">
                                @if($planVencidos) <span class="badge bg-danger">{{ $planVencidos }} pendiente(s)</span> @endif
                                @if($planProximos) <span class="badge bg-warning text-dark">{{ $planProximos }} próximo(s)</span> @endif
                                @if(!$planVencidos && !$planProximos) <span class="badge bg-success">Al día</span> @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('unidades.show', $u->uuid) }}" class="btn btn-sm btn-primary" title="Ver ficha">
                                    <i class="bi bi-folder2-open"></i> Ficha
                                </a>
                                @can('unidades.destroy')
                                <a href="{{ route('unidades.desmarcar', $u->uuid) }}" class="btn btn-sm btn-outline-danger"
                                   title="Quitar de unidades propias"
                                   onclick="return confirm('¿Quitar este camión de unidades propias? El camión NO se elimina, solo deja de figurar como propio.')">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-truck fs-2 d-block"></i>
                                No hay unidades propias registradas. Registre el camión en el módulo Camiones y agréguelo aquí.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TALLERES --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="bi bi-tools me-1"></i> Talleres de Confianza</h5>
                @can('unidades.edit')
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="nuevoTaller()">
                    <i class="bi bi-plus-lg"></i> Nuevo Taller
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Especialidad</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Contacto</th>
                            @can('unidades.edit')<th class="text-center" style="width:110px">Acciones</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($talleres as $t)
                        <tr>
                            <td class="fw-bold">{{ $t->nombre }}</td>
                            <td>{{ $t->especialidad ?? '—' }}</td>
                            <td>{{ $t->telefono ?? '—' }}</td>
                            <td>{{ $t->direccion ?? '—' }}</td>
                            <td>{{ $t->contacto ?? '—' }}</td>
                            @can('unidades.edit')
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary" onclick='editarTaller(@json($t))' title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @can('unidades.destroy')
                                <a href="{{ route('talleres.destroy', $t->uuid) }}" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar este taller?')" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </a>
                                @endcan
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay talleres registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- MODAL AGREGAR UNIDAD PROPIA --}}
@can('unidades.create')
<div class="modal fade" id="modalMarcar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck-flatbed"></i> Agregar Unidad Propia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('unidades.marcar') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        Seleccione un camión ya registrado en el sistema para marcarlo como unidad propia de la empresa.
                        Si el camión aún no existe, regístrelo primero en el módulo
                        <a href="{{ route('camiones.index') }}">Camiones</a>.
                    </p>
                    <label class="form-label">Camión <span class="text-danger">*</span></label>
                    <select name="camion_id" class="form-select" required>
                        <option value="">-- Seleccione un camión --</option>
                        @foreach($disponibles as $c)
                            <option value="{{ $c->id }}">{{ $c->placa }} — {{ $c->modelo }} ({{ $c->anio }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- MODAL TALLER (nuevo / editar) --}}
@can('unidades.edit')
<div class="modal fade" id="modalTaller" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloTaller"><i class="bi bi-tools"></i> Nuevo Taller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTaller" method="POST" action="{{ route('talleres.store') }}">
                @csrf
                <input type="hidden" name="_method" id="tallerMethod" value="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="taller_nombre" class="form-control" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Especialidad</label>
                            <input type="text" name="especialidad" id="taller_especialidad" class="form-control"
                                   placeholder="Ej: Chapería, Mecánica general" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="taller_telefono" class="form-control" maxlength="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="taller_direccion" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Persona de contacto</label>
                            <input type="text" name="contacto" id="taller_contacto" class="form-control" maxlength="150">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="taller_observaciones" class="form-control" rows="2"></textarea>
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

@section('scripts')
<script>
function nuevoTaller() {
    const f = document.getElementById('formTaller');
    f.reset();
    f.action = "{{ route('talleres.store') }}";
    document.getElementById('tallerMethod').value = 'POST';
    document.getElementById('tituloTaller').innerHTML = '<i class="bi bi-tools"></i> Nuevo Taller';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTaller')).show();
}

function editarTaller(t) {
    const f = document.getElementById('formTaller');
    f.reset();
    f.action = url_global + '/talleres/' + t.uuid;
    document.getElementById('tallerMethod').value = 'PUT';
    document.getElementById('tituloTaller').innerHTML = '<i class="bi bi-tools"></i> Editar Taller';
    document.getElementById('taller_nombre').value        = t.nombre ?? '';
    document.getElementById('taller_especialidad').value  = t.especialidad ?? '';
    document.getElementById('taller_telefono').value      = t.telefono ?? '';
    document.getElementById('taller_direccion').value     = t.direccion ?? '';
    document.getElementById('taller_contacto').value      = t.contacto ?? '';
    document.getElementById('taller_observaciones').value = t.observaciones ?? '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTaller')).show();
}
</script>
@endsection
