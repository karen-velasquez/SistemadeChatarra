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
        Schema::table('prestamos_internos', function (Blueprint $table) {
            $table->string('metodo_pago', 50)->nullable()->after('concepto');
            $table->string('codigo_seguimiento')->nullable()->after('metodo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_internos', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'codigo_seguimiento']);
        });
    }
};
