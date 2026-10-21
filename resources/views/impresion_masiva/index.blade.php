@extends('layouts.app')
@section('titulo','Impresión Masiva')

@section('content')
<div class="pagetitle">
    <h1>IMPRESIÓN MASIVA DE DOCUMENTOS</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item active">Impresión Masiva</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Generar paquete de documentos para imprimir</h5>
            <p class="text-muted small">
                Selecciona un rango de fechas y los tipos de documento a incluir. Se generará un
                único PDF con una portada/índice y todos los documentos adjuntos encontrados —
                las imágenes se convierten automáticamente a PDF — listo para enviar directo a imprimir.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('impresion_masiva.buscar') }}" method="POST" target="_blank">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="desde" value="{{ old('desde') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="hasta" value="{{ old('hasta') }}" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label d-block">Tipos de documento a incluir</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tipos[]" value="contratos" id="tipo_contratos">
                        <label class="form-check-label" for="tipo_contratos">Contratos (documento adjunto)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tipos[]" value="documentos_entrega" id="tipo_entrega">
                        <label class="form-check-label" for="tipo_entrega">Documentos de entrega (tramos)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tipos[]" value="vouchers_proveedor" id="tipo_vp">
                        <label class="form-check-label" for="tipo_vp">Vouchers de pago a proveedores</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tipos[]" value="vouchers_cliente" id="tipo_vc">
                        <label class="form-check-label" for="tipo_vc">Vouchers de cobro a clientes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tipos[]" value="vouchers_camion" id="tipo_vf">
                        <label class="form-check-label" for="tipo_vf">Vouchers de pago de flete (camiones)</label>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-printer me-1"></i>Generar PDF para imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
