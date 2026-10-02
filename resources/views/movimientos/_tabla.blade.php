{{--
    Partial: tabla de movimientos reutilizable
    Variables requeridas:
      $movimientos  — colección o paginador de Movimiento (con cuentaEmpresa.empresa cargado)
    Variables opcionales:
      $mostrarCuenta   — bool, muestra columna Empresa/Cuenta (default true)
      $mostrarEliminar — bool, muestra botón eliminar (default false)
      $mostrarEditar   — bool, muestra botón editar junto al de eliminar, solo
                         en movimientos manuales (categoría "otro") (default false)
--}}
@php
    $mostrarCuenta   = $mostrarCuenta   ?? true;
    $mostrarEliminar = $mostrarEliminar ?? false;
    $mostrarEditar   = $mostrarEditar   ?? false;
@endphp

<div class="table-responsive">
    <table class="table table-hover table-sm table-bordered align-middle" id="tabla_movimientos">
        <thead class="table-light">
            <tr>
                <th>Fecha</th>
                @if($mostrarCuenta)
                <th>Empresa / Cuenta</th>
                @endif
                <th>Concepto</th>
                <th>Categoría</th>
                <th class="text-center">Tipo</th>
                <th class="text-end">Monto (Bs)</th>
                <th>Código</th>
                <th class="text-center"></th>
                @if($mostrarEliminar)
                <th class="text-center">Acción</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $m)
            @php
                $anulado  = !is_null($m->deleted_at);
                $rowClass = $anulado ? 'table-secondary' : ($m->tipo === 'ingreso' ? 'table-success' : 'table-danger');
                $catBadge = match($m->categoria) {
                    'anticipo_cliente'     => 'bg-primary',
                    'pago_cliente'        => 'bg-primary',
                    'pago_proveedor'      => 'bg-warning text-dark',
                    'pago_camion'         => 'bg-info text-dark',
                    'gasto_extra'         => 'bg-dark',
                    'pago_sueldo'         => 'bg-secondary',
                    'prestamo_otorgado'   => 'bg-purple',
                    'prestamo_recibido'   => 'bg-purple',
                    'devolucion_prestamo' => 'bg-purple',
                    default               => 'bg-secondary',
                };
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="text-nowrap">{{ $m->fecha->format('d/m/Y') }}</td>
                @if($mostrarCuenta)
                <td>
                    <div class="small fw-semibold">{{ $m->cuentaEmpresa->empresa->nombre ?? '-' }}</div>
                    <div class="text-muted small">{{ $m->cuentaEmpresa->nombre_cuenta ?? '-' }}</div>
                    @if($m->cuentaEmpresa->banco ?? false)
                    <div class="text-muted" style="font-size:.72rem"><i class="bi bi-bank me-1"></i>{{ $m->cuentaEmpresa->banco->nombre }}</div>
                    @endif
                </td>
                @endif
                <td>
                    @if($anulado)
                        <span class="text-decoration-line-through text-muted">{{ $m->concepto }}</span>
                        <span class="badge bg-danger ms-1" style="font-size:.65rem">ANULADO</span>
                    @else
                        {{ $m->concepto }}
                    @endif
                </td>
                <td><span class="badge {{ $catBadge }} {{ $anulado ? 'opacity-50' : '' }}">{{ \App\Models\Movimiento::categoriaLabel($m->categoria) }}</span></td>
                <td class="text-center">
                    @if($anulado)
                        <span class="badge bg-secondary">{{ $m->tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}</span>
                    @elseif($m->tipo === 'ingreso')
                        <span class="badge bg-success">Ingreso</span>
                    @else
                        <span class="badge bg-danger">Egreso</span>
                    @endif
                </td>
                <td class="text-end fw-semibold text-nowrap {{ $anulado ? 'text-muted text-decoration-line-through' : ($m->tipo === 'ingreso' ? 'text-success' : 'text-danger') }}">
                    {{ $m->tipo === 'ingreso' ? '+' : '-' }} Bs {{ number_format($m->monto_bolivianos, 2, ',', '.') }}
                </td>
                <td class="text-nowrap">
                    @if($m->codigo_seguimiento)
                        @php $esQr = str_starts_with($m->codigo_seguimiento, 'QR-'); @endphp
                        <span class="badge {{ $esQr ? 'bg-info text-dark' : 'bg-light text-dark border' }}" style="font-size:.72rem">
                            <i class="bi bi-{{ $esQr ? 'qr-code' : 'arrow-left-right' }} me-1"></i>{{ $m->codigo_seguimiento }}
                        </span>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $cuentaOrigenInfo = '';
                        $cuentaDestinoInfo = '';
                        $tieneOrigen = false;
                        $esLotePago = false;

                        if ($m->lotePago && $m->lotePago->cuentaOrigen) {
                            $esLotePago = true;
                            $cuentaOrigenInfo = ($m->lotePago->cuentaOrigen->empresa->nombre ?? '') . ' - ' . $m->lotePago->cuentaOrigen->nombre_cuenta;
                        }

                        if ($m->origen_type && $m->origen) {
                            $tieneOrigen = true;

                            // Para INGRESOS: origen = cuenta del cliente (CuentaBancaria), destino = mi cuenta (CuentaEmpresa)
                            // Para EGRESOS: origen = mi cuenta (CuentaEmpresa), destino = cuenta del proveedor/camión (CuentaBancaria)

                            if ($m->tipo === 'ingreso') {
                                // Origen: cuenta del cliente (CuentaBancaria)
                                if (method_exists($m->origen, 'cuentaOrigen') && $m->origen->cuentaOrigen) {
                                    $cuenta = $m->origen->cuentaOrigen;
                                    if (get_class($cuenta) === 'App\Models\CuentaBancaria') {
                                        $titular = $cuenta->nombre_titular ?? 'Cliente';
                                        if ($cuenta->apellido_paterno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_paterno_titular;
                                        }
                                        if ($cuenta->apellido_materno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_materno_titular;
                                        }
                                        $bancoNombre = $cuenta->banco ? $cuenta->banco->nombre : '';
                                        $cuentaOrigenInfo = $titular . ($bancoNombre ? ' - ' . $bancoNombre : '');
                                    } else {
                                        // Es CuentaEmpresa
                                        $cuentaOrigenInfo = ($cuenta->empresa->nombre ?? '') . ' - ' . $cuenta->nombre_cuenta;
                                    }
                                }
                                // Destino: mi cuenta empresa (CuentaEmpresa)
                                if (method_exists($m->origen, 'cuentaDestino') && $m->origen->cuentaDestino) {
                                    $cuenta = $m->origen->cuentaDestino;
                                    if (get_class($cuenta) === 'App\Models\CuentaEmpresa') {
                                        $cuentaDestinoInfo = ($cuenta->empresa->nombre ?? '') . ' - ' . $cuenta->nombre_cuenta;
                                    } else {
                                        // Es CuentaBancaria
                                        $titular = $cuenta->nombre_titular ?? 'Tercero';
                                        if ($cuenta->apellido_paterno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_paterno_titular;
                                        }
                                        if ($cuenta->apellido_materno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_materno_titular;
                                        }
                                        $bancoNombre = $cuenta->banco ? $cuenta->banco->nombre : '';
                                        $cuentaDestinoInfo = $titular . ($bancoNombre ? ' - ' . $bancoNombre : '');
                                    }
                                }
                            } else {
                                // Origen: mi cuenta empresa (CuentaEmpresa)
                                if (method_exists($m->origen, 'cuentaOrigen') && $m->origen->cuentaOrigen) {
                                    $cuenta = $m->origen->cuentaOrigen;
                                    if (get_class($cuenta) === 'App\Models\CuentaEmpresa') {
                                        $cuentaOrigenInfo = ($cuenta->empresa->nombre ?? '') . ' - ' . $cuenta->nombre_cuenta;
                                    } else {
                                        // Es CuentaBancaria
                                        $titular = $cuenta->nombre_titular ?? 'Tercero';
                                        if ($cuenta->apellido_paterno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_paterno_titular;
                                        }
                                        if ($cuenta->apellido_materno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_materno_titular;
                                        }
                                        $bancoNombre = $cuenta->banco ? $cuenta->banco->nombre : '';
                                        $cuentaOrigenInfo = $titular . ($bancoNombre ? ' - ' . $bancoNombre : '');
                                    }
                                }
                                // Destino: cuenta del proveedor/camión (CuentaBancaria)
                                if (method_exists($m->origen, 'cuentaDestino') && $m->origen->cuentaDestino) {
                                    $cuenta = $m->origen->cuentaDestino;
                                    if (get_class($cuenta) === 'App\Models\CuentaBancaria') {
                                        $titular = $cuenta->nombre_titular ?? 'Proveedor/Camión';
                                        if ($cuenta->apellido_paterno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_paterno_titular;
                                        }
                                        if ($cuenta->apellido_materno_titular) {
                                            $titular .= ' ' . $cuenta->apellido_materno_titular;
                                        }
                                        $bancoNombre = $cuenta->banco ? $cuenta->banco->nombre : '';
                                        $cuentaDestinoInfo = $titular . ($bancoNombre ? ' - ' . $bancoNombre : '');
                                    } else {
                                        // Es CuentaEmpresa
                                        $cuentaDestinoInfo = ($cuenta->empresa->nombre ?? '') . ' - ' . $cuenta->nombre_cuenta;
                                    }
                                }
                            }
                        }
                    @endphp
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1"
                        data-fecha="{{ $m->fecha->format('d/m/Y') }}"
                        data-empresa="{{ $m->cuentaEmpresa->empresa->nombre ?? '-' }}"
                        data-cuenta="{{ $m->cuentaEmpresa->nombre_cuenta ?? '-' }}"
                        data-banco="{{ $m->cuentaEmpresa->banco->nombre ?? '' }}"
                        data-concepto="{{ $m->concepto }}"
                        data-categoria="{{ \App\Models\Movimiento::categoriaLabel($m->categoria) }}"
                        data-tipo="{{ $m->tipo }}"
                        data-monto="{{ $m->moneda }} {{ number_format($m->monto, 2) }}"
                        data-tc="{{ number_format($m->tipo_cambio, 4) }}"
                        data-montobs="{{ number_format($m->monto_bolivianos, 2) }}"
                        data-moneda="{{ $m->moneda }}"
                        data-obs="{{ $m->observaciones ?? '' }}"
                        data-codigo="{{ $m->codigo_seguimiento ?? '' }}"
                        data-tiene-origen="{{ $tieneOrigen ? '1' : '0' }}"
                        data-es-lote="{{ $esLotePago ? '1' : '0' }}"
                        data-cuenta-origen="{{ $cuentaOrigenInfo }}"
                        data-cuenta-destino="{{ $cuentaDestinoInfo }}"
                        data-origen-tipo="{{ $m->origen_type ? class_basename($m->origen_type) : '' }}"
                        onclick="verDetalleMovimiento(this)"
                        title="Ver detalle">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
                @if($mostrarEliminar)
                <td class="text-center text-nowrap">
                    @if($mostrarEditar && !$anulado && !$m->origen_type && $m->categoria === 'otro')
                    @can('tesoreria.edit')
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                        onclick="abrirEditarMovimiento('{{ $m->uuid }}', '{{ $m->tipo }}', '{{ number_format((float) $m->monto, 2, '.', '') }}', '{{ number_format((float) $m->tipo_cambio, 4, '.', '') }}', '{{ $m->fecha->format('Y-m-d') }}', {{ Illuminate\Support\Js::from($m->concepto) }}, {{ Illuminate\Support\Js::from($m->observaciones ?? '') }})"
                        title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    @endcan
                    @endif
                    @can('tesoreria.destroy')
                    @if($anulado)
                        <span class="btn btn-sm btn-outline-secondary py-0 px-1 disabled" tabindex="0"
                              data-bs-toggle="tooltip" data-bs-trigger="hover focus"
                              title="Este movimiento ya fue eliminado.">
                            <i class="bi bi-slash-circle"></i>
                        </span>
                    @elseif($m->origen_type)
                        <span class="btn btn-sm btn-outline-secondary py-0 px-1 disabled" tabindex="0"
                              data-bs-toggle="tooltip" data-bs-trigger="hover focus"
                              title="Las ediciones de estos movimientos son desde sus respectivos módulos.">
                            <i class="bi bi-lock"></i>
                        </span>
                    @else
                    <a href="{{ route('tesoreria.movimiento.destroy', $m->uuid) }}"
                       class="btn btn-sm btn-outline-danger py-0 px-1"
                       onclick="return confirm('¿Eliminar este movimiento? Esto revertirá el saldo.')">
                        <i class="bi bi-trash"></i>
                    </a>
                    @endif
                    @endcan
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ 8 + ($mostrarCuenta ? 1 : 0) + ($mostrarEliminar ? 1 : 0) }}" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                    Sin movimientos registrados
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal detalle (se incluye una sola vez por página) --}}
@once
<div class="modal fade" id="modalDetalleMovimiento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white"><i class="bi bi-receipt me-2"></i>Detalle del Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="background: transparent url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e&quot;) center/1em auto no-repeat; filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body pt-2">
                {{-- Tipo y Fecha --}}
                <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded" style="background: #f8f9fa;">
                    <div>
                        <small class="text-muted d-block mb-1">Tipo de movimiento</small>
                        <span id="det_tipo"></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block mb-1">Fecha</small>
                        <span class="fw-semibold" id="det_fecha"></span>
                    </div>
                </div>

                {{-- Concepto y Categoría --}}
                <div class="mb-3">
                    <div class="p-3 rounded" style="background: #e7f3ff; border-left: 4px solid #0d6efd;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <small class="text-muted d-block mb-1"><i class="bi bi-file-text me-1"></i>Concepto</small>
                                <div class="fw-semibold" id="det_concepto"></div>
                            </div>
                            <div class="ms-3" id="det_categoria"></div>
                        </div>
                    </div>
                </div>

                {{-- Código de seguimiento --}}
                <div id="det_row_codigo" class="mb-3">
                    <div class="p-2 rounded" style="background: #fff9e6; border-left: 4px solid #ffc107;">
                        <small class="text-muted d-block mb-1"><i class="bi bi-hash me-1"></i>Código de seguimiento</small>
                        <div id="det_codigo"></div>
                    </div>
                </div>

                {{-- Flujo de dinero --}}
                <div id="det_row_flujo" class="mb-3">
                    <div class="p-3 rounded" style="background: #f0f9ff; border-left: 4px solid #06b6d4;">
                        <h6 class="text-muted mb-3" style="font-size: 0.85rem;"><i class="bi bi-arrow-left-right me-1"></i>FLUJO DE DINERO</h6>
                        <div class="row g-3">
                            <div class="col-md-6" id="det_col_origen">
                                <div class="text-center p-3 rounded" style="background: white; border: 2px dashed #dc3545;">
                                    <small class="text-danger fw-semibold d-block mb-2"><i class="bi bi-box-arrow-up me-1"></i>ORIGEN</small>
                                    <div id="det_cuenta_origen" class="small fw-semibold"></div>
                                </div>
                            </div>
                            <div class="col-md-6" id="det_col_destino">
                                <div class="text-center p-3 rounded" style="background: white; border: 2px dashed #28a745;">
                                    <small class="text-success fw-semibold d-block mb-2"><i class="bi bi-box-arrow-in-down me-1"></i>DESTINO</small>
                                    <div id="det_cuenta_destino" class="small fw-semibold"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mi cuenta registrada --}}
                <div class="mb-3">
                    <div class="p-3 rounded" style="background: #f0fdf4; border-left: 4px solid #10b981;">
                        <h6 class="text-muted mb-2" style="font-size: 0.85rem;"><i class="bi bi-wallet2 me-1"></i>MI CUENTA REGISTRADA</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Empresa</small>
                                <div class="fw-semibold" id="det_empresa"></div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Cuenta</small>
                                <div class="fw-semibold" id="det_cuenta"></div>
                            </div>
                        </div>
                        <div id="det_row_banco" class="mt-2">
                            <small class="text-muted d-block">Banco</small>
                            <div class="fw-semibold" id="det_banco"></div>
                        </div>
                    </div>
                </div>

                {{-- Monto --}}
                <div class="mb-3">
                    <div class="p-3 rounded" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                        <h6 class="text-muted mb-2" style="font-size: 0.85rem;"><i class="bi bi-cash-coin me-1"></i>MONTOS</h6>
                        <div class="row" id="det_row_monto_orig">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Monto original</small>
                                <div class="fw-semibold" id="det_monto"></div>
                            </div>
                            <div class="col-md-6" id="det_row_tc">
                                <small class="text-muted d-block">Tipo de cambio</small>
                                <div class="fw-semibold" id="det_tc"></div>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted d-block">Monto en Bolivianos</small>
                            <div class="fs-4" id="det_monto_bs"></div>
                        </div>
                    </div>
                </div>

                {{-- Información adicional --}}
                <div id="det_row_info_adicional" class="mb-3">
                    <div class="p-3 rounded" style="background: #fce7f3; border-left: 4px solid #ec4899;">
                        <h6 class="text-muted mb-2" style="font-size: 0.85rem;"><i class="bi bi-info-circle me-1"></i>INFORMACIÓN ADICIONAL</h6>
                        <div id="det_info_origen"></div>
                        <div id="det_info_lote"></div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div id="det_row_obs" class="mb-2">
                    <div class="p-3 rounded" style="background: #f3f4f6; border-left: 4px solid #6b7280;">
                        <small class="text-muted d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>Observaciones</small>
                        <div id="det_obs" class="text-muted small"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function verDetalleMovimiento(btn) {
    const d = btn.dataset;

    // Fecha y tipo
    document.getElementById('det_fecha').textContent = d.fecha;
    document.getElementById('det_tipo').innerHTML = d.tipo === 'ingreso'
        ? '<span class="badge bg-success fs-6"><i class="bi bi-arrow-down-circle me-1"></i>INGRESO</span>'
        : '<span class="badge bg-danger fs-6"><i class="bi bi-arrow-up-circle me-1"></i>EGRESO</span>';

    // Concepto y categoría
    document.getElementById('det_concepto').textContent = d.concepto;
    document.getElementById('det_categoria').innerHTML = '<span class="badge bg-secondary">' + d.categoria + '</span>';

    // Código de seguimiento
    const codigoRow = document.getElementById('det_row_codigo');
    if (d.codigo) {
        codigoRow.style.display = '';
        const esQr = d.codigo.startsWith('QR-');
        const badgeClass = esQr ? 'bg-info text-dark' : 'bg-light text-dark border';
        const iconClass = esQr ? 'qr-code' : 'arrow-left-right';
        document.getElementById('det_codigo').innerHTML =
            '<span class="badge ' + badgeClass + ' fs-6"><i class="bi bi-' + iconClass + ' me-1"></i>' + d.codigo + '</span>';
    } else {
        codigoRow.style.display = 'none';
    }

    // Flujo de dinero
    const flujoRow = document.getElementById('det_row_flujo');
    const tieneOrigen = d.tieneOrigen === '1';
    const esLote = d.esLote === '1';

    if (tieneOrigen || esLote) {
        flujoRow.style.display = '';

        // Cuenta origen
        const colOrigen = document.getElementById('det_col_origen');
        if (d.cuentaOrigen) {
            colOrigen.style.display = '';
            document.getElementById('det_cuenta_origen').textContent = d.cuentaOrigen;
        } else {
            colOrigen.style.display = 'none';
        }

        // Cuenta destino
        const colDestino = document.getElementById('det_col_destino');
        if (d.cuentaDestino) {
            colDestino.style.display = '';
            document.getElementById('det_cuenta_destino').textContent = d.cuentaDestino;
        } else {
            colDestino.style.display = 'none';
        }
    } else {
        flujoRow.style.display = 'none';
    }

    // Mi cuenta registrada
    document.getElementById('det_empresa').textContent = d.empresa;
    document.getElementById('det_cuenta').textContent = d.cuenta;
    const bancoRow = document.getElementById('det_row_banco');
    if (d.banco) {
        bancoRow.style.display = '';
        document.getElementById('det_banco').textContent = d.banco;
    } else {
        bancoRow.style.display = 'none';
    }

    // Montos
    const esBob = d.moneda === 'BOB';
    const montoOrigRow = document.getElementById('det_row_monto_orig');
    if (esBob) {
        montoOrigRow.style.display = 'none';
    } else {
        montoOrigRow.style.display = '';
        document.getElementById('det_monto').textContent = d.monto;
        document.getElementById('det_tc').textContent = d.tc;
    }

    const color = d.tipo === 'ingreso' ? 'text-success' : 'text-danger';
    const signo = d.tipo === 'ingreso' ? '+' : '-';
    document.getElementById('det_monto_bs').className = 'fs-4 fw-bold ' + color;
    document.getElementById('det_monto_bs').textContent = signo + ' Bs ' + d.montobs;

    // Información adicional
    const infoAdicionalRow = document.getElementById('det_row_info_adicional');
    const infoOrigen = document.getElementById('det_info_origen');
    const infoLote = document.getElementById('det_info_lote');

    let tieneInfoAdicional = false;

    if (tieneOrigen && d.origenTipo) {
        tieneInfoAdicional = true;
        const tipoMap = {
            'PagoCliente': 'Pago de Cliente',
            'PagoProveedor': 'Pago a Proveedor',
            'PagoCamion': 'Pago a Camión',
            'PrestamoInterno': 'Préstamo Interno'
        };
        infoOrigen.innerHTML = '<small class="text-muted">Generado por:</small> <span class="badge bg-primary">' +
            (tipoMap[d.origenTipo] || d.origenTipo) + '</span>';
        infoOrigen.style.display = '';
    } else {
        infoOrigen.style.display = 'none';
    }

    if (esLote) {
        tieneInfoAdicional = true;
        infoLote.innerHTML = '<small class="text-muted mt-2 d-block"><i class="bi bi-collection me-1"></i>Este movimiento forma parte de un <strong>lote de pago masivo</strong></small>';
        infoLote.style.display = '';
    } else {
        infoLote.style.display = 'none';
    }

    infoAdicionalRow.style.display = tieneInfoAdicional ? '' : 'none';

    // Observaciones
    const obsRow = document.getElementById('det_row_obs');
    if (d.obs) {
        obsRow.style.display = '';
        document.getElementById('det_obs').textContent = d.obs;
    } else {
        obsRow.style.display = 'none';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleMovimiento')).show();
}

// Tooltips de los botones bloqueados (candado/anulado): un <span disabled> no
// dispara el title nativo de forma confiable en todos los navegadores.
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush
@endonce
