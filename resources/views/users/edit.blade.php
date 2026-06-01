@extends('layouts.app')
@section('titulo','Editar Usuario')
@section('content')

<div class="pagetitle">
    <h1>SEGURIDAD DE ACCESOS</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Modificar Datos</li>
      </ol>
    </nav>
 </div><!-- End Page Title -->
 <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Modificar Datos del Usuario</h5>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Actualice la información del usuario. El empleado asociado no puede ser modificado para mantener la integridad de los registros del sistema.
                Puede cambiar el rol asignado para ajustar los permisos de acceso.
            </p>

            {{-- Indicadores de características --}}
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lock-fill text-warning me-2"></i>
                        <div>
                            <small class="text-muted d-block">Empleado</small>
                            <strong class="small">No modificable</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pencil-fill text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Rol</small>
                            <strong class="small">Modificable</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-envelope-fill text-info me-2"></i>
                        <div>
                            <small class="text-muted d-block">Email</small>
                            <strong class="small">Modificable</strong>
                        </div>
                    </div>
                </div>
            </div>

           <!--CONTENIDO -->
           {!! Form::model($user,['route'=>['users.update',$user->id],'method'=>'PUT']) !!}
                @include('users._form',['texto' => 'Actualizar','tipo'=>'2','texto_pass'=>'Cambiar Contraseña','color'=>'success'])
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


