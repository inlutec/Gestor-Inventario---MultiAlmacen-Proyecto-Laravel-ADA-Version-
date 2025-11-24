<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('justificante_id')->nullable()->after('tipo');
            $table->foreign('justificante_id')->references('id')->on('justificantes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->dropForeign(['justificante_id']);
            $table->dropColumn('justificante_id');
        });
    }
};
