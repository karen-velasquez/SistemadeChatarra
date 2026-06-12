@extends('layouts.app')
@section('titulo','Parámetros del Sistema')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PARÁMETROS DEL SISTEMA</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Parámetros</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"⚙️ Los <b>Parámetros</b> son los catálogos que usa todo el sistema: cargos de empleados, países, monedas, marcas de camión, sucursales bancarias, etc."},
                        {"element":"#param-nota","intro":"ℹ️ Aquí gestionas esos catálogos. Puedes <b>editar la descripción</b> de los existentes y, si tienes permiso, <b>agregar nuevos</b>.","position":"bottom"},
                        {"element":"#param-primer-grupo","intro":"🗂️ Los parámetros se agrupan por <b>tipo</b>. Haz clic en el encabezado de un grupo para <b>desplegar</b> sus valores. Dentro verás el Valor y la Descripción de cada uno; los que dicen <b>En uso</b> están siendo usados por otros registros. Con el botón ✏️ de cada fila editas su descripción.","position":"bottom"},
                        {"element":"#btnNuevoParametro","intro":"➕ Con <b>Nuevo Parámetro</b> agregas un valor nuevo a un catálogo: eliges el <b>grupo</b> de la lista y escribes el valor.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('parametros.create')
            <button type="button" id="btnNuevoParametro" class="btn btn-primary btn-sm"
                    onclick="nuevoParametro()">
                <i class="bi bi-plus-lg"></i> Nuevo Parámetro
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Parámetros del Sistema</h5>
            <p class="text-muted small mb-3">
                <i class="bi bi-sliders me-1"></i>
                Configuración de valores parametrizables del sistema organizados por tipo. Los parámetros permiten gestionar catálogos como cargos de empleados, lugares, países, sucursales bancarias, entre otros. Los parámetros en uso no pueden ser eliminados para mantener la integridad de los datos.
            </p>
            <div class="alert alert-info border-0 mb-3" id="param-nota">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Nota:</strong> Los parámetros del sistema son gestionados desde los seeders y la base de datos. Solo puede editar la descripción de parámetros existentes. Para agregar nuevos tipos o valores, contacte al administrador del sistema.
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-tag-fill text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Formato Tipo</small>
                            <strong class="small">minúsculas_con_guion</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-fonts text-success me-2"></i>
                        <div>
                            <small class="text-muted d-block">Formato Valor</small>
                            <strong class="small">MAYÚSCULAS</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-lock-fill text-info me-2"></i>
                        <div>
                            <small class="text-muted d-block">Protección</small>
                            <strong class="small">Datos en uso bloqueados</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @forelse($parametros as $tipo => $items)
    <div class="card mb-3" @if($loop->first) id="param-primer-grupo" @endif>
        <div class="card-header bg-transparent fw-bold"
             style="cursor: pointer;"
             data-bs-toggle="collapse"
             data-bs-target="#collapse-{{ \Str::slug($tipo) }}"
             aria-expanded="false"
             aria-controls="collapse-{{ \Str::slug($tipo) }}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-tag me-1"></i>{{ $tipo }}
                    <span class="badge bg-secondary ms-2">{{ $items->count() }}</span>
                </div>
                <i class="bi bi-chevron-down"></i>
            </div>
        </div>
        <div id="collapse-{{ \Str::slug($tipo) }}" class="collapse">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Valor</th>
                            <th>Descripción</th>
                            @can('parametros.edit')
                            <th class="text-center" style="width:80px">Acciones</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $p)
                        @php
                            $verificacion = $p->verificarUso();
                        @endphp
                        <tr>
                            <td>
                                {{ $p->valor }}
                                @if($verificacion['enUso'])
                                    <span class="badge bg-info text-dark ms-2" title="{{ $verificacion['mensaje'] }}">
                                        <i class="bi bi-link-45deg"></i> En uso
                                    </span>
                                @endif
                            </td>
                            <td>{{ $p->descripcion ?? '—' }}</td>
                            @can('parametros.edit')
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editarParametro('{{ $p->uuid }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                            @endcan
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="bi bi-sliders fs-1"></i>
        <p class="mt-2">No hay parámetros registrados.</p>
    </div>
    @endforelse
</section>

{{-- MODAL NUEVO PARÁMETRO --}}
@can('parametros.create')
<div class="modal fade" id="modalNuevoParametro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nuevo Parámetro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoParametro" method="POST" action="{{ route('parametros.store') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3"><span class="text-danger">*</span> Campos obligatorios.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tipo / Grupo <span class="text-danger">*</span></label>
                            <select name="tipo" id="nuevo_tipo" class="form-select" required>
                                <option value="">-- Seleccione un grupo --</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Elige el grupo (catálogo) al que pertenece el nuevo valor.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="text" name="valor" id="nuevo_valor" class="form-control"
                                   required placeholder="Ej: SUPERVISOR"
                                   oninput="this.value=this.value.toUpperCase()">
                            <div class="form-text">El valor que se mostrará. Se guarda en MAYÚSCULAS.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" id="nuevo_descripcion" class="form-control"
                                   placeholder="Información adicional (opcional)">
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

{{-- MODAL EDITAR DESCRIPCIÓN --}}
<div class="modal fade" id="modalParametro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-sliders"></i> Editar Descripción del Parámetro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formParametro" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tipo / Grupo</label>
                            <input type="text" id="tipo" class="form-control" disabled>
                            <div class="form-text">El tipo no puede ser modificado.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Valor</label>
                            <input type="text" id="valor" class="form-control" disabled>
                            <div class="form-text">El valor no puede ser modificado.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" id="descripcion" class="form-control"
                                   placeholder="Información adicional (opcional)">
                            <div class="form-text">Puede actualizar la descripción del parámetro.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
/* Animación del icono chevron */
.card-header[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.3s ease;
}

.card-header[data-bs-toggle="collapse"][aria-expanded="false"] .bi-chevron-down {
    transform: rotate(-90deg);
}

.card-header[data-bs-toggle="collapse"]:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
</style>
<script>
function nuevoParametro() {
    const form = document.getElementById('formNuevoParametro');
    if (form) form.reset();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevoParametro')).show();
}

function editarParametro(uuid) {
    fetch(url_global + '/parametros/' + uuid + '/edit')
        .then(r => r.json())
        .then(p => {
            document.getElementById('formParametro').action = url_global + '/parametros/' + uuid;
            document.getElementById('tipo').value           = p.tipo ?? '';
            document.getElementById('valor').value          = p.valor ?? '';
            document.getElementById('descripcion').value    = p.descripcion ?? '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalParametro')).show();
        });
}

// Sincronizar el estado de aria-expanded con los collapse
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.collapse').forEach(collapse => {
        collapse.addEventListener('shown.bs.collapse', function() {
            const header = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (header) header.setAttribute('aria-expanded', 'true');
        });

        collapse.addEventListener('hidden.bs.collapse', function() {
            const header = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (header) header.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>
@endsection
