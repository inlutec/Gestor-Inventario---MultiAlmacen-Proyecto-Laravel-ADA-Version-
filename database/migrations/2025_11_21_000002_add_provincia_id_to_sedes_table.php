<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->foreignId('provincia_id')->nullable()->constrained('provincias')->cascadeOnDelete();
            $table->boolean('es_almacen_central')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->dropForeign(['sedes_provincia_id_foreign']);
            $table->dropColumn('es_almacen_central');
        });
    }
};