<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camion_documentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('camion_id');
            $table->enum('tipo', ['RUAT', 'CONTRATO', 'IMPUESTO', 'SEGURO', 'SOAT', 'OTRO']);
            $table->string('descripcion')->nullable();
            $table->string('archivo')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('camion_id')->references('id')->on('camiones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camion_documentos');
    }
};
