<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('banco_id');
            $table->enum('tipo_titular', ['proveedor', 'operador', 'empleado', 'cliente']);
            $table->unsignedBigInteger('titular_id')->nullable();
            $table->string('titular_type', 100)->nullable();
            $table->string('numero_cuenta', 100);
            $table->string('moneda', 10)->default('BOB');
            $table->string('alias', 150)->nullable();
            $table->string('nombre_titular', 100)->nullable();
            $table->string('apellido_paterno_titular', 100)->nullable();
            $table->string('apellido_materno_titular', 100)->nullable();
            $table->string('nro_documento', 20)->nullable();
            $table->string('email_notificacion', 150)->nullable();
            $table->string('tipo_relacion', 50)->nullable();
            $table->string('sucursal_departamento', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('banco_id')->references('id')->on('bancos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
