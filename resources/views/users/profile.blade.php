@extends('layouts.app')
@section('titulo','Mis Datos')
@section('content')
                       
@php
      $avatarActual = auth()->user()->avatar && file_exists(public_path('storage/avatares/' . auth()->user()->avatar)) ? asset('storage/avatares/' . auth()->user()->avatar) : asset('assets/avatar/defaultAvatar.svg');
      $passwordErrors = session('active_tab') === 'change_password' || $errors->hasAny(['current_password', 'new_password', 'new_password_confirmation']);
@endphp

<div class="pagetitle">
    <h1>Mis Datos</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Mis datos</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
              <img src="{{ $avatarActual }}" alt="" class="rounded-circle">      
                    <h2 class="text-center">{{ $user->name }}</h2>
                    <h3 class="text-center">{{ $user->rol[0]->name }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-body pt-3">

                    <ul class="nav nav-tabs nav-tabs-bordered">
                        <li class="nav-item">
                            <button class="nav-link {{ $passwordErrors ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#profile-overview" type="button">Mis Datos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit" type="button">Modificar Mis Datos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $passwordErrors ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#profile-change-password" type="button">Cambiar Contraseña</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-2">
                        <div class="tab-pane fade {{ $passwordErrors ? '' : 'show active' }} profile-overview" id="profile-overview">
                            <h5 class="card-title">Detalle</h5>
                            <div class="row">
                                <div class="col-lg-3 col-md-4 label">Nombre Completo</div>
                                <div class="col-lg-9 col-md-8">
                                    @if($user->empleado_id == null)
                                        {{ $user->name }}
                                    @else
                                        {{ $user->empleado->nombres }} {{ $user->empleado->ap_paterno }} {{ $user->empleado->ap_materno }}
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-3 col-md-4 label">Rol de Acceso</div>
                                <div class="col-lg-9 col-md-8">{{ $user->rol[0]->name }}</div>
                            </div>

                            <div class="row">
                                <div class="col-lg-3 col-md-4 label">Usuario</div>
                                <div class="col-lg-9 col-md-8">{{ $user->email }}</div>
                            </div>

                            @if ($user->empleado)
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Correo Electr&oacute;nico</div>
                                    <div class="col-lg-9 col-md-8">{{ $user->empleado->email }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
                            {!! Form::model($user,['route'=>['users.update_profile',$user->id],'method'=>'PUT','enctype'=>"multipart/form-data"]) !!}
                            <p>Puede ingresar la nueva foto de perfil y presionar el bot&oacute;n <strong>GUARDAR</strong> para que se guarde en el sistema. El usuario permite acceder al sistema.</p>
                        <div class="row mb-3">
                            <label for="profileImage" class="col-md-4 col-form-label text-right">Foto de Perfil</label>
                            <div class="col-md-8 text-center text-md-start">
                                <div class="position-relative d-inline-block">
                                  <img src="{{ $avatarActual }}" alt="" id="imagen" class="rounded-circle shadow-sm border" style="width: 130px; height: 130px; object-fit: cover; background: #f1f1f1;">
                                    <label for="fileInput" class="btn btn-primary btn-sm rounded-circle position-absolute" style="bottom: 5px; right: 5px;" title="Cambiar Foto">
                                        <i class="bi bi-camera"></i>
                                    </label>
                                </div>

                                <input type="file" id="fileInput" name="avatar" style="display: none;" accept="image/*">
                                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnRemoveAvatar"><i class="bi bi-trash"></i> Quitar foto</button>
                                </div>

                                <small class="text-muted d-block mt-2">Formatos permitidos: JPG, PNG, WEBP. Máximo 2MB.</small>
                                @error('avatar')
                                    <span class="text-danger d-block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                            <div class="row mt-2">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>

                        <div class="tab-pane fade pt-3 {{ $passwordErrors ? 'show active' : '' }}" id="profile-change-password">
                            <form method="POST" action="{{ route('change.password') }}">
                                @csrf

                                <div class="row mb-3">
                                    <label for="current_password" class="col-md-4 col-lg-3 col-form-label">Contraseña Actual</label>
                                    <div class="col-md-8 col-lg-6 position-relative">
                                        <input name="current_password" type="password" class="form-control" id="current_password" required>
                                        <i id="toggleCurrentPasswordIcon" class="bi bi-eye position-absolute" style="right: 15px; top: 10px; cursor: pointer;" onclick="togglePassword('current_password', 'toggleCurrentPasswordIcon')"></i>
                                        @error('current_password')
                                            <span class="text-danger d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="new_password" class="col-md-4 col-lg-3 col-form-label">Nueva Contraseña</label>
                                    <div class="col-md-8 col-lg-6 position-relative">
                                        <input name="new_password" type="password" class="form-control" id="new_password" required>
                                        <i id="toggleNewPasswordIcon" class="bi bi-eye position-absolute" style="right: 15px; top: 10px; cursor: pointer;" onclick="togglePassword('new_password', 'toggleNewPasswordIcon')"></i>
                                        <div class="progress mt-2" style="height: 7px;">
                                            <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <small id="passwordStrengthText" class="form-text"></small>

                                        @error('new_password')
                                            <span class="text-danger d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="new_password_confirmation" class="col-md-4 col-lg-3 col-form-label">Confirmar Nueva Contraseña</label>
                                    <div class="col-md-8 col-lg-6 position-relative">
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
                                        <i id="toggleConfirmPasswordIcon" class="bi bi-eye position-absolute" style="right: 15px; top: 10px; cursor: pointer;" onclick="togglePassword('new_password_confirmation', 'toggleConfirmPasswordIcon')"></i>
                                        <small id="passwordHelpBlock" class="form-text text-muted"><span id="passwordMatch"></span></small>
                                        @error('new_password_confirmation')
                                            <span class="text-danger d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/forms/verContrasenia.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/forms/users.js') }}" type="text/javascript"></script>

@if(session('active_tab') === 'change_password' || $errors->hasAny(['current_password', 'new_password', 'new_password_confirmation']))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const triggerEl = document.querySelector('[data-bs-target="#profile-change-password"]');
    if (triggerEl && typeof bootstrap !== 'undefined') {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
    }
    @if(old('new_password'))
        const newPassword = document.getElementById('new_password');
        if (newPassword) {
            newPassword.value = @json(old('new_password'));
        }
    @endif
    @if(old('new_password_confirmation'))
        const newPasswordConfirmation = document.getElementById('new_password_confirmation');
        if (newPasswordConfirmation) {
            newPasswordConfirmation.value = @json(old('new_password_confirmation'));
        }
    @endif
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('fileInput');
    const imagen = document.getElementById('imagen');
    const btnRemove = document.getElementById('btnRemoveAvatar');
    const removeAvatar = document.getElementById('remove_avatar');
    const defaultAvatar = "{{ asset('assets/avatar/defaultAvatar.svg') }}";
    if (input && imagen && removeAvatar) {
        input.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                Swal.fire('Error', 'Debe seleccionar una imagen válida.', 'error');
                input.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Error', 'La imagen no debe superar los 2MB.', 'error');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                imagen.src = e.target.result;
                removeAvatar.value = '0';
            };
            reader.readAsDataURL(file);
        });
    }
    if (btnRemove && input && imagen && removeAvatar) {
        btnRemove.addEventListener('click', function () {
            input.value = '';
            imagen.src = defaultAvatar;
            removeAvatar.value = '1';
        });
    }
});
</script>
@endsection