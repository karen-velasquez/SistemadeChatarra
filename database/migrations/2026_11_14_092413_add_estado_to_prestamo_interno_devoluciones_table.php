<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamo_interno_devoluciones', function (Blueprint $table) {
            $table->string('estado')->default('activa')->after('voucher');
        });
    }

    public function down(): void
    {
        Schema::table('prestamo_interno_devoluciones', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
