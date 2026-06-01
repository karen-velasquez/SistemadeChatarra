@extends('layouts.app')
@section('titulo','Permisos')
@section('content')
<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>Permisos</h1>
            <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Permisos</li>
            </ol>
            </nav>
        </div>
    </div>
 </div>
        
<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                <h5 class="card-title">Permisos Registrados</h5>
                <div class="alert alert-info border-0 d-flex align-items-start gap-2 py-2 px-3 mb-3">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <div class="small">
                        <strong>Vista de solo lectura.</strong> Los permisos son las acciones específicas que se pueden habilitar o restringir dentro del sistema (ver, crear, editar, eliminar).
                        Se agrupan por módulo y se asignan a los roles para controlar con precisión el acceso de cada usuario.
                        <br><strong>Nota:</strong> Los permisos son administrados directamente en la base de datos por el equipo de desarrollo.
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Nombre del acceso o ruta</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Grupo</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($permisos as $p )
                                <tr>
                                    <td class="fw-bold">{{ $p->name }}</td>
                                    <td class="text-center">{{ $p->descripcion }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{$p->grupo}}</span>
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
@endsection
@section('scripts')
<script src="{{ asset('js/tablas/basica.js') }}" type="text/javascript"></script>
@endsection