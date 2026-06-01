
@extends('layouts.app')
@section('titulo','Nuevo Usuario')
@section('content')

<div class="pagetitle">
    <h1>SEGURIDAD DE ACCESOS</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Nuevo Usuario</li>
      </ol>
    </nav>
 </div><!-- End Page Title -->
 <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Nuevo Usuario del Sistema</h5>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Registre un nuevo usuario del sistema asociado a un empleado. Cada usuario tiene acceso según el rol asignado.
                El empleado seleccionado no podrá ser modificado posteriormente para mantener la integridad de los registros de auditoría.
            </p>

            {{-- Indicadores de características --}}
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-badge-fill text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Asociación</small>
                            <strong class="small">Empleado del sistema</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-lock-fill text-success me-2"></i>
                        <div>
                            <small class="text-muted d-block">Seguridad</small>
                            <strong class="small">Validación en tiempo real</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-key-fill text-info me-2"></i>
                        <div>
                            <small class="text-muted d-block">Contraseña</small>
                            <strong class="small">Mínimo 8 caracteres</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-magic text-warning me-2"></i>
                        <div>
                            <small class="text-muted d-block">Autocompletado</small>
                            <strong class="small">Datos del empleado</strong>
                        </div>
                    </div>
                </div>
            </div>

           <!--CONTENIDO -->
           {!! Form::open(['route'=>'users.store','class'=>'form-horizontal']) !!}
                @include('users._form',['texto' => 'Registrar','tipo'=>'1','texto_pass'=>'Contraseña','color'=>'primary'])
            {!! Form::close() !!}
            <!-- EndCONTENIDO Example -->
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/forms/users.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/forms/verContrasenia.js') }}" type="text/javascript"></script>
@endsection
