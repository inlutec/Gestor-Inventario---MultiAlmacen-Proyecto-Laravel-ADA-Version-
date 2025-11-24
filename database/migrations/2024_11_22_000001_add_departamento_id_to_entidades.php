<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('plano_id');
            $table->foreignId('departamento_id')->nullable()->after('sede_id');
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('set null');
            $table->foreign('departamento_id')->references('id')->on('departamentos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropForeign(['entidades_departamento_id_foreign']);
            $table->dropForeign(['entidades_sede_id_foreign']);
            $table->dropColumn('departamento_id');
            $table->dropColumn('sede_id');
        });
    }
};