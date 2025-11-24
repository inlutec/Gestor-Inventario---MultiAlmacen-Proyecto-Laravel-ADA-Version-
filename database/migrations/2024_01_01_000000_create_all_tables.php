<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->enum('rol', ['admin', 'usuario'])->default('usuario');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('rol');
        });

        Schema::create('sesiones', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('ip', 45);
            $table->timestamp('fecha_expiracion');
            $table->boolean('activa')->default(true);
            $table->timestamps();
            
            $table->index('usuario_id');
            $table->index('activa');
        });

        Schema::create('intentos_login', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->timestamp('fecha');
            
            $table->index(['ip', 'fecha']);
        });

        Schema::create('tipos_entidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('clave', 50)->unique();
            $table->string('icono', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('campos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_entidad_id')->constrained('tipos_entidad')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('clave', 50);
            $table->enum('tipo_dato', ['text', 'number', 'select', 'textarea', 'date', 'checkbox'])->default('text');
            $table->json('opciones')->nullable();
            $table->boolean('obligatorio')->default(false);
            $table->boolean('mostrar_en_tabla')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->index('tipo_entidad_id');
        });

        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->longText('imagen');
            $table->text('descripcion')->nullable();
            $table->foreignId('usuario_creador_id')->constrained('usuarios')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_entidad_id')->constrained('tipos_entidad')->onDelete('cascade');
            $table->json('datos');
            $table->foreignId('plano_id')->nullable()->constrained('planos')->onDelete('set null');
            $table->decimal('posicion_x', 5, 2)->nullable();
            $table->decimal('posicion_y', 5, 2)->nullable();
            $table->json('fotos')->nullable();
            $table->foreignId('usuario_creador_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();
            
            $table->index('tipo_entidad_id');
            $table->index('plano_id');
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido', 50)->unique();
            $table->date('fecha_pedido');
            $table->date('fecha_recepcion')->nullable();
            $table->enum('estado', ['pendiente', 'recibido', 'cancelado'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->string('albaran_foto', 255)->nullable();
            $table->json('datos')->nullable();
            $table->foreignId('impresora_id')->nullable()->constrained('entidades')->onDelete('set null');
            $table->foreignId('usuario_creador_id')->constrained('usuarios')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('estado');
            $table->index('numero_pedido');
        });

        Schema::create('detalles_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('entidad_id')->constrained('entidades')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index('pedido_id');
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->enum('tipo', ['info', 'warning', 'error'])->default('info');
            $table->boolean('leido')->default(false);
            $table->timestamps();
            
            $table->index(['usuario_id', 'leido']);
        });

        Schema::create('registro_cambios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('tipo_entidad', 50);
            $table->enum('accion', ['crear', 'modificar', 'eliminar', 'ubicar', 'consumir', 'desubicar']);
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            
            $table->index(['tipo_entidad', 'entidad_id']);
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_cambios');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('detalles_pedido');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('entidades');
        Schema::dropIfExists('planos');
        Schema::dropIfExists('campos');
        Schema::dropIfExists('tipos_entidad');
        Schema::dropIfExists('intentos_login');
        Schema::dropIfExists('sesiones');
        Schema::dropIfExists('usuarios');
    }
};
