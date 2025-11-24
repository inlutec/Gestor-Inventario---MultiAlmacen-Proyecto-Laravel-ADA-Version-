<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('justificantes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['entrada', 'salida'])->comment('Tipo de movimiento');
            $table->string('nombre', 255)->comment('Nombre del justificante');
            $table->text('descripcion')->nullable()->comment('Descripción opcional');
            $table->boolean('activo')->default(true)->comment('Si está activo');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->timestamps();
            
            $table->index(['tipo', 'activo']);
        });
        
        // Insertar justificantes por defecto
        DB::table('justificantes')->insert([
            // Entradas
            ['tipo' => 'entrada', 'nombre' => 'Compra', 'descripcion' => 'Material adquirido mediante compra', 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'entrada', 'nombre' => 'Donación', 'descripcion' => 'Material recibido como donación', 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'entrada', 'nombre' => 'Devolución', 'descripcion' => 'Material devuelto al almacén', 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'entrada', 'nombre' => 'Préstamo devuelto', 'descripcion' => 'Material prestado que se devuelve', 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'entrada', 'nombre' => 'Reasignación interna', 'descripcion' => 'Traspaso entre departamentos', 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            
            // Salidas
            ['tipo' => 'salida', 'nombre' => 'Asignación a departamento', 'descripcion' => 'Material asignado a un departamento', 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'salida', 'nombre' => 'Préstamo temporal', 'descripcion' => 'Material prestado temporalmente', 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'salida', 'nombre' => 'Baja por obsolescencia', 'descripcion' => 'Material dado de baja por estar obsoleto', 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'salida', 'nombre' => 'Baja por deterioro', 'descripcion' => 'Material dado de baja por deterioro', 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'salida', 'nombre' => 'Cesión a otra entidad', 'descripcion' => 'Material cedido a otra organización', 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['tipo' => 'salida', 'nombre' => 'Reparación externa', 'descripcion' => 'Material enviado para reparación', 'activo' => true, 'orden' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('justificantes');
    }
};
