<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adquisiciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('descripcion'); // qué bien se adquiere
            $table->string('tipo_bien', 100)->nullable(); // Camión, Maquinaria, Inmueble...
            $table->unsignedBigInteger('camion_id')->nullable(); // si el bien es una unidad propia
            $table->enum('origen', ['NACIONAL', 'EXTERIOR'])->default('NACIONAL');
            $table->string('pais_origen', 100)->nullable();
            $table->string('vendedor')->nullable(); // vendedor o beneficiario de los pagos
            $table->enum('financiamiento', ['CREDITO', 'CAPITAL']);
            $table->string('entidad_financiera', 150)->nullable(); // banco, si es crédito
            $table->string('moneda', 5)->default('BOB'); // moneda en que se pactó la compra/crédito
            $table->decimal('monto_total', 14, 2);
            $table->enum('tipo_interes', ['FIJO', 'VARIABLE'])->nullable();
            $table->decimal('tasa_interes', 6, 3)->nullable(); // % anual
            $table->date('fecha_adquisicion');
            $table->date('fecha_contrato_inicio')->nullable();
            $table->date('fecha_contrato_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('camion_id')->references('id')->on('camiones')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adquisiciones');
    }
};
