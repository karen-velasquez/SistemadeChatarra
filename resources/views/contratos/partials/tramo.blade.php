@php
    $estadoColor = [
        'En ruta'        => 'primary',
        'Transbordando'  => 'warning text-dark',
        'Transbordado'   => 'info text-dark',
        'Entregado'      => 'success',
        'Div. Carga'     => 'warning text-dark',
        'Desactivado'    => 'secondary',
    ];
    $estadoIcono = [
        'En ruta'        => 'bi-truck',
        'Transbordando'  => 'bi-arrow-left-right',
        'Transbordado'   => 'bi-check2-all',
        'Entregado'      => 'bi-check-circle',
        'Div. Carga'     => 'bi-pie-chart',
        'Desactivado'    => 'bi-slash-circle',
    ];
    $estadoBorde = [
        'En ruta'        => 'border-primary',
        'Transbordando'  => 'border-warning',
        'Transbordado'   => 'border-info',
        'Entregado'      => 'border-success',
        'Div. Carga'     => 'border-warning',
        'Desactivado'    => 'border-secondary',
    ];
    $color  = $estadoColor[$tramo->estado]  ?? 'secondary';
    $icono  = $estadoIcono[$tramo->estado]  ?? 'bi-circle';
    $hijos  = $tramo->tramosHijos;
    $indent = $nivel * 20;

    // Estado general de la carga (considera hijos)
    $estadoCargaGeneral = $tramo->estado;
    if ($hijos->isNotEmpty()) {
        $todosEntregados = $hijos->every(fn($h) => in_array($h->estado, ['Entregado', 'Desactivado']));
        $estadoCargaGeneral = $todosEntregados ? 'Entregado' : 'En proceso';
    }
    $mostrarEstadoCarga = $hijos->isNotEmpty() && $estadoCargaGeneral !== $tramo->estado;

    // El borde debe reflejar el estado general de la carga si hay hijos
    $estadoParaBorde = ($hijos->isNotEmpty() && $estadoCargaGeneral === 'Entregado') ? 'Entregado' : $tramo->estado;
    $borde = $estadoBorde[$estadoParaBorde] ?? 'border-secondary';
@endphp

<div class="border rounded p-2 mb-2 {{ $nivel > 0 ? 'border-start border-3 ' . $borde : '' }}"
     style="margin-left: {{ $indent }}px; position: relative;">

    {{-- Badges de estado: arriba a la derecha --}}
    <div class="d-flex gap-1" style="position: absolute; top: 8px; right: 8px;">
        @if($mostrarEstadoCarga)
            <span class="badge {{ $estadoCargaGeneral === 'Entregado' ? 'bg-success' : 'bg-secondary' }}">
                <i class="bi bi-box-seam"></i> Carga: {{ $estadoCargaGeneral }}
            </span>
        @endif
        <span class="badge bg-{{ $color }}">
            <i class="bi {{ $icono }}"></i> {{ $tramo->estado }}
        </span>
    </div>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2" style="padding-right: 110px;">

        {{-- Info del tramo --}}
        <div>
            @if($nivel > 0)
                <span class="text-muted me-1">
                    @for($i = 0; $i < $nivel; $i++)<i class="bi bi-arrow-return-right"></i>@endfor
                </span>
            @endif

            <span class="badge bg-{{ $tramo->tipo_tramo === 'Internacional' ? 'danger' : 'secondary' }} me-1">
                {{ $tramo->tipo_tramo }}
            </span>
            <strong>{{ $tramo->origen }}</strong>
            <i class="bi bi-arrow-right mx-1 text-muted"></i>
            <strong>{{ $tramo->destino }}</strong>

            <span class="ms-2 text-muted small">
                <i class="bi bi-truck"></i> {{ $tramo->camion->placa }} {{ $tramo->camion->marca->valor ?? '-' }}
                @if($tramo->conductor)
                    &nbsp;·&nbsp;<i class="bi bi-person"></i> {{ $tramo->conductor->nombre_completo }}
                @endif
            </span>
        </div>

        {{-- Acciones --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">

            @can('contratos.edit')
                @if(!($enviosCerrados ?? false) && $tramo->estado !== 'Desactivado')
                    {{-- Botones llegada / editar: visibles si está en ruta --}}
                    @if($tramo->estado === 'En ruta')
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="abrirModalEditarTramo('{{ $tramo->uuid }}')">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-outline-success"
                            onclick="abrirModalLlegada(
                                '{{ $tramo->uuid }}',
                                '{{ $tramo->origen }} → {{ $tramo->destino }} ({{ $tramo->camion->placa }})',
                                '{{ $tramo->peso_salida }}',
                                '{{ $tramo->fecha_salida->format('Y-m-d') }}',
                                {{ $tramo->camion_id }},
                                {{ $tramo->conductor_id ?? 'null' }},
                                '{{ $tramo->tipo_tramo }}'
                            )">
                            <i class="bi bi-geo-alt"></i> Registrar llegada
                        </button>
                    @endif

                    {{-- Botón agregar transbordo: si está transbordando (aún quedan toneladas) --}}
                    @if($tramo->estado === 'Transbordando')
                        @php
                            $yaAsignadoHijos = (float) $tramo->tramosHijos()->where('activo', true)->sum('peso_salida');
                            $disponibleTransbordo = round((float) $tramo->peso_llegada - $yaAsignadoHijos, 3);
                        @endphp
                        <button class="btn btn-sm btn-outline-info"
                            onclick="abrirModalTransbordo(
                                {{ $tramo->contrato_camion_id }},
                                {{ $tramo->id }},
                                '{{ addslashes($tramo->destino) }}',
                                '{{ addslashes($tramo->origen) }} → {{ addslashes($tramo->destino) }} ({{ $tramo->camion->placa }})',
                                {{ $disponibleTransbordo }},
                                '{{ $tramo->fecha_llegada?->format('Y-m-d') }}'
                            )"
                            {{ $disponibleTransbordo <= 0 ? 'disabled' : '' }}>
                            <i class="bi bi-arrow-down-right"></i> Agregar transbordo
                            @if($disponibleTransbordo > 0)
                                <span class="badge bg-light text-dark ms-1">{{ number_format($disponibleTransbordo, 2, ',', '.') }} t disp.</span>
                            @else
                                <span class="badge bg-danger ms-1">Sin toneladas</span>
                            @endif
                        </button>
                    @endif

                    {{-- Desactivar: solo en tramos hijos, sin hijos activos propios, y solo en ruta --}}
                    @php $tieneHijosActivos = $tramo->tramosHijos()->where('activo', true)->exists(); @endphp
                    @if($nivel > 0 && !$tieneHijosActivos && $tramo->estado === 'En ruta')
                        <button class="btn btn-sm btn-outline-warning"
                            title="Desactivar tramo"
                            onclick="confirmarToggleTramo(
                                '{{ route('tramo.toggle-activo', $tramo->uuid) }}',
                                '{{ $tramo->camion->placa }}',
                                '{{ addslashes($tramo->origen) }} → {{ addslashes($tramo->destino) }}',
                                true
                            )">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                    @endif
                @elseif(!($enviosCerrados ?? false))
                    {{-- Solo mostrar botón reactivar si está desactivado y envíos abiertos --}}
                    @if($nivel > 0)
                        <button class="btn btn-sm btn-outline-success"
                            title="Reactivar tramo"
                            onclick="confirmarToggleTramo(
                                '{{ route('tramo.toggle-activo', $tramo->uuid) }}',
                                '{{ $tramo->camion->placa }}',
                                '{{ addslashes($tramo->origen) }} → {{ addslashes($tramo->destino) }}',
                                false
                            )">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    @endif
                @endif
            @endcan

            {{-- Nota de entrega y doc. adjunto: siempre visibles, con o sin envíos cerrados --}}
            @can('contratos.index')
                @if(!in_array($tramo->estado, ['Div. Carga', 'Desactivado']))
                    <a href="{{ route('tramo.nota-entrega', $tramo->uuid) }}"
                        class="btn btn-sm {{ in_array($tramo->estado, ['Entregado', 'Transbordado']) ? 'btn-outline-success' : 'btn-outline-secondary' }}" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> Nota de entrega
                    </a>
                @endif
                @if($tramo->documento_entrega)
                    <a href="{{ route('tramo.documento-entrega', $tramo->uuid) }}"
                        class="btn btn-sm btn-outline-primary" target="_blank"
                        title="Ver documento de entrega adjunto">
                        <i class="bi bi-paperclip"></i> Doc. entrega
                    </a>
                @endif
            @endcan
        </div>
    </div>

    {{-- Línea 1: pesos --}}
    <div class="mt-2 d-flex flex-wrap gap-3">
        @if($nivel === 0 && $tramo->peso_declarado)
            <small class="text-muted">
                <i class="bi bi-tag"></i> Declarado por proveedor:
                <strong>{{ number_format($tramo->peso_declarado, 2, ',', '.') }} t</strong>
            </small>
        @elseif($nivel > 0 && $tramo->peso_salida)
            <small class="text-muted">
                <i class="bi bi-box-arrow-up"></i> Salida:
                <strong>{{ number_format($tramo->peso_salida, 2, ',', '.') }} t</strong>
            </small>
        @endif
        @if($tramo->peso_llegada)
            @php $diff = $tramo->peso_salida ? $tramo->peso_salida - $tramo->peso_llegada : 0; @endphp
            <small class="{{ $diff > 0 ? 'text-warning' : 'text-success' }}">
                <i class="bi bi-box-arrow-in-down"></i> Llegada:
                <strong>{{ number_format($tramo->peso_llegada, 2, ',', '.') }} t</strong>
                @if($diff > 0)
                    <span class="text-danger">(−{{ number_format($diff, 2, ',', '.') }} t)</span>
                @endif
            </small>
        @endif
    </div>
    {{-- Línea 2: fechas con origen/destino --}}
    <div class="d-flex flex-wrap gap-3">
        @if($tramo->fecha_salida)
            <small class="text-muted">
                <i class="bi bi-calendar"></i> Salida desde <strong>{{ $tramo->origen }}</strong>: {{ $tramo->fecha_salida->format('d/m/Y') }}
            </small>
        @endif
        @if($tramo->fecha_llegada)
            <small class="text-muted">
                <i class="bi bi-calendar-check"></i> Llegada a <strong>{{ $tramo->destino }}</strong>: {{ $tramo->fecha_llegada->format('d/m/Y') }}
            </small>
        @endif
    </div>

    @if($tramo->observaciones)
        <small class="text-muted d-block mt-1">
            <i class="bi bi-chat-left-text"></i> {{ $tramo->observaciones }}
        </small>
    @endif
</div>

{{-- Tramos hijos recursivos --}}
@foreach($hijos as $hijo)
    @include('contratos.partials.tramo', [
        'tramo'               => $hijo,
        'nivel'               => $nivel + 1,
        'camionesDisponibles' => $camionesDisponibles,
        'enviosCerrados'      => $enviosCerrados ?? false,
    ])
@endforeach
