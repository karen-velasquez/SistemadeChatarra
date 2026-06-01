<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camiones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('placa', 20)->unique();
            $table->unsignedBigInteger('placa_pais_id')->nullable();
            $table->unsignedBigInteger('tipo_vehiculo_id')->nullable();
            $table->unsignedBigInteger('marca_id')->nullable();
            $table->string('modelo', 100);
            $table->year('anio');
            $table->decimal('capacidad_kg', 10, 2);
            $table->string('color', 50)->nullable();
            $table->enum('estado', ['Activo', 'Inactivo', 'En mantenimiento'])->default('Activo');
            $table->string('documento_ruat')->nullable();
            $table->unsignedBigInteger('propietario_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('placa_pais_id')->references('id')->on('parametros')->onDelete('set null');
            $table->foreign('tipo_vehiculo_id')->references('id')->on('parametros')->onDelete('set null');
            $table->foreign('marca_id')->references('id')->on('parametros')->onDelete('set null');
            $table->foreign('propietario_id')->references('id')->on('operadores_transporte')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camiones');
    }
};
