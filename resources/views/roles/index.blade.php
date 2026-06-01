@extends('layouts.app')
@section('titulo','Roles Sistema')
@section('content')
<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>Roles de Acceso</h1>
            <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Roles</li>
            </ol>
            </nav>
        </div>
        @can('roles.create')
            <a href="{{route('roles.create')}}" class="btn btn-primary" title="Crea un nuevo rol con sus permisos">Agregar Nuevo</a>
        @endcan
    </div>
 </div><!-- End Page Title -->
 <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Roles de Acceso Registrados</h5>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Los roles agrupan un conjunto de permisos que determinan qué puede hacer cada usuario dentro del sistema.
                Asigna el rol adecuado a cada persona según su función en la empresa: administrador, operador, supervisor, etc.
            </p>
           <!--CONTENIDO -->
           <div class="table-responsive">
            <table id="tabla_roles" class="table table-hover table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Nº</th>
                        <th class="text-center">Rol</th>
                        <th class="text-center">Descripción</th>
                        <th class="text-center">Nro usuarios <br>con el rol</th>
                        <th class="text-center">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="fw-bold">
                                {{$role->name}}
                                @if($role->users_count > 0)
                                    <span class="badge bg-info text-dark ms-2" title="Este rol está siendo usado por {{$role->users_count}} {{ $role->users_count == 1 ? 'usuario' : 'usuarios' }}">
                                        <i class="bi bi-link-45deg"></i> En uso
                                    </span>
                                @endif
                            </td>
                            <td class="text-wrap">{{$role->descripcion}}</td>
                            <td class="text-center"><span class="badge bg-secondary">{{$role->users_count}} {{ $role->users_count == 1 ? 'Usuario' : 'Usuarios' }}</span></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('roles.show')
                                    <a href="{{route('roles.show',$role->uuid)}}"
                                       class="btn btn-sm btn-outline-info"
                                       title="Ver Permisos">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    @can('roles.edit')
                                    <a href="{{route('roles.edit',$role->uuid)}}"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('roles.destroy')
                                        @if($role->users_count > 0)
                                            <button class="btn btn-sm btn-secondary" disabled
                                                title="No se puede eliminar porque {{$role->users_count}} {{ $role->users_count == 1 ? 'usuario tiene' : 'usuarios tienen' }} este rol asignado"
                                                style="opacity: 0.5; cursor: not-allowed;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('roles.destroy',$role->uuid) }}"
                                               class="btn btn-sm btn-outline-danger"
                                               title="Eliminar"
                                               onclick="return confirm('¿Eliminar el rol {{$role->name}}? Esta acción no se puede deshacer.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        @endif
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
<script>
$(document).ready(function() {
    $('#tabla_roles').DataTable({
        language: {
            processing:  "Procesando...",
            lengthMenu:  'Mostrar <select><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="-1">Todos</option></select> registros',
            paginate:    { sFirst: "Primero", sLast: "Último", previous: "Anterior", next: "Siguiente" },
            info:        "Página _PAGE_ de _PAGES_ — _TOTAL_ roles",
            search:      "Buscar:",
            emptyTable:  "No hay roles registrados.",
            infoEmpty:   "",
        },
        orderCellsTop: true,
        ordering:      true,
        order:         [[1, 'asc']], // Ordenar por nombre de rol
        pageLength:    10,
        lengthMenu:    [10, 25, 50, -1],
        columnDefs:    [
            { orderable: false, targets: 0 }, // Nº no ordenable
            { orderable: false, targets: -1 } // Opciones no ordenables
        ],
    });
});
</script>
@endsection