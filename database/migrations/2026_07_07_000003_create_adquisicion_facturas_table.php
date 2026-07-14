<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adquisicion_facturas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('adquisicion_id');
            $table->string('numero', 50)->nullable();
            $table->date('fecha');
            $table->string('emisor', 150)->nullable();
            $table->decimal('monto', 14, 2);
            $table->string('moneda', 5)->default('BOB');
            $table->string('archivo')->nullable();
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
        Schema::dropIfExists('adquisicion_facturas');
    }
};
