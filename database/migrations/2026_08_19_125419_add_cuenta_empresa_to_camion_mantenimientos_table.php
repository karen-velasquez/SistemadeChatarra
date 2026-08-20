<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('camion_mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_empresa_id')->nullable()->after('taller_id');
            $table->foreign('cuenta_empresa_id')->references('id')->on('cuentas_empresa')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camion_mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['cuenta_empresa_id']);
            $table->dropColumn('cuenta_empresa_id');
        });
    }
};
