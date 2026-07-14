<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->dropColumn('pais_origen');
        });
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->unsignedBigInteger('pais_origen_id')->nullable()->after('origen');
            $table->foreign('pais_origen_id')->references('id')->on('parametros')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->dropForeign(['pais_origen_id']);
            $table->dropColumn('pais_origen_id');
        });
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->string('pais_origen', 100)->nullable()->after('origen');
        });
    }
};
