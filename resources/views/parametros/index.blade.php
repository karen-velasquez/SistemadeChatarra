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
        @can('parametros.create')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalParametro" onclick="resetModal()">
            <i class="bi bi-plus-lg"></i> Nuevo Parámetro
        </button>
        @endcan
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
    <div class="card mb-3">
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
                            @canany(['parametros.edit','parametros.destroy'])
                            <th class="text-center" style="width:100px">Acciones</th>
                            @endcanany
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
                            @canany(['parametros.edit','parametros.destroy'])
                            <td class="text-center">
                                @can('parametros.edit')
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editarParametro('{{ $p->uuid }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endcan
                                @can('parametros.destroy')
                                @if($verificacion['enUso'])
                                    <button class="btn btn-sm btn-secondary" disabled
                                            title="{{ $verificacion['mensaje'] }}"
                                            style="opacity: 0.5; cursor: not-allowed;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <a href="{{ route('parametros.destroy', $p->uuid) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Eliminar este parámetro?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                @endif
                                @endcan
                            </td>
                            @endcanany
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

{{-- MODAL PARÁMETRO --}}
<div class="modal fade" id="modalParametro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-sliders"></i> <span id="tituloModal">Nuevo Parámetro</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formParametro" method="POST" action="{{ route('parametros.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodParametro" value="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tipo / Grupo <span class="text-danger">(*)</span></label>
                            <select name="tipo" id="tipo" class="form-select" required onchange="toggleNuevoTipo(); validarFormularioParametro()">
                                <option value="">-- Seleccione un tipo --</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                                <option value="__NUEVO__">+ Crear nuevo tipo</option>
                            </select>
                            <input type="text" name="tipo_nuevo" id="tipo_nuevo"
                                   class="form-control mt-2"
                                   style="display: none; text-transform: lowercase;"
                                   placeholder="Ej: cargo_vendedores, tipo_documentos"
                                   oninput="this.value = this.value.toLowerCase().replace(/\s+/g, '_'); validarFormularioParametro()">
                            <div class="form-text">Categoría a la que pertenece este parámetro.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Valor <span class="text-danger">(*)</span></label>
                            <input type="text" name="valor" id="valor" class="form-control text-uppercase"
                                   required placeholder="Ej: ADMINISTRADOR, CONTADOR"
                                   oninput="this.value = this.value.toUpperCase(); validarFormularioParametro()">
                            <div class="form-text">Nombre o valor principal del parámetro.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" id="descripcion" class="form-control"
                                   placeholder="Información adicional (opcional)">
                            <div class="form-text">Detalles adicionales sobre este parámetro.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar" disabled>Registrar</button>
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
function resetModal() {
    document.getElementById('tituloModal').innerText   = 'Nuevo Parámetro';
    document.getElementById('btnGuardar').innerText    = 'Registrar';
    document.getElementById('methodParametro').value   = 'POST';
    document.getElementById('formParametro').action    = '{{ route("parametros.store") }}';
    document.getElementById('formParametro').reset();
    document.getElementById('tipo_nuevo').style.display = 'none';
    document.getElementById('tipo_nuevo').removeAttribute('required');
    document.getElementById('btnGuardar').disabled = true;
}

function validarFormularioParametro() {
    const tipoSelect = document.getElementById('tipo');
    const tipoNuevo = document.getElementById('tipo_nuevo');
    const valor = document.getElementById('valor');
    const btnGuardar = document.getElementById('btnGuardar');

    let tipoValido = false;

    // Verificar si seleccionó un tipo existente o está creando uno nuevo
    if (tipoSelect.value === '__NUEVO__') {
        tipoValido = tipoNuevo.value.trim() !== '';
    } else {
        tipoValido = tipoSelect.value !== '';
    }

    // El formulario es válido si tiene tipo Y valor
    const formularioValido = tipoValido && valor.value.trim() !== '';

    btnGuardar.disabled = !formularioValido;
}

function editarParametro(uuid) {
    fetch('/parametros/' + uuid + '/edit')
        .then(r => r.json())
        .then(p => {
            document.getElementById('tituloModal').innerText  = 'Editar Parámetro';
            document.getElementById('btnGuardar').innerText   = 'Actualizar';
            document.getElementById('methodParametro').value  = 'PUT';
            document.getElementById('formParametro').action   = '/parametros/' + uuid;
            document.getElementById('tipo').value             = p.tipo ?? '';
            document.getElementById('descripcion').value      = p.descripcion ?? '';
            document.getElementById('valor').value            = p.valor ?? '';
            document.getElementById('tipo_nuevo').style.display = 'none';
            document.getElementById('tipo_nuevo').removeAttribute('required');
            validarFormularioParametro(); // Validar al cargar datos de edición
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalParametro')).show();
        });
}

function toggleNuevoTipo() {
    const tipoSelect = document.getElementById('tipo');
    const tipoNuevoInput = document.getElementById('tipo_nuevo');

    if (tipoSelect.value === '__NUEVO__') {
        tipoNuevoInput.style.display = 'block';
        tipoNuevoInput.setAttribute('required', 'required');
        tipoNuevoInput.focus();
    } else {
        tipoNuevoInput.style.display = 'none';
        tipoNuevoInput.removeAttribute('required');
        tipoNuevoInput.value = '';
    }
}

// Interceptar el submit para manejar el nuevo tipo
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formParametro');
    form.addEventListener('submit', function(e) {
        const tipoSelect = document.getElementById('tipo');
        const tipoNuevoInput = document.getElementById('tipo_nuevo');

        if (tipoSelect.value === '__NUEVO__') {
            if (tipoNuevoInput.value.trim() === '') {
                e.preventDefault();
                alert('Por favor ingrese el nombre del nuevo tipo.');
                tipoNuevoInput.focus();
                return false;
            }
            // Reemplazar el valor del select con el valor del input
            tipoSelect.removeAttribute('required');
            tipoNuevoInput.name = 'tipo';
        }
    });

    // Sincronizar el estado de aria-expanded con los collapse
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
