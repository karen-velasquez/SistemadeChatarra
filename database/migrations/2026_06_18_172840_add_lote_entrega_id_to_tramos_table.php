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
        Schema::table('tramos', function (Blueprint $table) {
            $table->unsignedBigInteger('lote_entrega_id')->nullable()->after('lote_pago_id');
            $table->foreign('lote_entrega_id')->references('id')->on('lotes_entrega')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tramos', function (Blueprint $table) {
            $table->dropForeign(['lote_entrega_id']);
            $table->dropColumn('lote_entrega_id');
        });
    }
};
