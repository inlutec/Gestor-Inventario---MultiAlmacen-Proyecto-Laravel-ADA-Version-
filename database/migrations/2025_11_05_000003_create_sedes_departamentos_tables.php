<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('clave')->unique();
            $table->timestamps();
        });

        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('clave');
            $table->timestamps();
            $table->unique(['sede_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamentos');
        Schema::dropIfExists('sedes');
    }
};
