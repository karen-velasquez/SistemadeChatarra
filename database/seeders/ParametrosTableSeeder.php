<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

class ParametrosTableSeeder extends Seeder
{
    public function run()
    {
        // Grupos del sistema
        Parametro::create(['tipo' => 'grupos', 'descripcion' => 'ADMINISTRACION']);
        Parametro::create(['tipo' => 'grupos', 'descripcion' => 'PROVEEDORES']);
        Parametro::create(['tipo' => 'grupos', 'descripcion' => 'CLIENTES']);

        // Países (descripcion = nombre largo, valor = código/nombre corto)
        Parametro::create(['tipo' => 'paises', 'descripcion' => 'Bolivia', 'valor' => 'BOLIVIA']);
        Parametro::create(['tipo' => 'paises', 'descripcion' => 'Brasil', 'valor' => 'BRASIL']);
        Parametro::create(['tipo' => 'paises', 'descripcion' => 'Chile', 'valor' => 'CHILE']);
        Parametro::create(['tipo' => 'paises', 'descripcion' => 'Argentina', 'valor' => 'ARGENTINA']);
        Parametro::create(['tipo' => 'paises', 'descripcion' => 'Paraguay', 'valor' => 'PARAGUAY']);

        // Sucursales de cuentas bancarias (descripcion = nombre, valor = sigla)
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Cochabamba', 'valor' => 'CBB']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Cobija',     'valor' => 'COB']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'La Paz',     'valor' => 'LPZ']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Oruro',      'valor' => 'ORU']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Potosí',     'valor' => 'POT']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Santa Cruz', 'valor' => 'SCZ']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Sucre',      'valor' => 'SUC']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Tarija',     'valor' => 'TJA']);
        Parametro::create(['tipo' => 'sucursal_cuenta', 'descripcion' => 'Trinidad',   'valor' => 'TRI']);

        // Lugar de expedición CI
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'LP']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'CB']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'SC']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'OR']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'PT']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'TA']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'BN']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'PD']);
        Parametro::create(['tipo' => 'lugar_ci', 'descripcion' => 'CH']);

        // Relaciones Titular Empleado (para cuentas bancarias)
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Esposa', 'valor' => 'Esposa']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Esposo', 'valor' => 'Esposo']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Hermana', 'valor' => 'Hermana']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Hermano', 'valor' => 'Hermano']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Madre', 'valor' => 'Madre']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Padre', 'valor' => 'Padre']);
        Parametro::create(['tipo' => 'relacion_titular_empleado', 'descripcion' => 'Familiar', 'valor' => 'Familiar']);

        // Relaciones Titular Proveedor
        Parametro::create(['tipo' => 'relacion_titular_proveedor', 'descripcion' => 'Gerente', 'valor' => 'Gerente']);
        Parametro::create(['tipo' => 'relacion_titular_proveedor', 'descripcion' => 'Empleado', 'valor' => 'Empleado']);
        Parametro::create(['tipo' => 'relacion_titular_proveedor', 'descripcion' => 'Representante legal', 'valor' => 'Representante legal']);
        Parametro::create(['tipo' => 'relacion_titular_proveedor', 'descripcion' => 'Socio', 'valor' => 'Socio']);

        // Relaciones Titular Cliente
        Parametro::create(['tipo' => 'relacion_titular_cliente', 'descripcion' => 'Gerente', 'valor' => 'Gerente']);
        Parametro::create(['tipo' => 'relacion_titular_cliente', 'descripcion' => 'Empleado', 'valor' => 'Empleado']);
        Parametro::create(['tipo' => 'relacion_titular_cliente', 'descripcion' => 'Representante legal', 'valor' => 'Representante legal']);
        Parametro::create(['tipo' => 'relacion_titular_cliente', 'descripcion' => 'Socio', 'valor' => 'Socio']);
        Parametro::create(['tipo' => 'relacion_titular_cliente', 'descripcion' => 'Familiar', 'valor' => 'Familiar']);

        // Relaciones Titular Propietario/Conductor (Operador)
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Esposa', 'valor' => 'Esposa']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Esposo', 'valor' => 'Esposo']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Hermana', 'valor' => 'Hermana']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Hermano', 'valor' => 'Hermano']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Madre', 'valor' => 'Madre']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Padre', 'valor' => 'Padre']);
        Parametro::create(['tipo' => 'relacion_titular_propietario_conductor', 'descripcion' => 'Familiar', 'valor' => 'Familiar']);

        // Tipos de Moneda (descripcion = nombre completo, valor = código ISO)
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Boliviano', 'valor' => 'BOB']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Dólar estadounidense', 'valor' => 'USD']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Peso argentino', 'valor' => 'ARS']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Real brasileño', 'valor' => 'BRL']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Peso chileno', 'valor' => 'CLP']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Peso colombiano', 'valor' => 'COP']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Sol peruano', 'valor' => 'PEN']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Guaraní paraguayo', 'valor' => 'PYG']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Peso uruguayo', 'valor' => 'UYU']);
        Parametro::create(['tipo' => 'tipo_moneda', 'descripcion' => 'Euro', 'valor' => 'EUR']);

        // Marcas de Camiones
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Mercedes-Benz', 'valor' => 'MERCEDES-BENZ']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Volvo', 'valor' => 'VOLVO']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Scania', 'valor' => 'SCANIA']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Freightliner', 'valor' => 'FREIGHTLINER']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'International', 'valor' => 'INTERNATIONAL']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Kenworth', 'valor' => 'KENWORTH']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Mack', 'valor' => 'MACK']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Hino', 'valor' => 'HINO']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Isuzu', 'valor' => 'ISUZU']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Ford', 'valor' => 'FORD']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'Chevrolet', 'valor' => 'CHEVROLET']);
        Parametro::create(['tipo' => 'camion_marca', 'descripcion' => 'JAC', 'valor' => 'JAC']);

        // Tipos de Vehículo (Camiones)
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Tracto', 'valor' => 'TRACTO']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Semirremolque', 'valor' => 'SEMIRREMOLQUE']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Plataforma', 'valor' => 'PLATAFORMA']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Volqueta', 'valor' => 'VOLQUETA']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Cisterna', 'valor' => 'CISTERNA']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Refrigerado', 'valor' => 'REFRIGERADO']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camión Furgón', 'valor' => 'FURGON']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Camioneta', 'valor' => 'CAMIONETA']);
        Parametro::create(['tipo' => 'camion_tipo', 'descripcion' => 'Otro', 'valor' => 'OTRO']);

        // Tipos de bien (Créditos y Adquisiciones)
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'CAMIONES'], ['descripcion' => 'Camiones y unidades de transporte']);
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'VEHICULO_LIVIANO'], ['descripcion' => 'Auto, camioneta u otro vehículo liviano']);
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'MAQUINARIA'], ['descripcion' => 'Maquinaria y equipo pesado']);
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'INMUEBLE'], ['descripcion' => 'Terrenos, oficinas, galpones']);
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'ARTEFACTO'], ['descripcion' => 'Artefactos y equipos varios']);
        Parametro::firstOrCreate(['tipo' => 'bien_tipo', 'valor' => 'OTRO'], ['descripcion' => 'Otro tipo de bien']);

        // Países de origen para adquisiciones/importaciones (Bolivia y limítrofes primero, se puede ampliar)
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'BOLIVIA'], ['descripcion' => 'Bolivia']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'BRASIL'], ['descripcion' => 'Brasil']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'CHILE'], ['descripcion' => 'Chile']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'ARGENTINA'], ['descripcion' => 'Argentina']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'PARAGUAY'], ['descripcion' => 'Paraguay']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'PERU'], ['descripcion' => 'Perú']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'CHINA'], ['descripcion' => 'China']);
        Parametro::firstOrCreate(['tipo' => 'pais_exportacion', 'valor' => 'ESTADOS_UNIDOS'], ['descripcion' => 'Estados Unidos']);

        // Países de Sudamérica para documentos (primero Bolivia y limítrofes, luego los demás)
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Bolivia', 'valor' => 'BOLIVIA']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Perú', 'valor' => 'PERU']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Chile', 'valor' => 'CHILE']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Argentina', 'valor' => 'ARGENTINA']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Brasil', 'valor' => 'BRASIL']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Paraguay', 'valor' => 'PARAGUAY']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Colombia', 'valor' => 'COLOMBIA']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Ecuador', 'valor' => 'ECUADOR']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Uruguay', 'valor' => 'URUGUAY']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Venezuela', 'valor' => 'VENEZUELA']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Guyana', 'valor' => 'GUYANA']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Surinam', 'valor' => 'SURINAM']);
        Parametro::create(['tipo' => 'pais_documento', 'descripcion' => 'Guayana Francesa', 'valor' => 'GUAYANA_FRANCESA']);

        // Cargos de Empleados
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Dueño', 'valor' => 'DUEÑO']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Gerente General', 'valor' => 'GERENTE GENERAL']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Administrador', 'valor' => 'ADMINISTRADOR']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Contador', 'valor' => 'CONTADOR']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Facturación', 'valor' => 'FACTURACIÓN']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Operador Logístico', 'valor' => 'OPERADOR LOGÍSTICO']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Almacenero', 'valor' => 'ALMACENERO']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Cajero', 'valor' => 'CAJERO']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Asistente', 'valor' => 'ASISTENTE']);
        Parametro::create(['tipo' => 'cargo_empleados', 'descripcion' => 'Secretaria', 'valor' => 'SECRETARIA']);
    }
}
