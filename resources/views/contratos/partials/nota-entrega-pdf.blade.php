<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }

    .header { text-align: center; border-bottom: 3px solid #1a6e3c; padding-bottom: 12px; margin-bottom: 20px; }
    .header h1 { font-size: 22px; color: #1a6e3c; letter-spacing: 1px; }
    .header h2 { font-size: 13px; color: #555; font-weight: normal; margin-top: 4px; }
    .header .numero { font-size: 11px; color: #888; margin-top: 4px; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .badge-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .badge-info    { background: #e0f2fe; color: #0c4a6e; border: 1px solid #7dd3fc; }
    .badge-danger  { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }

    .section { margin-bottom: 18px; }
    .section-title {
        font-size: 11px; font-weight: bold; text-transform: uppercase;
        color: #fff; background: #1a6e3c;
        padding: 4px 10px; border-radius: 3px; margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 5px 8px; vertical-align: top; }
    table.info td.label { font-weight: bold; color: #444; width: 38%; }
    table.info tr:nth-child(even) td { background: #f7f7f7; }

    .two-col { display: table; width: 100%; }
    .col-left  { display: table-cell; width: 50%; padding-right: 10px; vertical-align: top; }
    .col-right { display: table-cell; width: 50%; padding-left: 10px; vertical-align: top; }

    .pesos-box {
        border: 2px solid #1a6e3c; border-radius: 6px;
        padding: 12px 16px; margin-bottom: 18px;
        background: #f0fdf4;
    }
    .pesos-box .row { display: table; width: 100%; margin-bottom: 6px; }
    .pesos-box .cell { display: table-cell; text-align: center; }
    .pesos-box .big { font-size: 20px; font-weight: bold; color: #1a6e3c; }
    .pesos-box .lbl { font-size: 10px; color: #555; text-transform: uppercase; }
    .pesos-box .diff { font-size: 11px; color: #b91c1c; font-weight: bold; }
    .divider { border: none; border-top: 1px dashed #ccc; margin: 14px 0; }

    .descuento-box {
        border: 1px solid #fca5a5; border-radius: 5px;
        background: #fff7f7; padding: 8px 12px; margin-bottom: 18px;
    }
    .descuento-box .title { font-weight: bold; color: #b91c1c; margin-bottom: 4px; }

    .obs-box {
        border: 1px solid #d1d5db; border-radius: 5px;
        background: #fafafa; padding: 8px 12px; margin-bottom: 18px;
        font-style: italic; color: #555;
    }

    .ruta {
        text-align: center; font-size: 14px; font-weight: bold;
        padding: 10px; background: #f0fdf4; border-radius: 5px;
        border: 1px solid #6ee7b7; margin-bottom: 18px; color: #1a6e3c;
    }
    .ruta .arrow { color: #aaa; margin: 0 8px; }

    .footer {
        border-top: 1px solid #ccc; margin-top: 30px; padding-top: 10px;
        font-size: 10px; color: #888; text-align: center;
    }

    .firma-section { display: table; width: 100%; margin-top: 40px; }
    .firma-col { display: table-cell; width: 33%; text-align: center; padding: 0 10px; }
    .firma-line { border-top: 1px solid #333; margin: 0 auto 5px; width: 80%; }
    .firma-label { font-size: 10px; color: #555; }

    /* Marca de agua: logo centrado y semitransparente, detrás del contenido */
    .marca-agua {
        position: fixed;
        top: 50%;
        left: 50%;
        width: 380px;
        margin-left: -190px;   /* la mitad del width, para centrar (DomPDF no soporta transform) */
        margin-top: -190px;
        text-align: center;
        opacity: 0.07;
        z-index: -1000;
    }
    .marca-agua img { width: 380px; }
</style>
</head>
<body>

{{-- MARCA DE AGUA (logo centrado y tenue, detrás del contenido) --}}
<div class="marca-agua">
    <img src="{{ public_path('assets/images/logo.jpeg') }}" alt="">
</div>

{{-- ENCABEZADO --}}
<div style="display:table; width:100%; border-bottom:3px solid #1a6e3c; padding-bottom:12px; margin-bottom:20px;">
    <div style="display:table-cell; vertical-align:middle; width:150px;">
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:center;">
        <h1 style="font-size:22px; color:#1a6e3c; letter-spacing:1px;">NOTA DE ENTREGA</h1>
        <h2 style="font-size:13px; color:#555; font-weight:normal; margin-top:4px;">Contrato N° {{ $tramo->contratoCamion->contrato->numero_contrato }}</h2>
        <div style="font-size:11px; color:#888; margin-top:4px;">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:right; width:150px;">
        <img src="{{ public_path('assets/images/logo.jpeg') }}" alt="Logo" style="height:90px;">
    </div>
</div>

{{-- RUTA --}}
<div class="ruta">
    {{ strtoupper($tramo->origen) }}
    <span class="arrow">&#8594;</span>
    {{ strtoupper($tramo->destino) }}
    &nbsp;&nbsp;
    <span class="badge {{ $tramo->tipo_tramo === 'Internacional' ? 'badge-danger' : 'badge-info' }}">
        {{ $tramo->tipo_tramo }}
    </span>
</div>

{{-- PESOS --}}
@php
    $esParcial    = $tramo->estado === 'Div. Carga';
    $enTransito   = in_array($tramo->estado, ['En ruta', 'Transbordando']);
    $tramoHijo    = $esParcial ? $tramo->tramosHijos()->orderBy('id')->first() : null;
    $totalLlego   = $esParcial
        ? round((float)$tramo->peso_llegada + (float)optional($tramoHijo)->peso_salida, 3)
        : (float)$tramo->peso_salida;
    $tnCliente    = (float)$tramo->peso_llegada;
    $tnRestante   = $tramoHijo ? (float)$tramoHijo->peso_salida : 0;
    $merma        = (!$esParcial && $tramo->peso_llegada !== null)
        ? round((float)$tramo->peso_salida - (float)$tramo->peso_llegada, 3)
        : 0;
@endphp
<div class="pesos-box">
    @if($esParcial)
    <div class="row">
        <div class="cell">
            <div class="big">{{ number_format($totalLlego, 2, ',', '.') }} t</div>
            <div class="lbl">Total que llegó</div>
        </div>
        <div class="cell" style="font-size:22px; color:#aaa; padding-top:8px;">&#8594;</div>
        <div class="cell">
            <div class="big" style="color:#0c4a6e;">{{ number_format($tnCliente, 2, ',', '.') }} t</div>
            <div class="lbl">Entregado a este cliente</div>
        </div>
        @if($tnRestante > 0)
        <div class="cell">
            <div class="big" style="color:#b45309;">{{ number_format($tnRestante, 2, ',', '.') }} t</div>
            <div class="lbl">Continúa en nuevo tramo</div>
        </div>
        @endif
    </div>
    @else
    <div class="row">
        <div class="cell">
            <div class="big">{{ $tramo->peso_salida !== null ? number_format($tramo->peso_salida, 2, ',', '.') . ' t' : '—' }}</div>
            <div class="lbl">Peso de salida</div>
        </div>
        <div class="cell" style="font-size:22px; color:#aaa; padding-top:8px;">&#8594;</div>
        <div class="cell">
            @if($tramo->peso_llegada !== null)
                <div class="big">{{ number_format($tramo->peso_llegada, 2, ',', '.') }} t</div>
                <div class="lbl">Peso de llegada (neto)</div>
            @else
                <div class="big" style="color:#b45309;">En tránsito</div>
                <div class="lbl">Peso de llegada pendiente</div>
            @endif
        </div>
        @if($merma > 0)
        <div class="cell">
            <div class="big diff">− {{ number_format($merma, 2, ',', '.') }} t</div>
            <div class="lbl">Merma</div>
        </div>
        @endif
    </div>
    @endif
</div>

<div class="two-col">
    {{-- DATOS DEL CAMIÓN --}}
    <div class="col-left">
        <div class="section">
            <div class="section-title">Datos del Camión</div>
            <table class="info">
                <tr><td class="label">Placa:</td><td>{{ $tramo->camion->placa }}</td></tr>
                <tr><td class="label">Marca / Modelo:</td><td>{{ $tramo->camion->marca->valor ?? '-' }} {{ $tramo->camion->modelo }}</td></tr>
                @if($tramo->camion->color)
                <tr><td class="label">Color:</td><td>{{ $tramo->camion->color }}</td></tr>
                @endif
                @if($tramo->camion->anio)
                <tr><td class="label">Año:</td><td>{{ $tramo->camion->anio }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    {{-- DATOS DEL CONDUCTOR --}}
    <div class="col-right">
        <div class="section">
            <div class="section-title">Datos del Conductor</div>
            @if($tramo->conductor)
            <table class="info">
                <tr><td class="label">Nombre:</td><td>{{ $tramo->conductor->nombre_completo }}</td></tr>
                @if($tramo->conductor->licencia_numero)
                <tr><td class="label">N° Licencia:</td><td>{{ $tramo->conductor->licencia_numero }}</td></tr>
                @endif
                @if($tramo->conductor->licencia_pais)
                <tr><td class="label">País licencia:</td><td>{{ $tramo->conductor->licencia_pais }}</td></tr>
                @endif
                @if($tramo->conductor->telefono)
                <tr><td class="label">Teléfono:</td><td>{{ $tramo->conductor->telefono }}</td></tr>
                @endif
            </table>
            @else
                <p style="color:#888; font-style:italic;">Sin conductor registrado.</p>
            @endif
        </div>
    </div>
</div>

<hr class="divider">

{{-- FECHAS Y CONTRATO --}}
<div class="section">
    <div class="section-title">Datos del Viaje</div>
    <table class="info">
        <tr>
            <td class="label">Fecha de salida:</td>
            <td>{{ $tramo->fecha_salida?->format('d/m/Y') ?? '—' }}</td>
            <td class="label">Fecha de llegada:</td>
            <td>{{ $tramo->fecha_llegada?->format('d/m/Y') ?? 'En tránsito' }}</td>
        </tr>
        <tr>
            <td class="label">Proveedor:</td>
            <td>{{ $tramo->contratoCamion->contrato->proveedor?->nombre ?? '—' }}</td>
            <td class="label">Cliente:</td>
            <td>{{ $tramo->cliente?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Estado:</td>
            <td colspan="3">
                @php
                    $badgeClase = match($tramo->estado) {
                        'Entregado'    => 'badge-success',
                        'Transbordado' => 'badge-info',
                        default        => 'badge-info',
                    };
                @endphp
                <span class="badge {{ $badgeClase }}">{{ $tramo->estado }}</span>
                @if(in_array($tramo->estado, ['En ruta', 'Transbordando']))
                    <span style="font-size:10px; color:#b45309; margin-left:6px;">* Documento provisional — carga aún en tránsito</span>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- PRECIO POR TONELADA --}}
@if($tramo->precio_por_tonelada)
@php
    $monedaVenta  = $tramo->moneda_venta ?? 'BOB';
    $precioTon    = (float) $tramo->precio_por_tonelada;
    $totalVenta   = round($precioTon * $tnCliente, 2);
@endphp
<div style="border:2px solid #1a6e3c; border-radius:5px; background:#f0fdf4; padding:10px 14px; margin-bottom:18px;">
    <div style="font-weight:bold; color:#1a6e3c; margin-bottom:6px; text-transform:uppercase; font-size:11px;">Valor de la Carga Entregada</div>
    <div style="display:table; width:100%;">
        <div style="display:table-cell; text-align:center;">
            <div style="font-size:18px; font-weight:bold; color:#1a6e3c;">{{ number_format($precioTon, 2, ',', '.') }} {{ $monedaVenta }}/t</div>
            <div style="font-size:10px; color:#555; text-transform:uppercase;">Precio por tonelada</div>
        </div>
        <div style="display:table-cell; text-align:center; font-size:22px; color:#aaa; padding-top:6px;">×</div>
        <div style="display:table-cell; text-align:center;">
            <div style="font-size:18px; font-weight:bold; color:#1a6e3c;">{{ number_format($tnCliente, 2, ',', '.') }} t</div>
            <div style="font-size:10px; color:#555; text-transform:uppercase;">Toneladas entregadas</div>
        </div>
        <div style="display:table-cell; text-align:center; font-size:22px; color:#aaa; padding-top:6px;">=</div>
        <div style="display:table-cell; text-align:center;">
            <div style="font-size:20px; font-weight:bold; color:#065f46;">{{ number_format($totalVenta, 2, ',', '.') }} {{ $monedaVenta }}</div>
            <div style="font-size:10px; color:#555; text-transform:uppercase;">Total a cobrar</div>
        </div>
    </div>
</div>
@endif

{{-- DESCUENTO --}}
@if($tramo->descuento_porcentaje)
<div class="descuento-box">
    <div class="title">&#9888; Descuento aplicado al pago del camionero</div>
    <strong>{{ number_format($tramo->descuento_porcentaje, 2, ',', '.') }}%</strong> sobre el flete de este tramo.
</div>
@endif

{{-- OBSERVACIONES --}}
@if($tramo->observaciones_llegada)
<div class="obs-box">
    <strong>Observaciones de llegada:</strong> {{ $tramo->observaciones_llegada }}
</div>
@endif

@if($tramo->observaciones)
<div class="obs-box">
    <strong>Observaciones del tramo:</strong> {{ $tramo->observaciones }}
</div>
@endif

{{-- FIRMAS --}}
<div class="firma-section">
    <div class="firma-col">
        <div class="firma-line"></div>
        <div class="firma-label">Firma del Conductor</div>
        <div class="firma-label">{{ $tramo->conductor?->nombre_completo ?? '—' }}</div>
    </div>
    <div class="firma-col">
        <div class="firma-line"></div>
        <div class="firma-label">Firma del Receptor</div>
        <div class="firma-label">{{ $tramo->cliente?->nombre ?? '—' }}</div>
    </div>
    <div class="firma-col">
        <div class="firma-line"></div>
        <div class="firma-label">Sello / Empresa</div>
    </div>
</div>

<div class="footer">
    Documento generado por el Sistema de Compra y Venta de Chatarra &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
