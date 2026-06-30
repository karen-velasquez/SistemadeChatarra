<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedors', function (Blueprint $table) {
            $table->enum('tipo_proveedor', ['NACIONAL', 'INTERNACIONAL'])->nullable()->after('tipo_producto');
        });
    }

    public function down(): void
    {
        Schema::table('proveedors', function (Blueprint $table) {
            $table->dropColumn('tipo_proveedor');
        });
    }
};
