<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operadores_transporte', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre', 150);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100);
            $table->string('ci', 20)->unique();
            $table->unsignedBigInteger('ci_pais_id')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->enum('tipo_operador', ['propietario', 'chofer', 'ambos'])->default('chofer');
            $table->string('licencia_numero', 30)->nullable();
            $table->unsignedBigInteger('licencia_pais_id')->nullable();
            $table->date('licencia_vencimiento')->nullable();
            $table->string('doc_carnet')->nullable();
            $table->string('doc_licencia')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('ci_pais_id')->references('id')->on('parametros')->onDelete('set null');
            $table->foreign('licencia_pais_id')->references('id')->on('parametros')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadores_transporte');
    }
};
