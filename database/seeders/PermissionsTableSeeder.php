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
            ['name' => 'proveedores.show',    'descripcion' => 'Ver detalle de Proveedores', 'grupo' => 'PROVEEDORES'],
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
            // Unidades Propias
            ['name' => 'unidades.index',   'descripcion' => 'Ver Unidades Propias',                          'grupo' => 'UNIDADES_PROPIAS'],
            ['name' => 'unidades.create',  'descripcion' => 'Agregar Unidades Propias',                      'grupo' => 'UNIDADES_PROPIAS'],
            ['name' => 'unidades.edit',    'descripcion' => 'Registrar documentos, mantenimientos y talleres','grupo' => 'UNIDADES_PROPIAS'],
            ['name' => 'unidades.destroy', 'descripcion' => 'Eliminar registros de Unidades Propias',        'grupo' => 'UNIDADES_PROPIAS'],
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
            // Camiones asignados a un Contrato y sus Tramos de transporte
            ['name' => 'contrato_camion.create',  'descripcion' => 'Asignar Camiones a Contratos',        'grupo' => 'CONTRATO_CAMION'],
            ['name' => 'contrato_camion.edit',    'descripcion' => 'Editar Asignación y Flete de Camión', 'grupo' => 'CONTRATO_CAMION'],
            ['name' => 'tramo.create',  'descripcion' => 'Registrar Tramos y Llegadas',        'grupo' => 'CONTRATO_CAMION'],
            ['name' => 'tramo.edit',    'descripcion' => 'Editar Tramos y Deshacer Llegadas',  'grupo' => 'CONTRATO_CAMION'],
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
            // Bancos (catálogo)
            ['name' => 'bancos.index',   'descripcion' => 'Ver Bancos',      'grupo' => 'BANCOS'],
            ['name' => 'bancos.create',  'descripcion' => 'Agregar Bancos',  'grupo' => 'BANCOS'],
            ['name' => 'bancos.edit',    'descripcion' => 'Editar Bancos',   'grupo' => 'BANCOS'],
            ['name' => 'bancos.destroy', 'descripcion' => 'Eliminar Bancos', 'grupo' => 'BANCOS'],
            // Cuentas dentro de un Banco (catálogo de cuentas por banco)
            ['name' => 'bancos_cuentas.create',  'descripcion' => 'Agregar Cuentas de Banco',  'grupo' => 'BANCOS'],
            ['name' => 'bancos_cuentas.edit',    'descripcion' => 'Editar Cuentas de Banco',   'grupo' => 'BANCOS'],
            ['name' => 'bancos_cuentas.destroy', 'descripcion' => 'Eliminar Cuentas de Banco', 'grupo' => 'BANCOS'],
            // Cuentas bancarias de una Empresa (con saldo y movimientos reales)
            ['name' => 'cuentas_bancarias.index',   'descripcion' => 'Ver Cuentas Bancarias de Empresas',      'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.create',  'descripcion' => 'Agregar Cuentas Bancarias de Empresas',  'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.edit',    'descripcion' => 'Editar Cuentas Bancarias de Empresas',   'grupo' => 'CUENTAS_BANCARIAS'],
            ['name' => 'cuentas_bancarias.destroy', 'descripcion' => 'Eliminar Cuentas Bancarias de Empresas', 'grupo' => 'CUENTAS_BANCARIAS'],
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
            ['name' => 'reportes.capital_utilidad',  'descripcion' => 'Ver Reportes de Capital y Utilidad', 'grupo' => 'REPORTES'],
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
            ['name' => 'empresas.index',   'descripcion' => 'Ver Empresas',      'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.create',  'descripcion' => 'Registrar Empresas', 'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.edit',    'descripcion' => 'Editar Empresas',    'grupo' => 'EMPRESAS'],
            ['name' => 'empresas.destroy', 'descripcion' => 'Eliminar Empresas',  'grupo' => 'EMPRESAS'],
            // Tesorería (movimientos de cuentas)
            ['name' => 'tesoreria.index',   'descripcion' => 'Ver Movimientos de Tesorería',      'grupo' => 'TESORERIA'],
            ['name' => 'tesoreria.create',  'descripcion' => 'Registrar Movimientos de Tesorería', 'grupo' => 'TESORERIA'],
            ['name' => 'tesoreria.edit',    'descripcion' => 'Editar Movimientos de Tesorería',   'grupo' => 'TESORERIA'],
            ['name' => 'tesoreria.destroy', 'descripcion' => 'Eliminar Movimientos de Tesorería',  'grupo' => 'TESORERIA'],
            // Préstamos internos entre empresas
            ['name' => 'prestamos_internos.index',   'descripcion' => 'Ver Préstamos Internos',       'grupo' => 'PRESTAMOS_INTERNOS'],
            ['name' => 'prestamos_internos.create',  'descripcion' => 'Registrar Préstamos Internos', 'grupo' => 'PRESTAMOS_INTERNOS'],
            ['name' => 'prestamos_internos.edit',    'descripcion' => 'Editar Préstamos Internos',    'grupo' => 'PRESTAMOS_INTERNOS'],
            ['name' => 'prestamos_internos.destroy', 'descripcion' => 'Eliminar Préstamos Internos',  'grupo' => 'PRESTAMOS_INTERNOS'],
            // Créditos y Adquisiciones
            ['name' => 'adquisiciones.index',   'descripcion' => 'Ver Créditos y Adquisiciones',               'grupo' => 'ADQUISICIONES'],
            ['name' => 'adquisiciones.create',  'descripcion' => 'Registrar Adquisiciones',                    'grupo' => 'ADQUISICIONES'],
            ['name' => 'adquisiciones.edit',    'descripcion' => 'Editar Adquisiciones y registrar pagos',     'grupo' => 'ADQUISICIONES'],
            ['name' => 'adquisiciones.destroy', 'descripcion' => 'Eliminar Adquisiciones y anular pagos',      'grupo' => 'ADQUISICIONES'],
            // Lotes de entrega
            ['name' => 'lotes_entrega.index',  'descripcion' => 'Ver Lotes de Entrega',          'grupo' => 'LOTES_ENTREGA'],
            ['name' => 'lotes_entrega.cerrar', 'descripcion' => 'Cerrar Lotes de Entrega',        'grupo' => 'LOTES_ENTREGA'],
            ['name' => 'lotes_entrega.pago',   'descripcion' => 'Registrar Pagos Extra en Lote',  'grupo' => 'LOTES_ENTREGA'],
            // Lotes de pago
            ['name' => 'lotes_pago.index',   'descripcion' => 'Ver Lotes de Pago',                     'grupo' => 'LOTES_PAGO'],
            ['name' => 'lotes_pago.edit',    'descripcion' => 'Editar Código Real y Fecha de Lotes de Pago', 'grupo' => 'LOTES_PAGO'],
            ['name' => 'lotes_pago.destroy', 'descripcion' => 'Eliminar Lotes de Pago y sus pagos',    'grupo' => 'LOTES_PAGO'],
            // Parámetros
            ['name' => 'parametros.index',   'descripcion' => 'Ver Parámetros',      'grupo' => 'PARAMETROS'],
            ['name' => 'parametros.create',  'descripcion' => 'Agregar Parámetros',  'grupo' => 'PARAMETROS'],
            ['name' => 'parametros.edit',    'descripcion' => 'Editar Parámetros',   'grupo' => 'PARAMETROS'],
            ['name' => 'parametros.destroy', 'descripcion' => 'Eliminar Parámetros', 'grupo' => 'PARAMETROS'],
            // Reglas de comisión
            ['name' => 'reglas_comision.index',   'descripcion' => 'Ver Reglas de Comisión',      'grupo' => 'REGLAS_COMISION'],
            ['name' => 'reglas_comision.create',  'descripcion' => 'Agregar Reglas de Comisión',  'grupo' => 'REGLAS_COMISION'],
            ['name' => 'reglas_comision.edit',    'descripcion' => 'Editar Reglas de Comisión',   'grupo' => 'REGLAS_COMISION'],
            ['name' => 'reglas_comision.destroy', 'descripcion' => 'Eliminar Reglas de Comisión', 'grupo' => 'REGLAS_COMISION'],

            // Reglas de comisión 2
            ['name' => 'reglas_comision2.index',   'descripcion' => 'Ver Reglas de Comisión 2',      'grupo' => 'REGLAS_COMISION2'],
            ['name' => 'reglas_comision2.create',  'descripcion' => 'Agregar Reglas de Comisión 2',  'grupo' => 'REGLAS_COMISION2'],
            ['name' => 'reglas_comision2.edit',    'descripcion' => 'Editar Reglas de Comisión 2',   'grupo' => 'REGLAS_COMISION2'],
            ['name' => 'reglas_comision2.destroy', 'descripcion' => 'Eliminar Reglas de Comisión 2', 'grupo' => 'REGLAS_COMISION2'],

            // Reglas de IT
            ['name' => 'reglas_it.index',   'descripcion' => 'Ver Reglas de IT',      'grupo' => 'REGLAS_IT'],
            ['name' => 'reglas_it.create',  'descripcion' => 'Agregar Reglas de IT',  'grupo' => 'REGLAS_IT'],
            ['name' => 'reglas_it.edit',    'descripcion' => 'Editar Reglas de IT',   'grupo' => 'REGLAS_IT'],
            ['name' => 'reglas_it.destroy', 'descripcion' => 'Eliminar Reglas de IT', 'grupo' => 'REGLAS_IT'],

            // Reglas de costo adicional
            ['name' => 'reglas_costo_adicional.index',   'descripcion' => 'Ver Reglas de Costo Adicional',      'grupo' => 'REGLAS_COSTO_ADICIONAL'],
            ['name' => 'reglas_costo_adicional.create',  'descripcion' => 'Agregar Reglas de Costo Adicional',  'grupo' => 'REGLAS_COSTO_ADICIONAL'],
            ['name' => 'reglas_costo_adicional.edit',    'descripcion' => 'Editar Reglas de Costo Adicional',   'grupo' => 'REGLAS_COSTO_ADICIONAL'],
            ['name' => 'reglas_costo_adicional.destroy', 'descripcion' => 'Eliminar Reglas de Costo Adicional', 'grupo' => 'REGLAS_COSTO_ADICIONAL'],
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
