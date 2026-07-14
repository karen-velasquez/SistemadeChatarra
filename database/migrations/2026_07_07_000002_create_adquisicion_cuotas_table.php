<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adquisicion_cuotas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('adquisicion_id');
            $table->unsignedInteger('nro');
            $table->date('fecha_programada');
            $table->decimal('monto', 14, 2); // en la moneda de la adquisición
            $table->decimal('tasa_aplicada', 6, 3)->nullable(); // % anual de esta cuota (interés variable); si es null usa la de la adquisición
            // Datos del pago realizado
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto_pagado', 14, 2)->nullable(); // en la moneda de la adquisición
            $table->decimal('tipo_cambio', 10, 4)->nullable(); // Bs por unidad de moneda
            $table->decimal('monto_pagado_bob', 14, 2)->nullable(); // lo que realmente salió de la empresa en Bs
            $table->string('comprobante')->nullable();
            $table->string('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('adquisicion_id')->references('id')->on('adquisiciones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adquisicion_cuotas');
    }
};
