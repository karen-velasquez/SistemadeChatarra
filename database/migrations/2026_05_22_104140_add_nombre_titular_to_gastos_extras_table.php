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
        Schema::table('gastos_extras', function (Blueprint $table) {
            $table->string('nombre_titular', 150)->nullable()->after('metodo_pago')->comment('Nombre de la persona titular que realizó el pago del voucher/boucher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos_extras', function (Blueprint $table) {
            $table->dropColumn('nombre_titular');

        });
    }
};