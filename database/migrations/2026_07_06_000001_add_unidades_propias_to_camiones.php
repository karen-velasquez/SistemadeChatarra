<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            $table->boolean('es_propio')->default(false)->after('estado');
            $table->unsignedInteger('kilometraje_actual')->default(0)->after('es_propio');
        });
    }

    public function down(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            $table->dropColumn(['es_propio', 'kilometraje_actual']);
        });
    }
};
