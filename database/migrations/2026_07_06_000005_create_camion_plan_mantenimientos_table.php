<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camion_plan_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('camion_id');
            $table->string('tarea', 150); // Ej: Cambio de aceite, Revisión de frenos
            $table->unsignedInteger('intervalo_km'); // cada cuántos km se repite
            $table->unsignedInteger('ultimo_km')->default(0); // km en que se realizó por última vez
            $table->text('notas')->nullable();
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
        Schema::dropIfExists('camion_plan_mantenimientos');
    }
};
