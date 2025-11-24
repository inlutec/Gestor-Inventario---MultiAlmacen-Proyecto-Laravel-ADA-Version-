<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('planos_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->onDelete('cascade');
            $table->string('hostname', 255); // Hostname de la impresora
            $table->integer('x'); // pixel X relativo al tamaño original (3000)
            $table->integer('y'); // pixel Y relativo al tamaño original (2000)
            $table->timestamps();

            $table->unique(['plano_id', 'hostname']);
            $table->index('hostname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_ubicaciones');
    }
};
