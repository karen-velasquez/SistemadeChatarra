<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre', 150);
            $table->unsignedBigInteger('pais_id')->nullable();
            $table->string('codigo_swift', 20)->nullable();
            $table->string('codigo_banco', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('pais_id')->references('id')->on('parametros')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
