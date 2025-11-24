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
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->timestamp('fecha_entrega')->nullable()->after('estado');
            $table->unsignedBigInteger('entregado_por')->nullable()->after('fecha_entrega');
            
            $table->foreign('entregado_por')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->dropForeign(['entregado_por']);
            $table->dropColumn(['fecha_entrega', 'entregado_por']);
        });
    }
};
