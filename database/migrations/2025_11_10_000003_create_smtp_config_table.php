<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_config', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->integer('port')->default(587);
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Encriptado
            $table->string('from_address');
            $table->string('from_name')->default('Gestión de Material');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultima_prueba')->nullable();
            $table->text('resultado_prueba')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_config');
    }
};
