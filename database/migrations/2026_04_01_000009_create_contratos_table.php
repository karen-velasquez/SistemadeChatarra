<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('numero_contrato', 20)->unique();
            $table->enum('tipo_contrato', ['Nacional', 'Internacional']);
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('proveedor_id');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->unsignedInteger('cantidad_camiones')->nullable();
            $table->decimal('toneladas_contrato', 10, 3)->nullable();
            $table->decimal('monto_total', 15, 2);
            $table->string('moneda', 10)->default('BOB');
            $table->enum('estado', ['Activo', 'Concluido'])->default('Activo');
            $table->boolean('envios_cerrados')->default(false);
            $table->timestamp('envios_cerrados_at')->nullable();
            $table->string('documento_pdf')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('proveedor_id')->references('id')->on('proveedors');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
