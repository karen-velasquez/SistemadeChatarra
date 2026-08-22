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
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"📄 Bienvenido al módulo <b>Contratos</b>. Aquí registras y haces seguimiento a las compras y ventas de chatarra. Te muestro cómo está organizado."},
                        {"element":"#datos","intro":"📑 Esta es la lista de todos los contratos registrados. Cada fila es un contrato con su proveedor, clientes, fechas y montos.","position":"top"},
                        {"element":"#datos thead th:nth-child(2)","intro":"🏷️ La columna <b>Tipo</b> indica si el contrato es <b>Nacional</b> (dentro del país) o <b>Internacional</b>.","position":"bottom"},
                        {"element":"#datos thead th:nth-child(7)","intro":"⚖️ En <b>Toneladas</b> verás una barra de avance: en verde lo ya entregado y en celeste lo que está en tránsito.","position":"bottom"},
                        {"element":"#datos thead th:nth-child(8)","intro":"💲 El <b>Monto al Proveedor</b> es lo que se le pagará. Si dice <i>Envíos cerrados</i>, ese contrato ya no admite cambios.","position":"bottom"},
                        {"element":"#datos tbody tr:first-child .btn-group","intro":"⚙️ Con el botón <b>Opciones</b> de cada fila gestionas sus camiones, ves el detalle, editas o eliminas el contrato.","position":"left"},
                        {"element":"#btnNuevoContrato","intro":"➕ Para crear un contrato nuevo, usa este botón <b>Nuevo Contrato</b>. ¡Eso es todo!","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            <button type="button" id="btnDescargarExcelContratos" class="btn btn-outline-success btn-sm" onclick="descargarExcelContratos()">
                <i class="bi bi-file-earmark-excel"></i> Descargar Excel
            </button>
            @can('contratos.create')
            <button type="button" id="btnNuevoContrato" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalContrato" onclick="resetModalContrato()">
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

                    {{-- ===== FILTROS ===== --}}
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1"><i class="bi bi-tag"></i> Tipo</label>
                            <select class="form-select" id="filtro_tipo" onchange="aplicarFiltrosContratos()">
                                <option value="">— Todos —</option>
                                <option value="Nacional">Nacional</option>
                                <option value="Internacional">Internacional</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1"><i class="bi bi-box-seam"></i> Proveedor</label>
                            <select class="form-select" id="filtro_proveedor_contrato" onchange="aplicarFiltrosContratos()">
                                <option value="">— Todos —</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1"><i class="bi bi-people"></i> Cliente</label>
                            <select class="form-select" id="filtro_cliente_contrato" onchange="aplicarFiltrosContratos()">
                                <option value="">— Todos —</option>
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}">{{ $cli->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosContratos()">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                        <div class="col-auto ms-auto">
                            <small class="text-muted">
                                Mostrando <span id="lbl_count_contratos">{{ $contratos->count() }}</span> contrato(s)
                                — generado el {{ now()->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datos" class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th style="white-space:nowrap; width:1%;">N° Contrato</th>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th style="max-width:180px;">Clientes</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Toneladas</th>
                                    <th>Monto al Proveedor</th>
                                    <th>Fecha Registro</th>
                                    <th>Registrado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contratos as $c)
                                @php $clientesEntregados = $c->clientes_entregados; @endphp
                                <tr data-tipo="{{ $c->tipo_contrato }}" data-proveedor-id="{{ $c->proveedor_id }}" data-clientes-ids="{{ $clientesEntregados->pluck('id')->implode(',') }}" data-numero-contrato="{{ $c->numero_contrato }}">
                                    <td style="white-space:nowrap;"><span class="fw-bold text-primary">{{ $c->numero_contrato }}</span></td>
                                    <td>
                                        @if($c->tipo_contrato === 'Nacional')
                                            <span class="badge bg-info text-dark">Nacional</span>
                                        @else
                                            <span class="badge bg-primary">Internacional</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->proveedor->nombre }} <small class="text-muted">({{ $c->proveedor->pais->valor ?? '-' }})</small></td>
                                    <td style="max-width:180px;">
                                        @forelse($clientesEntregados as $cli)
                                            <span class="badge bg-light text-dark border d-inline-block text-truncate"
                                                  style="max-width:160px; vertical-align:middle;"
                                                  title="{{ $cli->nombre }}"
                                                  data-bs-toggle="tooltip">{{ $cli->nombre }}</span>
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
                                                    <span class="text-success fw-semibold">{{ number_format($entregadas,2,',','.') }}t</span> /
                                                @endif
                                                <span style="color:#0ea5e9;">{{ number_format($enTransito,2,',','.') }}t</span>
                                                @if($tPendiente > 0)
                                                    / <span>{{ number_format($tPendiente,2,',','.') }}t pend.</span>
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $c->moneda }} {{ number_format($c->monto_total, 2, ',', '.') }}
                                        @if($c->envios_cerrados)
                                            <br><span class="badge bg-danger mt-1" style="font-size:.65rem">
                                                <i class="bi bi-lock-fill me-1"></i>Envíos cerrados
                                            </span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">{{ $c->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>{{ $c->usuarioCreador->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                            <ul class="dropdown-menu">
                                                @can('contratos.index')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('contratos.camiones', $c->uuid) }}">
                                                        <i class="bi bi-truck"></i>
                                                        {{ $c->envios_cerrados ? 'Ver Camiones' : 'Gestionar Camiones' }}
                                                    </a>
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
                                                @else
                                                <li>
                                                    <a class="dropdown-item text-success" href="{{ route('contratos.descerrar', $c->uuid) }}"
                                                        onclick="return confirm('¿Reabrir los envíos del contrato {{ $c->numero_contrato }}? Volverá a estar editable y saldrá de la liquidación de envíos.')">
                                                        <i class="bi bi-unlock"></i> Reabrir Envíos
                                                    </a>
                                                </li>
                                                @endif
                                                @endcan
                                                @can('contratos.index')
                                                @if($c->documento_pdf)
                                                <li>
                                                    <a class="dropdown-item text-danger" href="{{ route('contratos.pdf', $c->uuid) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-text"></i> Ver documento
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
                                                    <a class="dropdown-item text-danger" href="{{ route('contratos.destroy', $c->uuid) }}"
                                                        onclick="return confirm('¿Eliminar el contrato {{ $c->numero_contrato }}?\n\nEsta acción eliminará también todos los camiones asignados, tramos y pagos asociados. Esta acción no se puede deshacer.')">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </a>
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
            <div class="modal-header position-relative">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> <span id="tituloContrato">Nuevo Contrato</span></h5>
                <div class="d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-2 me-3">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalContrato"
                            data-steps='[
                                {"intro":"📝 Este es el formulario para registrar un <b>contrato</b>. Te explico qué va en cada campo. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#numero_contrato_display","intro":"🔢 El <b>N° de Contrato</b> se genera <b>automáticamente</b>. No necesitas escribirlo.","position":"bottom"},
                                {"element":"#tipo_contrato","intro":"🌎 Elige el <b>Tipo</b>: <b>Nacional</b> (dentro del país) o <b>Internacional</b>.","position":"bottom"},
                                {"element":"#proveedor_id","intro":"📦 Selecciona el <b>Proveedor</b> al que le compras la chatarra. La lista viene de tus proveedores registrados.","position":"bottom"},
                                {"element":"#fecha_inicio","intro":"📅 <b>Fecha de Inicio</b> del contrato. Es obligatoria.","position":"bottom"},
                                {"element":"#fecha_fin","intro":"📅 <b>Fecha de Fin</b> (opcional): hasta cuándo rige el contrato.","position":"bottom"},
                                {"element":"#toneladas_contrato","intro":"⚖️ <b>Total de Toneladas</b> pactadas en el contrato. Acepta decimales (ej: 500.000).","position":"top"},
                                {"element":"#monto_total","intro":"💲 <b>Monto total a pagar</b> al proveedor. A la izquierda eliges la <b>moneda</b> (BOB, USD, etc.).","position":"top"},
                                {"element":"#costo_unitario_display","intro":"🧮 <b>Costo Unitario</b>: se calcula automáticamente al ingresar toneladas y monto. También puedes ingresarlo directamente — si tienes toneladas, el monto se calculará solo.","position":"top"},
                                {"element":"#documento_pdf","intro":"📎 Adjunta el <b>documento del contrato</b> firmado (PDF o imagen) como respaldo (opcional, máx. 30 MB).","position":"top"},
                                {"element":"#btnContrato","intro":"💾 Cuando todo esté listo, pulsa <b>Registrar</b> para guardar el contrato.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formContrato" method="POST" action="{{ route('contratos.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodContrato" value="POST">
                <input type="hidden" name="numero_contrato" id="numero_contrato" value="{{ $numeroSiguiente }}">
                <input type="hidden" name="_idempotency_token" id="idempotencyToken" value="{{ $idempotencyToken }}">
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

                        {{-- Fila 3: Toneladas | Costo Unitario | Moneda+Monto Total --}}
                        <div class="col-md-4">
                            <label class="form-label">Total Toneladas <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric"
                                class="form-control @error('toneladas_contrato') is-invalid @enderror"
                                id="toneladas_contrato_display" placeholder="0,00" autocomplete="off" required>
                            <input type="hidden" name="toneladas_contrato" id="toneladas_contrato">
                            <small class="text-muted">Toneladas pactadas en el contrato.</small>
                            @error('toneladas_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Costo Unitario por Tonelada <span class="text-danger">(*)</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="costoUnitarioMonedaLabel">BOB</span>
                                <input type="text" inputmode="numeric" class="form-control @error('costo_unitario') is-invalid @enderror"
                                    id="costo_unitario_display" placeholder="0,00" autocomplete="off" required>
                                <input type="hidden" name="costo_unitario" id="costo_unitario">
                                <span class="input-group-text">/t</span>
                            </div>
                            <small class="text-muted">Se calcula solo o ingréselo para calcular el monto.</small>
                            @error('costo_unitario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
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
                                <input type="text" inputmode="numeric" class="form-control @error('monto_total') is-invalid @enderror"
                                    id="monto_total_display" placeholder="0,00" autocomplete="off">
                                <input type="hidden" name="monto_total" id="monto_total">
                            </div>
                            @error('moneda')<div class="text-danger small">{{ $message }}</div>@enderror
                            @error('monto_total')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        {{-- Documento del contrato (PDF o imagen) --}}
                        <div class="col-md-12">
                            <label class="form-label">Documento del Contrato</label>
                            <input type="file" class="form-control @error('documento_pdf') is-invalid @enderror"
                                name="documento_pdf" id="documento_pdf" accept=".pdf,.png,.jpg,.jpeg">
                            <small class="text-muted">Archivos PDF, PNG, JPG o JPEG. Tamaño máximo: 30 MB.</small>
                            <div id="pdfActualInfo" class="mt-1 d-none">
                                <span class="text-success"><i class="bi bi-file-earmark-check"></i> Ya tiene un documento cargado.</span>
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
            <script>
                document.getElementById('formContrato').addEventListener('submit', function(e) {
                    const btn = document.getElementById('btnContrato');
                    if (btn.disabled) { e.preventDefault(); return; }
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
                });
            </script>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
    // ── Descargar lista de contratos en Excel (valores numéricos limpios para fórmulas) ──
    const _contratosExcelData = @json($contratosExcelData);

    function descargarExcelContratos() {
        // Solo los contratos visibles según los filtros activos (Tipo/Proveedor/Cliente),
        // igual que se ven en pantalla — no todos los contratos registrados.
        const numerosVisibles = new Set(
            tablaContratos.rows({ search: 'applied' }).nodes().toArray()
                .map(tr => tr.dataset.numeroContrato)
        );
        const visibles = _contratosExcelData.filter(c => numerosVisibles.has(c.numero_contrato));

        const cols = ['N° CONTRATO','TIPO','PROVEEDOR','PLACA','CLIENTE','MONEDA','TN ENTREGADAS','PRECIO DE VENTA','TOTAL VENTAS','IMPORTE COMPRA','UTILIDAD BRUTA','IT 3%','COMISIÓN 1 (3%)','COMISIÓN 2 ZPL (1,1%)','UTILIDAD NETA','ESTADO ENVÍOS'];

        // En la fila SUBTOTAL, N° CONTRATO/TIPO/PROVEEDOR se reemplazan por el
        // texto "SUBTOTAL {número}" en la primera columna y se agrega el estado
        // de envíos al final. Una fila en blanco después separa visualmente el
        // subtotal del siguiente contrato.
        const dataRows = [];
        const rowStyles = [];
        visibles.forEach(c => {
            if (c.es_subtotal) {
                dataRows.push([
                    c.cliente, '', '', c.placa, '', c.moneda,
                    c.tn_entregadas, c.precio_venta, c.total_ventas, c.importe_compra, c.utilidad_bruta,
                    c.it_3, c.comision_1_3, c.comision_2_zpl, c.utilidad_neta, c.estado_envios,
                ]);
                rowStyles.push('subtotal');
                dataRows.push(cols.map(() => ''));
                rowStyles.push(null);
            } else {
                dataRows.push([
                    c.numero_contrato, c.tipo_contrato, c.proveedor, c.placa, c.cliente, c.moneda,
                    c.tn_entregadas, c.precio_venta, c.total_ventas, c.importe_compra, c.utilidad_bruta,
                    c.it_3, c.comision_1_3, c.comision_2_zpl, c.utilidad_neta, '',
                ]);
                rowStyles.push(null);
            }
        });

        const tituloLineas = [
            'REGISTRO DE CONTRATOS',
            'Descargado por: {{ addslashes(auth()->user()->name ?? '') }}',
            'Descargado el: {{ now()->format('d/m/Y H:i') }}',
        ];
        _exportarXlsx(cols, dataRows, 'Contratos', `contratos_{{ date('Ymd_His') }}.xlsx`, tituloLineas, rowStyles);
    }

    // ---- Generador XLSX con cabecera estilizada (fondo verde oscuro + texto blanco + negrita) ----
    // tituloLineas (opcional): filas de texto libre antes de la cabecera de columnas
    // (ej. título del reporte, quién y cuándo lo descargó).
    function _exportarXlsx(headers, rows, sheetName, filename, tituloLineas, rowStyles) {
        tituloLineas = tituloLineas || [];
        rowStyles = rowStyles || [];
        const esc = v => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

        const styleXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A6B2F"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF3B0"/></patternFill></fill>
  </fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
  </cellXfs>
</styleSheet>`;

        const colLetter = i => { let s='', n=i+1; while(n>0){s=String.fromCharCode(65+(n-1)%26)+s;n=Math.floor((n-1)/26);} return s; };

        const colWidths = headers.map(h => Math.max(14, h.length + 4));
        let colsXml = '<cols>';
        colWidths.forEach((w, ci) => { colsXml += `<col min="${ci+1}" max="${ci+1}" width="${w}" customWidth="1"/>`; });
        colsXml += '</cols>';

        let sheetData = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  ${colsXml}
  <sheetData>`;

        const ultimaCol = colLetter(headers.length - 1);
        const mergesXml = [];

        let filaActual = 1;
        tituloLineas.forEach((linea, li) => {
            // Solo la primera línea (el título del reporte) lleva estilo grande y
            // combina sus celdas a lo ancho de la tabla; las siguientes van planas.
            const esTitulo = li === 0;
            if (esTitulo) {
                // El fondo/estilo se aplica a TODAS las celdas del rango combinado
                // (no solo a la "ancla" A), para que el verde cubra toda la fila.
                sheetData += `<row r="${filaActual}">`;
                sheetData += `<c r="A${filaActual}" t="inlineStr" s="2"><is><t>${esc(linea)}</t></is></c>`;
                for (let ci = 1; ci < headers.length; ci++) {
                    sheetData += `<c r="${colLetter(ci)}${filaActual}" s="2"/>`;
                }
                sheetData += `</row>`;
                if (headers.length > 1) {
                    mergesXml.push(`<mergeCell ref="A${filaActual}:${ultimaCol}${filaActual}"/>`);
                }
            } else {
                sheetData += `<row r="${filaActual}"><c r="A${filaActual}" t="inlineStr"><is><t>${esc(linea)}</t></is></c></row>`;
            }
            filaActual++;
        });

        const filaCabecera = filaActual;
        sheetData += `<row r="${filaCabecera}">`;
        headers.forEach((h, ci) => {
            sheetData += `<c r="${colLetter(ci)}${filaCabecera}" t="inlineStr" s="1"><is><t>${esc(h)}</t></is></c>`;
        });
        sheetData += `</row>`;
        filaActual++;

        rows.forEach((row, ri) => {
            const s = rowStyles[ri] === 'subtotal' ? ' s="3"' : '';
            sheetData += `<row r="${filaActual}">`;
            row.forEach((val, ci) => {
                if (typeof val === 'number') {
                    sheetData += `<c r="${colLetter(ci)}${filaActual}"${s}><v>${val}</v></c>`;
                } else {
                    sheetData += `<c r="${colLetter(ci)}${filaActual}"${s} t="inlineStr"><is><t>${esc(val)}</t></is></c>`;
                }
            });
            sheetData += `</row>`;
            filaActual++;
        });
        sheetData += `</sheetData>`;
        if (mergesXml.length) {
            sheetData += `<mergeCells count="${mergesXml.length}">${mergesXml.join('')}</mergeCells>`;
        }
        sheetData += `</worksheet>`;

        const wb = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="${esc(sheetName)}" sheetId="1" r:id="rId1"/></sheets>
</workbook>`;

        const rels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`;

        const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>`;

        const rootRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>`;

        const zip = new JSZip();
        zip.file('[Content_Types].xml', contentTypes);
        zip.folder('_rels').file('.rels', rootRels);
        const xl = zip.folder('xl');
        xl.file('workbook.xml', wb);
        xl.file('styles.xml', styleXml);
        xl.folder('_rels').file('workbook.xml.rels', rels);
        xl.folder('worksheets').file('sheet1.xml', sheetData);

        zip.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
           .then(blob => {
               const url = URL.createObjectURL(blob);
               const a = document.createElement('a');
               a.href = url; a.download = filename; a.click();
               URL.revokeObjectURL(url);
           });
    }
</script>
<script>
    // ── Buscador en el select de Proveedor del filtro de la tabla ──
    $('#filtro_proveedor_contrato').select2({
        placeholder: '— Todos —',
        allowClear: true,
        width: '100%',
        language: {
            noResults: () => 'No se encontró ningún proveedor.',
            searching: () => 'Buscando...'
        }
    });

    // ── Buscador en el select de Proveedor del modal Nuevo/Editar Contrato ──
    // Se inicializa al abrir el modal porque Select2 necesita el elemento visible,
    // y con dropdownParent para que el desplegable no quede detrás del modal.
    $('#modalContrato').on('shown.bs.modal', function () {
        if (!$('#proveedor_id').data('select2')) {
            $('#proveedor_id').select2({
                placeholder: '-- Seleccione proveedor --',
                width: '100%',
                dropdownParent: $('#modalContrato'),
                language: {
                    noResults: () => 'No se encontró ningún proveedor.',
                    searching: () => 'Buscando...'
                }
            });
        }
        // El valor y el estado disabled se fijan por JS antes de abrir el modal:
        // hay que avisarle a Select2 para que refleje ambos.
        $('#proveedor_id').trigger('change.select2');
    });
</script>
<script>
    // Reabrir modal si hay errores de validación
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalContrato')).show();
        });
    @endif

    // ===== Filtros de la tabla (integrados con la paginación de DataTables) =====
    const tablaContratos = $('#datos').DataTable();

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'datos') return true;
        const fila         = tablaContratos.row(dataIndex).node();
        const tipo         = document.getElementById('filtro_tipo').value;
        const proveedorId  = document.getElementById('filtro_proveedor_contrato').value;
        const clienteId    = document.getElementById('filtro_cliente_contrato').value;

        const okTipo      = !tipo || fila.dataset.tipo === tipo;
        const okProveedor = !proveedorId || fila.dataset.proveedorId === proveedorId;
        const clientesIds = (fila.dataset.clientesIds || '').split(',');
        const okCliente   = !clienteId || clientesIds.includes(clienteId);

        return okTipo && okProveedor && okCliente;
    });

    tablaContratos.on('draw', function () {
        document.getElementById('lbl_count_contratos').textContent = tablaContratos.rows({ search: 'applied' }).count();
    });

    function aplicarFiltrosContratos() {
        tablaContratos.draw();
    }

    function limpiarFiltrosContratos() {
        document.getElementById('filtro_tipo').value               = '';
        document.getElementById('filtro_proveedor_contrato').value = '';
        document.getElementById('filtro_cliente_contrato').value   = '';
        aplicarFiltrosContratos();
    }

    function resetModalContrato() {
        document.getElementById('tituloContrato').innerText  = 'Nuevo Contrato';
        const btn = document.getElementById('btnContrato');
        btn.innerText     = 'Registrar';
        btn.style.display = '';
        btn.disabled      = true;
        document.getElementById('methodContrato').value      = 'POST';
        document.getElementById('formContrato').action       = '{{ route("contratos.store") }}';
        document.getElementById('numero_contrato_display').value = '{{ $numeroSiguiente }}';
        document.getElementById('numero_contrato').value         = '{{ $numeroSiguiente }}';
        fetch('{{ route("contratos.nuevo-token") }}')
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(d => {
                document.getElementById('idempotencyToken').value = d.token;
                document.getElementById('btnContrato').disabled = false;
            })
            .catch(() => { document.getElementById('btnContrato').disabled = false; });
        document.getElementById('formContrato').reset();
        document.getElementById('moneda').value = 'BOB';
        document.getElementById('monto_total_display').value = '';
        document.getElementById('monto_total').value = '';
        document.getElementById('toneladas_contrato_display').value = '';
        document.getElementById('toneladas_contrato').value = '';
        document.getElementById('pdfActualInfo').classList.add('d-none');
        document.getElementById('costo_unitario_display').value = '';
        document.getElementById('costo_unitario').value = '';
        const lbl = document.getElementById('costoUnitarioMonedaLabel');
        if (lbl) lbl.textContent = 'BOB';
        // Quitar disabled de todos los campos por si venían de modo solo-ver
        ['tipo_contrato','proveedor_id','fecha_inicio','fecha_fin','toneladas_contrato_display','moneda','monto_total_display','costo_unitario_display','documento_pdf'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('disabled');
        });
    }

    function _cargarContrato(uuid, soloVer) {
        fetch(url_global + '/contrato/' + uuid + '/edit')
            .then(r => r.json())
            .then(c => {
                const readonly = soloVer;
                document.getElementById('tituloContrato').innerText = soloVer ? 'Información del Contrato' : 'Editar Contrato';
                const btn = document.getElementById('btnContrato');
                btn.style.display = soloVer ? 'none' : '';
                btn.disabled      = false;
                btn.innerText     = 'Guardar';
                document.getElementById('methodContrato').value      = 'PUT';
                document.getElementById('formContrato').action       = url_global + '/contrato/' + c.id;
                document.getElementById('numero_contrato').value     = c.numero_contrato ?? '';
                document.getElementById('idempotencyToken').value    = '';

                const campos = ['tipo_contrato','proveedor_id','fecha_inicio','fecha_fin','moneda'];
                const camposFecha = ['fecha_inicio','fecha_fin'];
                campos.forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    let valor = c[id] ?? '';
                    // Un input type="date" solo acepta "YYYY-MM-DD". El backend
                    // devuelve la fecha como ISO ("2026-06-08T00:00:00...."),
                    // así que tomamos solo los primeros 10 caracteres.
                    if (camposFecha.includes(id) && valor) {
                        valor = String(valor).substring(0, 10);
                    }
                    el.value = valor;
                    readonly ? el.setAttribute('disabled', true) : el.removeAttribute('disabled');
                });
                // Cargar toneladas con formato visual
                toneladasCargar(c.toneladas_contrato ?? 0);
                const tonDisplay = document.getElementById('toneladas_contrato_display');
                readonly ? tonDisplay.setAttribute('disabled', true) : tonDisplay.removeAttribute('disabled');
                // Cargar monto con formato visual
                montoCargar(c.monto_total ?? 0);
                const displayEl = document.getElementById('monto_total_display');
                readonly ? displayEl.setAttribute('disabled', true) : displayEl.removeAttribute('disabled');
                document.getElementById('numero_contrato_display').value = c.numero_contrato;
                document.getElementById('numero_contrato_display').setAttribute('disabled', true);
                document.getElementById('documento_pdf').value = '';
                document.getElementById('documento_pdf').disabled = readonly;
                const pdfInfo = document.getElementById('pdfActualInfo');
                c.documento_pdf ? pdfInfo.classList.remove('d-none') : pdfInfo.classList.add('d-none');

                // Cargar costo unitario guardado en BD
                const monedaLbl = document.getElementById('costoUnitarioMonedaLabel');
                if (monedaLbl) monedaLbl.textContent = c.moneda ?? 'BOB';
                costoUnitarioCargar(c.costo_unitario ?? 0);
                const costoDisp = document.getElementById('costo_unitario_display');
                if (costoDisp) readonly ? costoDisp.setAttribute('disabled', true) : costoDisp.removeAttribute('disabled');
                document.getElementById('costo_unitario').disabled = readonly;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalContrato')).show();
            });
    }

    function editarContrato(id, uuid) {
        _cargarContrato(uuid, false);
    }

    function verContrato(id, uuid) {
        _cargarContrato(uuid, true);
    }

    // ===== Utilidades formato cajero (boliviano: 1.234,56) =====
    function _parse(txt) {
        return parseFloat((txt || '').replace(/\./g, '').replace(',', '.')) || 0;
    }

    function _fmt(valor, decimales) {
        if (!valor && valor !== 0) return '';
        return new Intl.NumberFormat('es-BO', {
            minimumFractionDigits: decimales ?? 2,
            maximumFractionDigits: decimales ?? 2
        }).format(valor);
    }

    // Aplica formato cajero a un input display y escribe el valor numérico en el hidden.
    // maxDec: máximo de decimales permitidos (2 para monto/costo, 3 para toneladas)
    function _cajeroOnInput(e, hiddenId, maxDec, onChangeCb) {
        const input = e.target;
        let raw = input.value.replace(/[^0-9,]/g, '');
        const partes = raw.split(',');
        if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
        const [ent, dec] = raw.split(',');
        if (dec !== undefined && dec.length > maxDec) raw = ent + ',' + dec.substring(0, maxDec);
        const [e2, d2] = raw.split(',');
        const entF  = (e2 || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const nuevo = d2 !== undefined ? entF + ',' + d2 : entF;
        const diff  = nuevo.length - input.value.length;
        const pos   = input.selectionStart + diff;
        input.value = nuevo;
        try { input.setSelectionRange(pos, pos); } catch (_) {}
        const hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = _parse(nuevo) || '';
        if (typeof onChangeCb === 'function') onChangeCb();
    }

    function _cajeroBlur(displayId, hiddenId, decimales) {
        const num = parseFloat(document.getElementById(hiddenId)?.value) || 0;
        if (num) document.getElementById(displayId).value = _fmt(num, decimales);
    }

    // Carga un valor numérico en el par display/hidden con formato correcto
    function _cargarCampo(displayId, hiddenId, valor, decimales) {
        const num = parseFloat(valor) || 0;
        document.getElementById(displayId).value = num ? _fmt(num, decimales) : '';
        document.getElementById(hiddenId).value  = num || '';
    }

    // ===== Lógica bidireccional =====
    // Flag para evitar bucles: cuando uno recalcula a los otros no vuelven a disparar
    let _recalculating = false;

    function _sincronizarMoneda() {
        const moneda = document.getElementById('moneda').value;
        const label  = document.getElementById('costoUnitarioMonedaLabel');
        if (label) label.textContent = moneda;
    }

    // Recalcula costo_unitario = monto / toneladas (se llama cuando cambia monto o toneladas)
    function _recalcCostoDesdeMontoyTon() {
        if (_recalculating) return;
        const monto = _parse(document.getElementById('monto_total_display').value);
        const ton   = _parse(document.getElementById('toneladas_contrato_display').value);
        if (monto > 0 && ton > 0) {
            _recalculating = true;
            const costo = monto / ton;
            document.getElementById('costo_unitario_display').value = _fmt(costo, 2);
            document.getElementById('costo_unitario').value         = costo;
            _recalculating = false;
        }
    }

    // Recalcula monto_total = costo * toneladas (se llama cuando cambia costo o toneladas con costo ya ingresado)
    function _recalcMontoDesdeCostoyTon() {
        if (_recalculating) return;
        const costo = _parse(document.getElementById('costo_unitario_display').value);
        const ton   = _parse(document.getElementById('toneladas_contrato_display').value);
        if (costo > 0 && ton > 0) {
            _recalculating = true;
            const monto = costo * ton;
            document.getElementById('monto_total_display').value = _fmt(monto, 2);
            document.getElementById('monto_total').value         = monto;
            _recalculating = false;
        }
    }

    // Al cambiar toneladas: si hay costo → recalcula monto; si hay monto → recalcula costo
    function _onToneladasChange() {
        const costo = _parse(document.getElementById('costo_unitario_display').value);
        if (costo > 0) {
            _recalcMontoDesdeCostoyTon();
        } else {
            _recalcCostoDesdeMontoyTon();
        }
    }

    // Compatibilidad con llamadas legacy que existan en el código
    function actualizarCostoUnitario() { _recalcCostoDesdeMontoyTon(); }

    function montoCargar(valor) {
        _cargarCampo('monto_total_display', 'monto_total', valor, 2);
    }

    function toneladasCargar(valor) {
        _cargarCampo('toneladas_contrato_display', 'toneladas_contrato', valor, 2);
    }

    function costoUnitarioCargar(valor) {
        _cargarCampo('costo_unitario_display', 'costo_unitario', valor, 2);
    }

    document.addEventListener('DOMContentLoaded', function () {

        // Monto total
        document.getElementById('monto_total_display').addEventListener('input', function (e) {
            _cajeroOnInput(e, 'monto_total', 2, _recalcCostoDesdeMontoyTon);
        });
        document.getElementById('monto_total_display').addEventListener('blur', function () {
            _cajeroBlur('monto_total_display', 'monto_total', 2);
            _recalcCostoDesdeMontoyTon();
        });

        // Toneladas
        document.getElementById('toneladas_contrato_display').addEventListener('input', function (e) {
            _cajeroOnInput(e, 'toneladas_contrato', 2, _onToneladasChange);
        });
        document.getElementById('toneladas_contrato_display').addEventListener('blur', function () {
            _cajeroBlur('toneladas_contrato_display', 'toneladas_contrato', 2);
            _onToneladasChange();
        });

        // Costo unitario
        document.getElementById('costo_unitario_display').addEventListener('input', function (e) {
            _cajeroOnInput(e, 'costo_unitario', 2, _recalcMontoDesdeCostoyTon);
        });
        document.getElementById('costo_unitario_display').addEventListener('blur', function () {
            _cajeroBlur('costo_unitario_display', 'costo_unitario', 2);
            _recalcMontoDesdeCostoyTon();
        });

        // Moneda
        document.getElementById('moneda').addEventListener('change', _sincronizarMoneda);

        // Inicializar tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection
