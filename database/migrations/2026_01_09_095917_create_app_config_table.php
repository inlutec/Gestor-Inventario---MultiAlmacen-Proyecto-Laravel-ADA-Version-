<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_config', function (Blueprint $table) {
            $table->id();
            $table->string('app_domain')->default('http://10.66.129.108');
            $table->timestamps();
        });

        // Insertar configuración por defecto
        DB::table('app_config')->insert([
            'app_domain' => 'http://10.66.129.108',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_config');
    }
};
