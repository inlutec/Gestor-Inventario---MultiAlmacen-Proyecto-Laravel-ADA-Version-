<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabla de movimientos de material (entradas y salidas)
        Schema::create('material_movimientos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['entrada', 'salida'])->index();
            $table->string('numero_documento')->unique(); // Número de albarán/justificante
            $table->timestamp('fecha_movimiento')->index();
            $table->unsignedBigInteger('usuario_id')->nullable(); // Usuario que registra
            $table->string('origen')->nullable(); // Para entradas: proveedor, etc.
            $table->string('destino')->nullable(); // Para salidas: departamento, persona, etc.
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['borrador', 'pendiente_firma', 'firmado', 'cancelado'])->default('borrador')->index();
            $table->string('enlace_publico')->nullable()->unique(); // Token para enlace público
            $table->timestamp('enlace_expira')->nullable();
            $table->timestamps();
            
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });

        // Tabla de detalle de movimientos (líneas de material)
        Schema::create('material_movimiento_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movimiento_id');
            $table->unsignedBigInteger('entidad_id'); // Referencia a la entidad de tipo "pequeño_material"
            $table->string('descripcion'); // Descripción del material
            $table->integer('cantidad');
            $table->string('unidad')->default('unidad'); // unidad, kg, m, etc.
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->foreign('movimiento_id')->references('id')->on('material_movimientos')->onDelete('cascade');
            $table->foreign('entidad_id')->references('id')->on('entidades')->onDelete('restrict');
        });

        // Tabla de firmas
        Schema::create('material_firmas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movimiento_id');
            $table->enum('tipo_firmante', ['emisor', 'receptor'])->index();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('dni')->nullable();
            $table->text('firma_rubrica'); // Base64 de la rúbrica
            $table->string('ip_address')->nullable();
            $table->timestamp('fecha_firma');
            $table->json('datos_adicionales')->nullable(); // Metadata adicional
            $table->timestamps();
            
            $table->foreign('movimiento_id')->references('id')->on('material_movimientos')->onDelete('cascade');
        });

        // Índices adicionales para optimización
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->index('fecha_movimiento');
            $table->index(['tipo', 'estado']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_firmas');
        Schema::dropIfExists('material_movimiento_detalles');
        Schema::dropIfExists('material_movimientos');
    }
};
