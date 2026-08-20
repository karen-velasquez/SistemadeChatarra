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
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    @if($unidades->isEmpty())
                    data-steps='[
                        {"intro":"🚛 Este es el módulo de <b>Unidades Propias</b>: los camiones de la empresa. Aquí controlas su <b>documentación</b> (RUAT, seguros, SOAT, impuestos) y su <b>mantenimiento</b> por kilometraje.<br><br>📭 Todavía no hay unidades propias. Primero registra el camión en el módulo <b>Camiones</b> y luego agrégalo aquí con <b>Agregar Unidad Propia</b>."}
                    ]'
                    @else
                    data-steps='[
                        {"intro":"🚛 Este es el módulo de <b>Unidades Propias</b>: los camiones que son de la empresa. A diferencia de los camiones alquilados, de estos se controla la documentación y el mantenimiento. Te muestro cómo."},
                        {"element":"#tabla_unidades","intro":"📋 Cada fila es un camión propio, con su conductor asignado y el kilometraje actual.","position":"top"},
                        {"element":"#tabla_unidades thead th:nth-child(5)","intro":"📄 <b>Documentos</b>: avisa si hay papeles <span style=\"color:#dc3545\"><b>vencidos</b></span> o <span style=\"color:#ffc107\"><b>por vencer</b></span> (RUAT, seguros, SOAT, impuestos). Si dice <span style=\"color:#198754\"><b>Al día</b></span>, está todo en regla.","position":"bottom"},
                        {"element":"#tabla_unidades thead th:nth-child(6)","intro":"🔧 <b>Mantenimiento</b>: según el plan por kilometraje, avisa qué servicios ya tocan o están próximos.","position":"bottom"},
                        {"element":"#tabla_unidades tbody tr:first-child td:last-child","intro":"📂 La <b>Ficha</b> abre el detalle del camión: ahí cargas documentos, registras mantenimientos y actualizas el kilometraje. La ✕ lo quita de unidades propias sin borrar el camión.","position":"left"},
                        {"element":"#seccion_talleres","intro":"🔧 Abajo están los <b>Talleres de Confianza</b>: los talleres donde se atienden las unidades, con su especialidad y teléfono.","position":"top"},
                        {"element":"#btnAgregarUnidad","intro":"➕ <b>Agregar Unidad Propia</b> marca un camión ya registrado como propio de la empresa.","position":"left"}
                    ]'
                    @endif>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('unidades.create')
            <button type="button" id="btnAgregarUnidad" class="btn btn-primary btn-sm"
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
                <table class="table table-hover align-middle" id="tabla_unidades">
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
    <div class="card" id="seccion_talleres">
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
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay talleres registrados.</td></tr>
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
            <form id="formMarcar" method="POST" action="{{ route('unidades.marcar') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        Seleccione un camión ya registrado en el sistema para marcarlo como unidad propia de la empresa.
                        Si el camión aún no existe, regístrelo primero en el módulo
                        <a href="{{ route('camiones.index') }}">Camiones</a>.
                    </p>
                    <label class="form-label">Camión <span class="text-danger">*</span></label>
                    <select name="camion_id" id="marcar_camion_id" class="form-select" required>
                        <option value="">-- Seleccione un camión --</option>
                        @foreach($disponibles as $c)
                            <option value="{{ $c->id }}">{{ $c->placa }} — {{ $c->modelo }} ({{ $c->anio }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnMarcar"><i class="bi bi-save"></i> Agregar</button>
                </div>
            </form>
            <script>
            document.getElementById('formMarcar').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnMarcar');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });
            </script>
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
                            <div class="input-group">
                                <select id="taller_telefono_pais" class="form-select flex-grow-0" style="width:115px; min-width:115px; max-width:115px;"
                                    onchange="actualizarPrefijoTelefonoTaller()">
                                    <option value="Bolivia" data-code="+591" data-maxlen="8" data-placeholder="Ej: 70000000">BO +591</option>
                                    <option value="Argentina" data-code="+54" data-maxlen="10" data-placeholder="Ej: 1150000000">AR +54</option>
                                    <option value="Brasil" data-code="+55" data-maxlen="11" data-placeholder="Ej: 11900000000">BR +55</option>
                                    <option value="Chile" data-code="+56" data-maxlen="9" data-placeholder="Ej: 912345678">CL +56</option>
                                    <option value="Paraguay" data-code="+595" data-maxlen="9" data-placeholder="Ej: 981000000">PY +595</option>
                                    <option value="Perú" data-code="+51" data-maxlen="9" data-placeholder="Ej: 912345678">PE +51</option>
                                    <option value="Colombia" data-code="+57" data-maxlen="10" data-placeholder="Ej: 3001234567">CO +57</option>
                                    <option value="Ecuador" data-code="+593" data-maxlen="10" data-placeholder="Ej: 0991234567">EC +593</option>
                                    <option value="Uruguay" data-code="+598" data-maxlen="8" data-placeholder="Ej: 91234567">UY +598</option>
                                    <option value="Venezuela" data-code="+58" data-maxlen="10" data-placeholder="Ej: 4121234567">VE +58</option>
                                    <option value="México" data-code="+52" data-maxlen="10" data-placeholder="Ej: 5512345678">MX +52</option>
                                    <option value="Estados Unidos" data-code="+1" data-maxlen="10" data-placeholder="Ej: 2025550100">US +1</option>
                                    <option value="Canadá" data-code="+1" data-maxlen="10" data-placeholder="Ej: 4165550100">CA +1</option>
                                    <option value="España" data-code="+34" data-maxlen="9" data-placeholder="Ej: 612345678">ES +34</option>
                                </select>
                                <input type="hidden" name="telefono_prefijo" id="taller_telefono_prefijo" value="+591">
                                <input type="text" class="form-control" name="telefono" id="taller_telefono"
                                    maxlength="8" placeholder="Ej: 70000000"
                                    oninput="validarTelefonoTaller(this)">
                            </div>
                            <div id="taller_telefono_feedback" class="form-text d-none"></div>
                            <small id="taller_telefono_hint" class="text-muted">Bolivia: 8 dígitos comenzando en 6 o 7</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                            <input type="text" name="direccion" id="taller_direccion" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="taller_observaciones" class="form-control" rows="2" maxlength="100"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnTaller"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
            <script>
            document.getElementById('formTaller').addEventListener('submit', function(e) {
                var btn = document.getElementById('btnTaller');
                if (btn.disabled) { e.preventDefault(); return; }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            });
            </script>
        </div>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script>
// Buscador en el select de camión: la lista crece y buscar a mano se vuelve lento.
// Se inicializa al abrir el modal porque Select2 necesita el elemento visible,
// y con dropdownParent para que el desplegable no quede detrás del modal.
$('#modalMarcar').on('shown.bs.modal', function () {
    if (!$('#marcar_camion_id').data('select2')) {
        $('#marcar_camion_id').select2({
            placeholder: 'Busque por placa o modelo...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalMarcar'),
            language: {
                noResults:  () => 'No se encontró ningún camión disponible.',
                searching:  () => 'Buscando...'
            }
        });
    }
});

// Al cerrar, dejar el select y el botón listos para la próxima vez
$('#modalMarcar').on('hidden.bs.modal', function () {
    const sel = $('#marcar_camion_id');
    sel.val('');
    if (sel.data('select2')) sel.trigger('change.select2');

    const btn = document.getElementById('btnMarcar');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save"></i> Agregar';
});

function restaurarBotonTaller() {
    const btn = document.getElementById('btnTaller');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
}

function nuevoTaller() {
    const f = document.getElementById('formTaller');
    f.reset();
    f.action = "{{ route('talleres.store') }}";
    document.getElementById('tallerMethod').value = 'POST';
    document.getElementById('tituloTaller').innerHTML = '<i class="bi bi-tools"></i> Nuevo Taller';
    document.getElementById('taller_telefono_pais').value = 'Bolivia';
    actualizarPrefijoTelefonoTaller();
    restaurarBotonTaller();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTaller')).show();
}

function editarTaller(t) {
    const f = document.getElementById('formTaller');
    f.reset();
    f.action = url_global + '/talleres/' + t.uuid;
    document.getElementById('tallerMethod').value = 'PUT';
    document.getElementById('tituloTaller').innerHTML = '<i class="bi bi-tools"></i> Editar Taller';
    restaurarBotonTaller();
    document.getElementById('taller_nombre').value        = t.nombre ?? '';
    document.getElementById('taller_especialidad').value  = t.especialidad ?? '';
    document.getElementById('taller_telefono_pais').value = 'Bolivia';
    actualizarPrefijoTelefonoTaller();
    document.getElementById('taller_telefono').value      = t.telefono ?? '';
    document.getElementById('taller_direccion').value     = t.direccion ?? '';
    document.getElementById('taller_observaciones').value = t.observaciones ?? '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTaller')).show();
}

// Validación de teléfono - Prefijos por país (mismo patrón que el módulo de Empleados)
const _prefijosTaller = {
    'Bolivia': { code: '+591', hint: '8 dígitos, comienza en 6 o 7', pattern: /^[67]\d{7}$/ },
    'Argentina': { code: '+54', hint: 'Ej: 1150000000', pattern: /^\d{8,10}$/ },
    'Brasil': { code: '+55', hint: 'Ej: 11900000000', pattern: /^\d{9,11}$/ },
    'Chile': { code: '+56', hint: 'Ej: 912345678', pattern: /^\d{9}$/ },
    'Paraguay': { code: '+595', hint: 'Ej: 0981000000', pattern: /^\d{8,10}$/ },
    'Perú': { code: '+51', hint: 'Ej: 912345678', pattern: /^\d{9}$/ },
    'Colombia': { code: '+57', hint: 'Ej: 3001234567', pattern: /^\d{10}$/ },
    'Ecuador': { code: '+593', hint: 'Ej: 0991234567', pattern: /^\d{9,10}$/ },
    'Uruguay': { code: '+598', hint: 'Ej: 91234567', pattern: /^\d{8}$/ },
    'Venezuela': { code: '+58', hint: 'Ej: 4121234567', pattern: /^\d{10}$/ },
    'México': { code: '+52', hint: 'Ej: 5512345678', pattern: /^\d{10}$/ },
    'Estados Unidos': { code: '+1', hint: 'Ej: 2025550100', pattern: /^\d{10}$/ },
    'Canadá': { code: '+1', hint: 'Ej: 4165550100', pattern: /^\d{10}$/ },
    'España': { code: '+34', hint: 'Ej: 612345678', pattern: /^\d{9}$/ },
};

function actualizarPrefijoTelefonoTaller() {
    const sel = document.getElementById('taller_telefono_pais');
    const opt = sel.options[sel.selectedIndex];
    const code = opt ? (opt.dataset.code || '+') : '+';
    const pais = opt ? opt.value : '';
    const maxlen = opt && opt.dataset.maxlen ? parseInt(opt.dataset.maxlen) : 15;
    const ph = opt && opt.dataset.placeholder ? opt.dataset.placeholder : 'Número';
    const info = _prefijosTaller[pais] || { hint: 'Ingrese el número sin código de país' };

    const inp = document.getElementById('taller_telefono');
    document.getElementById('taller_telefono_prefijo').value = code;
    document.getElementById('taller_telefono_hint').textContent = info.hint;
    inp.maxLength = maxlen;
    inp.placeholder = ph;
    inp.value = ''; // limpiar al cambiar país

    const fb = document.getElementById('taller_telefono_feedback');
    fb.className = 'form-text d-none';
    inp.classList.remove('is-valid', 'is-invalid');
}

function validarTelefonoTaller(input) {
    input.value = input.value.replace(/[^0-9]/g, '');

    const pais = document.getElementById('taller_telefono_pais').value;
    const info = _prefijosTaller[pais];
    const fb = document.getElementById('taller_telefono_feedback');
    const val = input.value;
    const maxlen = parseInt(input.maxLength) || 15;

    if (!val) {
        fb.className = 'form-text d-none';
        input.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (!info || !info.pattern) {
        fb.className = 'form-text d-none';
        input.classList.remove('is-valid', 'is-invalid');
        return;
    }

    const completo = val.length === maxlen;
    const valido = info.pattern.test(val);

    if (valido && completo) {
        fb.className = 'form-text text-success';
        fb.textContent = '✓ Número válido';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else if (!valido && completo) {
        fb.className = 'form-text text-danger';
        fb.textContent = '✗ Formato incorrecto — ' + info.hint;
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    } else {
        fb.className = 'form-text d-none';
        input.classList.remove('is-valid', 'is-invalid');
    }
}
</script>
@endsection
