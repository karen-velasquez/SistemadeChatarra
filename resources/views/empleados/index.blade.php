@extends('layouts.app')
@section('titulo', 'Empleados')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>EMPLEADOS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Empleados</li>
                </ol>
            </nav>
        </div>
        @can('empleados.create')
        <div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmpleado" onclick="resetModal()">
                <i class="bi bi-plus-lg"></i> Nuevo Empleado
            </button>
        </div>
        @endcan
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Empleados Registrados</h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Gestión del personal de la empresa. Los empleados pueden tener cuentas bancarias asociadas y ser vinculados a cuentas de usuario del sistema. Los empleados en uso no pueden ser eliminados para mantener la integridad de los datos.
                    </p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-check-fill text-success me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Validación CI</small>
                                    <strong class="small">Solo números (máx 20)</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone-fill text-primary me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Validación Teléfono</small>
                                    <strong class="small">14 países disponibles</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope-check-fill text-info me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Validación Email</small>
                                    <strong class="small">Formato en tiempo real</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-lock-fill text-warning me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Protección</small>
                                    <strong class="small">Datos en uso bloqueados</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($empleados->isEmpty())
                        <div class="alert alert-info py-2">
                            <small><i class="bi bi-info-circle"></i> No hay empleados registrados.</small>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table id="tabla_empleados" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nº</th>
                                    <th>Apellido y Nombre</th>
                                    <th>C.I.</th>
                                    <th>Cargo</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($empleados as $emp)
                                @php
                                    $verificacion = $emp->verificarUso();
                                @endphp
                                <tr class="{{ $emp->activo ? '' : 'table-secondary text-muted' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $emp->apellido_paterno }} {{ $emp->apellido_materno }}</strong>, {{ $emp->nombre }}
                                        @if($verificacion['enUso'])
                                            <span class="badge bg-info text-dark ms-2" title="{{ $verificacion['mensaje'] }}">
                                                <i class="bi bi-link-45deg"></i> En uso
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $emp->ci ?? '—' }}</td>
                                    <td>{{ $emp->cargo->valor ?? '—' }}</td>
                                    <td>{{ $emp->telefono ?? '—' }}</td>
                                    <td>{{ $emp->email ?? '—' }}</td>
                                    <td>
                                        @if($emp->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @can('empleados.edit')
                                            <button class="btn btn-sm btn-outline-secondary"
                                                title="Editar"
                                                onclick="editarEmpleado(
                                                    '{{ $emp->uuid }}',
                                                    '{{ addslashes($emp->nombre) }}',
                                                    '{{ addslashes($emp->apellido_paterno) }}',
                                                    '{{ addslashes($emp->apellido_materno) }}',
                                                    '{{ addslashes($emp->ci ?? '') }}',
                                                    '{{ $emp->cargo_id ?? '' }}',
                                                    '{{ addslashes($emp->telefono ?? '') }}',
                                                    '{{ addslashes($emp->email ?? '') }}'
                                                )">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="{{ route('empleados.toggle', $emp->uuid) }}"
                                                class="btn btn-sm {{ $emp->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $emp->activo ? 'Desactivar' : 'Activar' }}"
                                                onclick="return confirm('¿{{ $emp->activo ? 'Desactivar' : 'Activar' }} a {{ $emp->nombre }} {{ $emp->apellido_paterno }}?')">
                                                <i class="bi {{ $emp->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                            </a>
                                            @endcan
                                            @can('empleados.destroy')
                                            @if($verificacion['enUso'])
                                                <button class="btn btn-sm btn-secondary" disabled
                                                    title="{{ $verificacion['mensaje'] }}"
                                                    style="opacity: 0.5; cursor: not-allowed;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @else
                                                <a href="{{ route('empleados.destroy', $emp->uuid) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar"
                                                    onclick="return confirm('¿Eliminar a {{ $emp->nombre }} {{ $emp->apellido_paterno }}? Esta acción no se puede deshacer.')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            @endif
                                            @endcan
                                        </div>
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

{{-- ===== MODAL EMPLEADO ===== --}}
<div class="modal fade" id="modalEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge"></i> <span id="tituloEmpleado">Nuevo Empleado</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEmpleado" method="POST" action="{{ route('empleados.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodEmpleado" value="POST">
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="nombre" id="emp_nombre" required maxlength="100"
                                placeholder="Ej: MARÍA" style="text-transform:uppercase"
                                oninput="this.value=this.value.toUpperCase(); validarFormularioEmpleado()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="apellido_paterno" id="emp_apellido_paterno" required maxlength="100"
                                placeholder="Ej: FLORES" style="text-transform:uppercase"
                                oninput="this.value=this.value.toUpperCase(); validarFormularioEmpleado()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Materno <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="apellido_materno" id="emp_apellido_materno" required maxlength="100"
                                placeholder="Ej: GUTIÉRREZ" style="text-transform:uppercase"
                                oninput="this.value=this.value.toUpperCase(); validarFormularioEmpleado()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">C.I. / Documento <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="ci" id="emp_ci"
                                maxlength="20"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                placeholder="Ej: 12345678"
                                required
                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); validarFormularioEmpleado()">
                            <div class="form-text small">Solo números, máximo 20 dígitos</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Cargo <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="cargo_id" id="emp_cargo_id" required onchange="validarFormularioEmpleado()">
                                <option value="">-- Seleccione un cargo --</option>
                                @foreach($cargos as $cargo)
                                    <option value="{{ $cargo->id }}">{{ $cargo->valor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <div class="input-group">
                                <select id="emp_telefono_pais" class="form-select flex-grow-0" style="width:115px; min-width:115px; max-width:115px;"
                                    onchange="actualizarPrefijoTelefonoEmp()">
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
                                <input type="hidden" name="telefono_prefijo" id="emp_telefono_prefijo" value="+591">
                                <input type="text" class="form-control" name="telefono" id="emp_telefono"
                                    maxlength="8" placeholder="Ej: 70000000"
                                    oninput="validarTelefonoEmp(this)">
                            </div>
                            <div id="emp_telefono_feedback" class="form-text d-none"></div>
                            <small id="emp_telefono_hint" class="text-muted">Bolivia: 8 dígitos comenzando en 6 o 7</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="email" id="emp_email" maxlength="150"
                                placeholder="ejemplo@correo.com"
                                oninput="validarEmail(this)">
                            <div id="email-feedback" class="form-text small"></div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEmpleado" disabled>
                        <i class="bi bi-save"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#tabla_empleados').DataTable({
        language: {
            processing:  "Procesando...",
            lengthMenu:  'Mostrar <select><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="-1">Todos</option></select> registros',
            paginate:    { sFirst: "Primero", sLast: "Último", previous: "Anterior", next: "Siguiente" },
            info:        "Página _PAGE_ de _PAGES_ — _TOTAL_ empleados",
            search:      "Buscar:",
            emptyTable:  "No hay empleados registrados.",
            infoEmpty:   "",
        },
        orderCellsTop: true,
        ordering:      true,
        order:         [[1, 'asc']], // Ordenar por apellido y nombre
        pageLength:    10,
        lengthMenu:    [10, 25, 50, -1],
        columnDefs:    [
            { orderable: false, targets: 0 }, // Nº no ordenable
            { orderable: false, targets: -1 } // Acciones no ordenables
        ],
    });
});

function resetModal() {
    document.getElementById('tituloEmpleado').textContent      = 'Nuevo Empleado';
    document.getElementById('btnEmpleado').innerHTML           = '<i class="bi bi-save"></i> Registrar';
    document.getElementById('methodEmpleado').value            = 'POST';
    document.getElementById('formEmpleado').action             = '{{ route("empleados.store") }}';
    document.getElementById('emp_nombre').value                = '';
    document.getElementById('emp_apellido_paterno').value      = '';
    document.getElementById('emp_apellido_materno').value      = '';
    document.getElementById('emp_ci').value                    = '';
    document.getElementById('emp_cargo_id').value              = '';
    document.getElementById('emp_telefono').value              = '';
    document.getElementById('emp_email').value                 = '';

    // Reset selector de país y feedback de teléfono
    document.getElementById('emp_telefono_pais').value = 'Bolivia';
    actualizarPrefijoTelefonoEmp();
    const telFb = document.getElementById('emp_telefono_feedback');
    telFb.className = 'form-text d-none';
    document.getElementById('emp_telefono').classList.remove('is-valid', 'is-invalid');

    // Reset feedback de email
    const emailFb = document.getElementById('email-feedback');
    emailFb.textContent = '';
    emailFb.className = 'form-text small';
    document.getElementById('emp_email').classList.remove('is-valid', 'is-invalid');

    // Deshabilitar botón
    document.getElementById('btnEmpleado').disabled = true;
}

function validarFormularioEmpleado() {
    const nombre = document.getElementById('emp_nombre').value.trim();
    const apellidoP = document.getElementById('emp_apellido_paterno').value.trim();
    const apellidoM = document.getElementById('emp_apellido_materno').value.trim();
    const ci = document.getElementById('emp_ci').value.trim();
    const cargo = document.getElementById('emp_cargo_id').value;
    const btnEmpleado = document.getElementById('btnEmpleado');

    // Todos los campos obligatorios deben estar llenos
    const formularioValido = nombre !== '' &&
                            apellidoP !== '' &&
                            apellidoM !== '' &&
                            ci !== '' &&
                            cargo !== '';

    btnEmpleado.disabled = !formularioValido;
}

function editarEmpleado(uuid, nombre, apellidoPaterno, apellidoMaterno, ci, cargoId, telefono, email) {
    document.getElementById('tituloEmpleado').textContent      = 'Editar Empleado';
    document.getElementById('btnEmpleado').innerHTML           = '<i class="bi bi-save"></i> Actualizar';
    document.getElementById('methodEmpleado').value            = 'PUT';
    document.getElementById('formEmpleado').action             = '/empleados/' + uuid;
    document.getElementById('emp_nombre').value                = nombre;
    document.getElementById('emp_apellido_paterno').value      = apellidoPaterno;
    document.getElementById('emp_apellido_materno').value      = apellidoMaterno;
    document.getElementById('emp_ci').value                    = ci;
    document.getElementById('emp_cargo_id').value              = cargoId || '';
    document.getElementById('emp_telefono').value              = telefono;
    document.getElementById('emp_email').value                 = email;
    // Validar email al cargar en modo edición
    if (email) {
        validarEmail(document.getElementById('emp_email'));
    }
    // Validar formulario completo para habilitar/deshabilitar botón
    validarFormularioEmpleado();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEmpleado')).show();
}

function validarEmail(input) {
    const feedback = document.getElementById('email-feedback');
    const email = input.value.trim();

    // Si está vacío, limpiar feedback (campo opcional)
    if (email === '') {
        feedback.textContent = '';
        feedback.className = 'form-text small';
        input.classList.remove('is-invalid', 'is-valid');
        return;
    }

    // Patrón de validación de email
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (emailPattern.test(email)) {
        // Email válido
        feedback.textContent = '✓ Formato válido';
        feedback.className = 'form-text small text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else {
        // Email inválido
        let mensaje = '✗ ';

        if (!email.includes('@')) {
            mensaje += 'Falta el símbolo @';
        } else if (!email.includes('.') || email.lastIndexOf('.') < email.indexOf('@')) {
            mensaje += 'Formato incorrecto (debe ser: usuario@dominio.com)';
        } else if (email.indexOf('@') === 0) {
            mensaje += 'Debe incluir un nombre de usuario antes del @';
        } else if (email.endsWith('@') || email.endsWith('.')) {
            mensaje += 'Email incompleto';
        } else {
            mensaje += 'Formato de correo inválido';
        }

        feedback.textContent = mensaje;
        feedback.className = 'form-text small text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    }
}

// Validación de teléfono - Prefijos por país
const _prefijosEmp = {
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

function actualizarPrefijoTelefonoEmp() {
    const sel = document.getElementById('emp_telefono_pais');
    const opt = sel.options[sel.selectedIndex];
    const code = opt ? (opt.dataset.code || '+') : '+';
    const pais = opt ? opt.value : '';
    const maxlen = opt && opt.dataset.maxlen ? parseInt(opt.dataset.maxlen) : 15;
    const ph = opt && opt.dataset.placeholder ? opt.dataset.placeholder : 'Número';
    const info = _prefijosEmp[pais] || { hint: 'Ingrese el número sin código de país' };

    const inp = document.getElementById('emp_telefono');
    document.getElementById('emp_telefono_prefijo').value = code;
    document.getElementById('emp_telefono_hint').textContent = info.hint;
    inp.maxLength = maxlen;
    inp.placeholder = ph;
    inp.value = ''; // limpiar al cambiar país

    // limpiar feedback al cambiar país
    const fb = document.getElementById('emp_telefono_feedback');
    fb.className = 'form-text d-none';
    inp.classList.remove('is-valid', 'is-invalid');
}

function validarTelefonoEmp(input) {
    // solo dígitos
    input.value = input.value.replace(/[^0-9]/g, '');

    const pais = document.getElementById('emp_telefono_pais').value;
    const info = _prefijosEmp[pais];
    const fb = document.getElementById('emp_telefono_feedback');
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
        // longitud correcta Y patrón OK → verde
        fb.className = 'form-text text-success';
        fb.textContent = '✓ Número válido';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else if (!valido && completo) {
        // longitud completa pero patrón falla → rojo
        fb.className = 'form-text text-danger';
        fb.textContent = '✗ Formato incorrecto — ' + info.hint;
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    } else {
        // aún escribiendo
        fb.className = 'form-text d-none';
        input.classList.remove('is-valid', 'is-invalid');
    }
}
</script>
@endsection
