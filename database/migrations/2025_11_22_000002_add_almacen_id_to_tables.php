<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna almacen_id a las tablas necesarias
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('id');
            $table->foreign('almacen_id')->references('id')->on('departamentos')->onDelete('set null');
        });

        Schema::table('material_peticiones', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('id');
            $table->foreign('almacen_id')->references('id')->on('departamentos')->onDelete('set null');
        });

        Schema::table('solicitudes_reposicion', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('id');
            $table->foreign('almacen_id')->references('id')->on('departamentos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->dropForeign(['material_movimientos_almacen_id_foreign']);
            $table->dropColumn('almacen_id');
        });

        Schema::table('material_peticiones', function (Blueprint $table) {
            $table->dropForeign(['material_peticiones_almacen_id_foreign']);
            $table->dropColumn('almacen_id');
        });

        Schema::table('solicitudes_reposicion', function (Blueprint $table) {
            $table->dropForeign(['solicitudes_reposicion_almacen_id_foreign']);
            $table->dropColumn('almacen_id');
        });
    }
};