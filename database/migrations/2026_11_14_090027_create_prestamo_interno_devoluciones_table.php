<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamo_interno_devoluciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('prestamo_interno_id')->constrained('prestamos_internos')->cascadeOnDelete();
            $table->decimal('monto', 15, 2);
            $table->date('fecha');
            $table->string('concepto')->nullable();
            $table->string('voucher')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_interno_devoluciones');
    }
};
