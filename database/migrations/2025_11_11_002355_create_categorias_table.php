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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable(); // Ruta de la imagen
            $table->integer('orden')->default(0); // Para ordenar las categorías
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Agregar columna categoria_id a la tabla entidades
        Schema::table('entidades', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('tipo_entidad_id')->constrained('categorias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
        
        Schema::dropIfExists('categorias');
    }
};
