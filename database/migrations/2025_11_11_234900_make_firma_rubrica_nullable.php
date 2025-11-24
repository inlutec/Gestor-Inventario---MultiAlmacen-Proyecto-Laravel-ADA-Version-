<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            $table->text('firma_rubrica')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            $table->text('firma_rubrica')->nullable(false)->change();
        });
    }
};
