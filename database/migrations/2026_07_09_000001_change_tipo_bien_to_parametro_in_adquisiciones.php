<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->dropColumn('tipo_bien');
        });
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_bien_id')->nullable()->after('descripcion');
            $table->foreign('tipo_bien_id')->references('id')->on('parametros')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->dropForeign(['tipo_bien_id']);
            $table->dropColumn('tipo_bien_id');
        });
        Schema::table('adquisiciones', function (Blueprint $table) {
            $table->string('tipo_bien', 100)->nullable()->after('descripcion');
        });
    }
};
