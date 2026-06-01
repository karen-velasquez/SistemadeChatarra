<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Proveedores
            ['name' => 'proveedores.index',   'descripcion' => 'Ver todos los Proveedores',  'grupo' => 'PROVEEDORES'],
            ['name' => 'proveedores.create',  'descripcion' => 'Agregar Proveedores',        'grupo' => 'PROVEEDORES'],
            ['name' => 'proveedores.edit',    'descripcion' => 'Editar Proveedores',         'grupo' => 'PROVEEDORES'],
            ['name' => 'proveedores.destroy', 'descripcion' => 'Eliminar Proveedores',       'grupo' => 'PROVEEDORES'],
            // Clientes
            ['name' => 'clientes.index',   'descripcion' => 'Ver todos los Clientes', 'grupo' => 'CLIENTES'],
            ['name' => 'clientes.create',  'descripcion' => 'Agregar Clientes',       'grupo' => 'CLIENTES'],
            ['name' => 'clientes.edit',    'descripcion' => 'Editar Clientes',        'grupo' => 'CLIENTES'],
            ['name' => 'clientes.destroy', 'descripcion' => 'Eliminar Clientes',      'grupo' => 'CLIENTES'],
            // Camiones
            ['name' => 'camiones.index',   'descripcion' => 'Ver todos los Camiones', 'grupo' => 'CAMIONES'],
            ['name' => 'camiones.create',  'descripcion' => 'Agregar Camiones',       'grupo' => 'CAMIONES'],
            ['name' => 'camiones.edit',    'descripcion' => 'Editar Camiones',        'grupo' => 'CAMIONES'],
            ['name' => 'camiones.destroy', 'descripcion' => 'Eliminar Camiones',      'grupo' => 'CAMIONES'],
            // Operadores de Transporte
            ['name' => 'operadores.index',   'descripcion' => 'Ver todos los Operadores', 'grupo' => 'OPERADORES'],
            ['name' => 'operadores.create',  'descripcion' => 'Agregar Operadores',       'grupo' => 'OPERADORES'],
            ['name' => 'operadores.edit',    'descripcion' => 'Editar Operadores',        'grupo' => 'OPERADORES'],
            ['name' => 'operadores.destroy', 'descripcion' => 'Eliminar Operadores',      'grupo' => 'OPERADORES'],
            // Contratos
            ['name' => 'contratos.index',   'descripcion' => 'Ver todos los Contratos', 'grupo' => 'CONTRATOS'],
            ['name' => 'contratos.create',  'descripcion' => 'Agregar Contratos',       'grupo' => 'CONTRATOS'],
            ['name' => 'contratos.edit',       'descripcion' => 'Editar Contratos',             'grupo' => 'CONTRATOS'],
            ['name' => 'contratos.destroy',    'descripcion' => 'Eliminar Contratos',           'grupo' => 'CONTRATOS'],
            ['name' => 'contratos.cerrar',     'descripcion' => 'Cerrar envíos de Contratos',   'grupo' => 'CONTRATOS'],
            ['name' => 'contratos.liquidacion','descripcion' => 'Ver liquidación de Contratos', 'grupo' => 'CONTRATOS'],
            // Conductores
            ['name' => 'conductores.index',   'descripcion' => 'Ver asignaciones de conductores',      'grupo' => 'CONDUCTORES'],
            ['name' => 'conductores.create',  'descripcion' => 'Asignar conductores a camiones',       'grupo' => 'CONDUCTORES'],
            ['name' => 'conductores.edit',    'descripcion' => 'Editar asignaciones de conductores',   'grupo' => 'CONDUCTORES'],
            ['name' => 'conductores.destroy', 'descripcion' => 'Eliminar asignaciones de conductores', 'grupo' => 'CONDUCTORES'],
            // Seguimiento
            ['name' => 'seguimiento.index', 'descripcion' => 'Ver seguimiento de cargas', 'grupo' => 'SEGUIMIENTO'],
            // Empleados
            ['name' => 'empleados.index',   'descripcion' => 'Ver todos los Empleados', 'grupo' => 'EMPLEADOS'],
            ['name' => 'empleados.create',  'descripcion' => 'Agregar Empleados',       'grupo' => 'EMPLEADOS'],
            ['name' => 'empleados.edit',    'descripcion' => 'Editar Empleados',        'grupo' => 'EMPLEADOS'],
            ['name' => 'empleados.destroy', 'descripcion' => 'Eliminar Empleados',      'grupo' => 'EMPLEADOS'],
            // Bancos
            ['name' => 'bancos.index',   'descripcion' => 'Ver bancos y cuentas bancarias', 'grupo' => 'BANCOS'],
            ['name' => 'bancos.create',  'descripcion' => 'Agregar bancos y cuentas',       'grupo' => 'BANCOS'],
            ['name' => 'bancos.edit',    'descripcion' => 'Editar bancos y cuentas',        'grupo' => 'BANCOS'],
            ['name' => 'bancos.destroy', 'descripcion' => 'Eliminar bancos y cuentas',      'grupo' => 'BANCOS'],
            // Cuentas bancarias
            ['name' => 'cuentas_bancarias.index',   'descripcion' => 'Ver Cuentas Bancarias',      'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.create',  'descripcion' => 'Agregar Cuentas Bancarias',  'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.edit',    'descripcion' => 'Editar Cuentas Bancarias',   'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.destroy', 'descripcion' => 'Eliminar Cuentas Bancarias', 'grupo' => 'CUENTAS_BANCARIAS'],
            // Pagos a clientes
            ['name' => 'pagos_clientes.index',   'descripcion' => 'Ver Pagos de Clientes',       'grupo' => 'PAGOS_CLIENTES'],
            ['name' => 'pagos_clientes.create',  'descripcion' => 'Registrar Pagos de Clientes', 'grupo' => 'PAGOS_CLIENTES'],
            ['name' => 'pagos_clientes.edit',    'descripcion' => 'Editar Pagos de Clientes',    'grupo' => 'PAGOS_CLIENTES'],
            ['name' => 'pagos_clientes.destroy', 'descripcion' => 'Eliminar Pagos de Clientes',  'grupo' => 'PAGOS_CLIENTES'],
            // Pagos a proveedores
            ['name' => 'pagos_proveedores.index',   'descripcion' => 'Ver Pagos a Proveedores',       'grupo' => 'PAGOS_PROVEEDORES'],
            ['name' => 'pagos_proveedores.create',  'descripcion' => 'Registrar Pagos a Proveedores', 'grupo' => 'PAGOS_PROVEEDORES'],
            ['name' => 'pagos_proveedores.edit',    'descripcion' => 'Editar Pagos a Proveedores',    'grupo' => 'PAGOS_PROVEEDORES'],
            ['name' => 'pagos_proveedores.destroy', 'descripcion' => 'Eliminar Pagos a Proveedores',  'grupo' => 'PAGOS_PROVEEDORES'],
            // Pagos a camiones
            ['name' => 'pagos_camiones.index',   'descripcion' => 'Ver Pagos a Camiones',       'grupo' => 'PAGOS_CAMIONES'],
            ['name' => 'pagos_camiones.create',  'descripcion' => 'Registrar Pagos a Camiones', 'grupo' => 'PAGOS_CAMIONES'],
            ['name' => 'pagos_camiones.edit',    'descripcion' => 'Editar Pagos a Camiones',    'grupo' => 'PAGOS_CAMIONES'],
            ['name' => 'pagos_camiones.destroy', 'descripcion' => 'Eliminar Pagos a Camiones',  'grupo' => 'PAGOS_CAMIONES'],
            // Gastos extras
            ['name' => 'gastos_extras.index',   'descripcion' => 'Ver Gastos Extras',       'grupo' => 'GASTOS_EXTRAS'],
            ['name' => 'gastos_extras.create',  'descripcion' => 'Registrar Gastos Extras', 'grupo' => 'GASTOS_EXTRAS'],
            ['name' => 'gastos_extras.edit',    'descripcion' => 'Editar Gastos Extras',    'grupo' => 'GASTOS_EXTRAS'],
            ['name' => 'gastos_extras.destroy', 'descripcion' => 'Eliminar Gastos Extras',  'grupo' => 'GASTOS_EXTRAS'],
            // Reportes
            ['name' => 'reportes.index',  'descripcion' => 'Ver Reportes',      'grupo' => 'REPORTES'],
            ['name' => 'reportes.export', 'descripcion' => 'Exportar Reportes', 'grupo' => 'REPORTES'],
            // Permisos
            ['name' => 'permisos.index',   'descripcion' => 'Ver todos los Permisos', 'grupo' => 'PERMISOS'],
            ['name' => 'permisos.create',  'descripcion' => 'Agregar Permisos',       'grupo' => 'PERMISOS'],
            ['name' => 'permisos.edit',    'descripcion' => 'Editar Permisos',        'grupo' => 'PERMISOS'],
            ['name' => 'permisos.destroy', 'descripcion' => 'Eliminar Permisos',      'grupo' => 'PERMISOS'],
            // Usuarios
            ['name' => 'users.index',   'descripcion' => 'Ver todos los Usuarios', 'grupo' => 'USUARIOS'],
            ['name' => 'users.create',  'descripcion' => 'Agregar Usuarios',       'grupo' => 'USUARIOS'],
            ['name' => 'users.edit',    'descripcion' => 'Editar Usuarios',        'grupo' => 'USUARIOS'],
            ['name' => 'users.destroy', 'descripcion' => 'Eliminar Usuarios',      'grupo' => 'USUARIOS'],
            ['name' => 'users.show',    'descripcion' => 'Ver Perfil de Usuario',   'grupo' => 'USUARIOS'],
            // Roles
            ['name' => 'roles.index',   'descripcion' => 'Ver todos los Roles', 'grupo' => 'ROLES'],
            ['name' => 'roles.create',  'descripcion' => 'Agregar Roles',       'grupo' => 'ROLES'],
            ['name' => 'roles.edit',    'descripcion' => 'Editar Roles',        'grupo' => 'ROLES'],
            ['name' => 'roles.destroy', 'descripcion' => 'Eliminar Roles',      'grupo' => 'ROLES'],
            // Empresas / Tesorería
            ['name' => 'empresas.index',   'descripcion' => 'Ver Empresas y Tesorería',         'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.create',  'descripcion' => 'Registrar Empresas y Movimientos', 'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.edit',    'descripcion' => 'Editar Empresas',                   'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.destroy', 'descripcion' => 'Eliminar Empresas',                 'grupo' => 'EMPRESAS'],
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'web'],
                ['descripcion' => $p['descripcion'], 'grupo' => $p['grupo']]
            );
        }

        $role = Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => 'web'],
            ['uuid' => Str::uuid()->toString(), 'descripcion' => 'Super Administrador']
        );

        $role->givePermissionTo(Permission::all());
    }
}
