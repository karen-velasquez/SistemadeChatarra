<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camion_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('camion_id');
            $table->unsignedBigInteger('taller_id')->nullable();
            $table->date('fecha');
            $table->string('tipo', 100); // Ej: Chapería, Cambio de aceite, Frenos
            $table->text('descripcion')->nullable();
            $table->decimal('costo', 12, 2)->default(0);
            $table->unsignedInteger('kilometraje')->nullable();
            $table->string('comprobante')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('camion_id')->references('id')->on('camiones')->onDelete('cascade');
            $table->foreign('taller_id')->references('id')->on('talleres')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camion_mantenimientos');
    }
};
