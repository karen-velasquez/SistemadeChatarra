@extends('layouts.app')
@section('titulo','Contratos')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>GESTIÓN DE CONTRATOS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Contratos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('contratos.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalContrato" onclick="resetModalContrato()">
                <i class="bi bi-plus-lg"></i> Nuevo Contrato
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Contratos Registrados</h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Registra y hace seguimiento a los contratos de compra y venta de chatarra celebrados con clientes y proveedores.
                        Cada contrato puede tener camiones asignados para la entrega del material y un documento PDF adjunto como respaldo legal.
                    </p>
                    <div class="table-responsive">
                        <table id="datos" class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>N° Contrato</th>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th>Clientes</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Toneladas</th>
                                    <th>Monto al Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contratos as $c)
                                <tr>
                                    <td><span class="fw-bold text-primary">{{ $c->numero_contrato }}</span></td>
                                    <td>
                                        @if($c->tipo_contrato === 'Nacional')
                                            <span class="badge bg-info text-dark">Nacional</span>
                                        @else
                                            <span class="badge bg-primary">Internacional</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->proveedor->nombre }} <small class="text-muted">({{ $c->proveedor->pais->valor ?? '-' }})</small></td>
                                    <td>
                                        @forelse($c->clientes_entregados as $cli)
                                            <span class="badge bg-light text-dark border">{{ $cli->nombre }}</span>
                                        @empty
                                            <small class="text-muted">—</small>
                                        @endforelse
                                    </td>
                                    <td>{{ $c->fecha_inicio?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $c->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($c->toneladas_contrato)
                                            @php
                                                $total      = (float) $c->toneladas_contrato;
                                                $entregadas = $c->toneladas_entregadas;
                                                $enTransito = $c->toneladas_en_transito;
                                                $pctEnt     = min(100, round(($entregadas / $total) * 100, 1));
                                                $pctTra     = min(100 - $pctEnt, round(($enTransito / $total) * 100, 1));
                                                $tPendiente = max(0, $total - $entregadas - $enTransito);
                                            @endphp
                                            <div class="progress" style="height:16px; min-width:90px;">
                                                @if($pctEnt > 0)
                                                <div class="progress-bar bg-success" style="width:{{ $pctEnt }}%">
                                                    @if($pctEnt >= 15){{ $pctEnt }}%@endif
                                                </div>
                                                @endif
                                                @if($pctTra > 0)
                                                <div class="progress-bar" style="width:{{ $pctTra }}%; background:#38bdf8;">
                                                    @if($pctTra >= 15){{ $pctTra }}%@endif
                                                </div>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                @if($entregadas > 0)
                                                    <span class="text-success fw-semibold">{{ number_format($entregadas,1) }}t</span> /
                                                @endif
                                                <span style="color:#0ea5e9;">{{ number_format($enTransito,1) }}t</span>
                                                @if($tPendiente > 0)
                                                    / <span>{{ number_format($tPendiente,1) }}t pend.</span>
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $c->moneda }} {{ number_format($c->monto_total, 2) }}
                                        @if($c->envios_cerrados)
                                            <br><span class="badge bg-danger mt-1" style="font-size:.65rem">
                                                <i class="bi bi-lock-fill me-1"></i>Envíos cerrados
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                            <ul class="dropdown-menu">
                                                @can('contratos.index')
                                                <li>
                                                    @if($c->envios_cerrados)
                                                        <span class="dropdown-item text-muted">
                                                            <i class="bi bi-lock-fill text-danger"></i> Envíos cerrados
                                                        </span>
                                                    @else
                                                        <a class="dropdown-item" href="{{ route('contratos.camiones', $c->uuid) }}">
                                                            <i class="bi bi-truck"></i> Gestionar Camiones
                                                        </a>
                                                    @endif
                                                </li>
                                                @endcan
                                                @can('contratos.cerrar')
                                                @if(!$c->envios_cerrados)
                                                <li>
                                                    <a class="dropdown-item text-warning" href="{{ route('contratos.cerrar', $c->uuid) }}"
                                                        onclick="return confirm('¿Cerrar envíos del contrato {{ $c->numero_contrato }}? Ya no se podrán agregar más camiones.')">
                                                        <i class="bi bi-lock"></i> Cierre de Envíos
                                                    </a>
                                                </li>
                                                @endif
                                                @endcan
                                                @can('contratos.index')
                                                @if($c->documento_pdf)
                                                <li>
                                                    <a class="dropdown-item text-danger" href="{{ route('contratos.pdf', $c->uuid) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf"></i> Ver PDF
                                                    </a>
                                                </li>
                                                @endif
                                                @endcan
                                                @if($c->envios_cerrados)
                                                    @can('contratos.index')
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="verContrato({{ $c->id }}, '{{ $c->uuid }}')">
                                                            <i class="bi bi-eye"></i> Ver información
                                                        </a>
                                                    </li>
                                                    @endcan
                                                @else
                                                    @can('contratos.edit')
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="editarContrato({{ $c->id }}, '{{ $c->uuid }}')">
                                                            <i class="bi bi-pencil"></i> Modificar
                                                        </a>
                                                    </li>
                                                    @endcan
                                                @endif
                                                @can('contratos.destroy')
                                                <li>
                                                    @if($c->contratoCamiones->count() > 0)
                                                        <span class="dropdown-item text-muted" style="cursor: not-allowed;"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="left"
                                                            title="No se puede eliminar porque tiene {{ $c->contratoCamiones->count() }} camión(es) asignado(s)">
                                                            <i class="bi bi-trash"></i> Eliminar
                                                        </span>
                                                    @else
                                                        <a class="dropdown-item text-danger" href="{{ route('contratos.destroy', $c->uuid) }}"
                                                            onclick="return confirm('¿Eliminar el contrato {{ $c->numero_contrato }}?')">
                                                            <i class="bi bi-trash"></i> Eliminar
                                                        </a>
                                                    @endif
                                                </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== MODAL CONTRATO ===== --}}
<div class="modal fade" id="modalContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> <span id="tituloContrato">Nuevo Contrato</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContrato" method="POST" action="{{ route('contratos.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodContrato" value="POST">
                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                    <div class="row g-3">

                        {{-- Fila 1: N° Contrato | Tipo | Proveedor --}}
                        <div class="col-md-3">
                            <label class="form-label">N° Contrato</label>
                            <input type="text" class="form-control bg-light fw-bold text-primary"
                                id="numero_contrato_display" value="{{ $numeroSiguiente }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tipo de Contrato <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('tipo_contrato') is-invalid @enderror"
                                name="tipo_contrato" id="tipo_contrato" required>
                                <option value="">-- Seleccione --</option>
                                <option value="Nacional">Nacional</option>
                                <option value="Internacional">Internacional</option>
                            </select>
                            @error('tipo_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Proveedor <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('proveedor_id') is-invalid @enderror"
                                name="proveedor_id" id="proveedor_id" required>
                                <option value="">-- Seleccione proveedor --</option>
                                @foreach($proveedores as $pv)
                                    <option value="{{ $pv->id }}">
                                        {{ $pv->nombre }} — {{ $pv->pais->valor ?? '-' }}
                                        @if($pv->nit) (NIT: {{ $pv->nit }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('proveedor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 2: Fecha Inicio | Fecha Fin --}}
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control @error('fecha_inicio') is-invalid @enderror"
                                name="fecha_inicio" id="fecha_inicio" required>
                            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control @error('fecha_fin') is-invalid @enderror"
                                name="fecha_fin" id="fecha_fin">
                            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 3: Total Toneladas | Moneda+Monto --}}
                        <div class="col-md-4">
                            <label class="form-label">Total Toneladas <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.001"
                                class="form-control @error('toneladas_contrato') is-invalid @enderror"
                                name="toneladas_contrato" id="toneladas_contrato"
                                min="0.001" placeholder="Ej: 500.000" required>
                            <small class="text-muted">Toneladas pactadas.</small>
                            @error('toneladas_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Monto Total a Pagar al Proveedor <span class="text-danger">(*)</span></label>
                            <div class="input-group">
                                <select class="form-select flex-grow-0" style="width:90px;"
                                    name="moneda" id="moneda" required>
                                    <option value="BOB" selected>BOB</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="BRL">BRL</option>
                                    <option value="ARS">ARS</option>
                                    <option value="PEN">PEN</option>
                                    <option value="CLP">CLP</option>
                                    <option value="PYG">PYG</option>
                                    <option value="COP">COP</option>
                                </select>
                                <input type="number" step="0.01" class="form-control @error('monto_total') is-invalid @enderror"
                                    name="monto_total" id="monto_total" min="0" required placeholder="0.00">
                            </div>
                            @error('moneda')<div class="text-danger small">{{ $message }}</div>@enderror
                            @error('monto_total')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        {{-- Documento PDF --}}
                        <div class="col-md-12">
                            <label class="form-label">Documento PDF del Contrato</label>
                            <input type="file" class="form-control @error('documento_pdf') is-invalid @enderror"
                                name="documento_pdf" id="documento_pdf" accept=".pdf">
                            <small class="text-muted">Solo archivos PDF. Tamaño máximo: 30 MB.</small>
                            <div id="pdfActualInfo" class="mt-1 d-none">
                                <span class="text-success"><i class="bi bi-file-earmark-pdf"></i> Ya tiene PDF cargado.</span>
                                <small class="text-muted">Si selecciona uno nuevo, reemplazará al actual.</small>
                            </div>
                            @error('documento_pdf')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnContrato">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
    // Reabrir modal si hay errores de validación
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalContrato')).show();
        });
    @endif

    function resetModalContrato() {
        document.getElementById('tituloContrato').innerText  = 'Nuevo Contrato';
        document.getElementById('btnContrato').innerText     = 'Registrar';
        document.getElementById('btnContrato').style.display = '';
        document.getElementById('methodContrato').value      = 'POST';
        document.getElementById('formContrato').action       = '{{ route("contratos.store") }}';
        document.getElementById('numero_contrato_display').value = '{{ $numeroSiguiente }}';
        document.getElementById('formContrato').reset();
        document.getElementById('moneda').value = 'BOB';
        document.getElementById('pdfActualInfo').classList.add('d-none');
        // Quitar disabled de todos los campos por si venían de modo solo-ver
        ['tipo_contrato','proveedor_id','fecha_inicio','fecha_fin','toneladas_contrato','moneda','monto_total','documento_pdf'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('disabled');
        });
    }

    function _cargarContrato(uuid, soloVer) {
        fetch('/contrato/' + uuid + '/edit')
            .then(r => r.json())
            .then(c => {
                const readonly = soloVer;
                document.getElementById('tituloContrato').innerText = soloVer ? 'Información del Contrato' : 'Editar Contrato';
                document.getElementById('btnContrato').style.display = soloVer ? 'none' : '';
                document.getElementById('methodContrato').value      = 'PUT';
                document.getElementById('formContrato').action       = '/contrato/' + c.id;

                const campos = ['tipo_contrato','proveedor_id','fecha_inicio','fecha_fin','toneladas_contrato','moneda','monto_total'];
                campos.forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.value = c[id] ?? '';
                    readonly ? el.setAttribute('disabled', true) : el.removeAttribute('disabled');
                });
                document.getElementById('numero_contrato_display').value = c.numero_contrato;
                document.getElementById('numero_contrato_display').setAttribute('disabled', true);
                document.getElementById('documento_pdf').value = '';
                document.getElementById('documento_pdf').disabled = readonly;
                const pdfInfo = document.getElementById('pdfActualInfo');
                c.documento_pdf ? pdfInfo.classList.remove('d-none') : pdfInfo.classList.add('d-none');

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalContrato')).show();
            });
    }

    function editarContrato(id, uuid) {
        _cargarContrato(uuid, false);
    }

    function verContrato(id, uuid) {
        _cargarContrato(uuid, true);
    }

    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection
