@extends('layouts.app')
@section('titulo','Usuarios')
@section('content')
<div class="pagetitle mb-0">
    <div class="d-flex flex-row align-items-center justify-content-between mb-0">
        <div>
            <h1>SEGURIDAD DE ACCESOS</h1>
            <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Usuarios</li>
            </ol>
            </nav>
        </div>
           <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"👤 Aquí administras las <b>cuentas de acceso</b> al sistema. Cada usuario tiene un <b>rol</b> que define qué puede hacer."},
                        {"element":"#datos","intro":"📋 La lista de usuarios: el empleado vinculado, su nombre, el correo con el que entra, su rol y si está activo.","position":"top"},
                        {"element":"#datos thead th:nth-child(4)","intro":"🎭 El <b>Rol Asignado</b> determina los permisos del usuario. Los roles se gestionan en el submódulo Roles.","position":"bottom"},
                        {"element":"#datos thead th:nth-child(5)","intro":"🚦 <b>Estado</b>: Activo o Inactivo. Un usuario inactivo no puede entrar al sistema ni editarse.","position":"bottom"},
                        {"element":"#datos tbody tr:first-child td:last-child","intro":"⚙️ Acciones: ✏️ editar el usuario o 🗑 eliminarlo.","position":"left"},
                        {"element":"#btnNuevoUsuario","intro":"➕ Con <b>Agregar Nuevo</b> creas una cuenta: eliges el empleado, su correo, contraseña y rol. Se abre en una página aparte.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('users.create')
            <a href="{{route('users.create')}}" id="btnNuevoUsuario" class="btn btn-primary" title="Crea un nuevo usuario"> Agregar Nuevo</a>
            @endcan
           </div>
        </div>
   
 </div><!-- End Page Title -->
 <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Usuarios Registrados</h5>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Administra las cuentas de acceso al sistema. Cada usuario tiene un rol asignado que determina las acciones que puede realizar.
                Crea, edita o desactiva usuarios según las necesidades del equipo de trabajo.
            </p>
           <!--CONTENIDO -->
            <div class="table-responsive">
                <table cellspacing="0" width="100%" id="datos" class="table table-hover table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center">Empleado</th>
                            <th class="text-center">Nombre Completo</th>
                            <th class="text-center">Correo para acceso <br> al sistema</th>
                            <th class="text-center">Rol Asignado</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr class="{{ !$user->estado ? 'table-secondary text-muted' : '' }}">
                                <td class="text-center">
                                    @if($user->empleado)
                                        <span class="badge bg-success" title="Empleado: {{$user->empleado->nombre_completo}}">
                                            <i class="bi bi-person-check-fill"></i> {{$user->empleado->cargo->valor ?? 'Sin cargo'}}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-exclamation-triangle"></i> Sin empleado
                                        </span>
                                    @endif
                                </td>
                                <td>{{$user->name}}</td>
                                <td class="text-center">{{$user->email}}</td>
                                <td class="text-center">
                                    @foreach ($user->roles as $rol )
                                    <strong>{{$rol->name}}</strong>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($user->estado)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill"></i> Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle-fill"></i> Inactivo
                                        </span>
                                    @endif
                                </td>
                             
                                 <td class="text-center">
                                      <div class="d-flex gap-1 justify-content-center">
                                                @can('users.edit')
                                                    @if($user->estado)
                                                        <a href="{{ route('users.edit',$user->uuid) }}"
                                                           class="btn btn-sm btn-outline-secondary"
                                                           title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    @else
                                                        <button class="btn btn-sm btn-secondary" disabled
                                                                title="No se puede editar un usuario inactivo. Active primero al empleado asociado."
                                                                style="opacity: 0.5; cursor: not-allowed;">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                                @can('users.destroy')
                                                <a href="{{ route('users.destroy', $user->uuid) }}"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Eliminar"
                                                   onclick="return confirm('¿Eliminar al usuario {{$user->name}}? Esta acción no se puede deshacer.')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                @endcan
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

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
@endsection
