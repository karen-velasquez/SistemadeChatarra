<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camion_conductores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('camion_id');
            $table->unsignedBigInteger('conductor_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('camion_id')->references('id')->on('camiones');
            $table->foreign('conductor_id')->references('id')->on('operadores_transporte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camion_conductores');
    }
};
