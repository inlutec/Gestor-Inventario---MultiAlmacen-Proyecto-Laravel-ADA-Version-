<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'proyectos';
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla principal de proyectos
        Schema::connection('proyectos')->create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['planificacion', 'en_progreso', 'pausado', 'completado', 'cancelado'])->default('planificacion');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->date('fecha_fin_real')->nullable();
            $table->decimal('progreso', 5, 2)->default(0);
            $table->string('color', 7)->default('#006633');
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->boolean('archivado')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('estado');
            $table->index('prioridad');
            $table->index('responsable_id');
            $table->index('archivado');
        });

        // Tabla de ubicaciones/sitios
        Schema::connection('proyectos')->create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('direccion')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('ciudad')->nullable();
            $table->string('provincia')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['ciudad', 'provincia']);
        });

        // Relación proyectos con ubicaciones (muchos a muchos)
        Schema::connection('proyectos')->create('proyecto_ubicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->onDelete('cascade');
            $table->boolean('principal')->default(false);
            $table->timestamps();
            
            $table->unique(['proyecto_id', 'ubicacion_id']);
        });

        // Tabla de tareas
        Schema::connection('proyectos')->create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('tarea_padre_id')->nullable()->constrained('tareas')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'revision', 'completada', 'bloqueada', 'cancelada'])->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_completada')->nullable();
            $table->decimal('horas_estimadas', 8, 2)->nullable();
            $table->decimal('horas_reales', 8, 2)->nullable();
            $table->integer('orden')->default(0);
            $table->unsignedBigInteger('asignado_a')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('completado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['proyecto_id', 'estado']);
            $table->index('asignado_a');
            $table->index('fecha_vencimiento');
        });

        // Tabla de comentarios
        Schema::connection('proyectos')->create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->string('comentable_type');
            $table->unsignedBigInteger('comentable_id');
            $table->text('contenido');
            $table->unsignedBigInteger('usuario_id');
            $table->foreignId('comentario_padre_id')->nullable()->constrained('comentarios')->onDelete('cascade');
            $table->boolean('editado')->default(false);
            $table->timestamp('editado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['comentable_type', 'comentable_id'], 'comentables_idx');
            $table->index('usuario_id');
        });

        // Tabla de adjuntos/archivos
        Schema::connection('proyectos')->create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->string('adjuntable_type');
            $table->unsignedBigInteger('adjuntable_id');
            $table->string('nombre_original');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->string('tipo_mime');
            $table->unsignedBigInteger('tamano'); // bytes
            $table->unsignedBigInteger('subido_por');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['adjuntable_type', 'adjuntable_id'], 'adjuntables_idx');
            $table->index('subido_por');
        });

        // Tabla de etiquetas
        Schema::connection('proyectos')->create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#6B7280');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Relación polimórfica para etiquetas
        Schema::connection('proyectos')->create('etiquetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etiqueta_id')->constrained('etiquetas')->onDelete('cascade');
            $table->string('etiquetable_type');
            $table->unsignedBigInteger('etiquetable_id');
            $table->timestamps();
            
            $table->unique(['etiqueta_id', 'etiquetable_id', 'etiquetable_type'], 'etiquetables_unique');
            $table->index(['etiquetable_type', 'etiquetable_id'], 'etiquetables_idx');
        });

        // Tabla de equipos/grupos
        Schema::connection('proyectos')->create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->unsignedBigInteger('lider_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Miembros de equipos
        Schema::connection('proyectos')->create('equipo_miembro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->unsignedBigInteger('usuario_id');
            $table->enum('rol', ['miembro', 'coordinador', 'lider'])->default('miembro');
            $table->timestamps();
            
            $table->unique(['equipo_id', 'usuario_id']);
            $table->index('usuario_id');
        });

        // Miembros del proyecto con roles
        Schema::connection('proyectos')->create('proyecto_miembro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->onDelete('cascade');
            $table->enum('rol', ['observador', 'colaborador', 'coordinador', 'gestor'])->default('colaborador');
            $table->boolean('notificaciones')->default(true);
            $table->timestamps();
            
            $table->index(['proyecto_id', 'usuario_id']);
            $table->index(['proyecto_id', 'equipo_id']);
        });

        // Tabla de actividad/auditoría
        Schema::connection('proyectos')->create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('activable_type');
            $table->unsignedBigInteger('activable_id');
            $table->unsignedBigInteger('usuario_id');
            $table->string('accion'); // created, updated, deleted, commented, etc.
            $table->text('descripcion')->nullable();
            $table->json('datos_antiguos')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['activable_type', 'activable_id'], 'activables_idx');
            $table->index('usuario_id');
            $table->index('created_at');
        });

        // Tabla de notificaciones
        Schema::connection('proyectos')->create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo'); // tarea_asignada, comentario_nuevo, etc.
            $table->string('notificable_type');
            $table->unsignedBigInteger('notificable_id');
            $table->text('mensaje');
            $table->json('datos')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();
            
            $table->index(['usuario_id', 'leida']);
            $table->index('created_at');
            $table->index(['notificable_type', 'notificable_id'], 'notificables_idx');
        });

        // Tabla de hitos/milestones
        Schema::connection('proyectos')->create('hitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->date('fecha_objetivo');
            $table->date('fecha_completada')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completado', 'retrasado'])->default('pendiente');
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['proyecto_id', 'estado']);
        });

        // Tabla de checklists
        Schema::connection('proyectos')->create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->onDelete('cascade');
            $table->string('titulo');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Items de checklist
        Schema::connection('proyectos')->create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('checklists')->onDelete('cascade');
            $table->text('descripcion');
            $table->boolean('completado')->default(false);
            $table->unsignedBigInteger('completado_por')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->index(['checklist_id', 'completado']);
        });

        // Tabla de dependencias entre tareas
        Schema::connection('proyectos')->create('tarea_dependencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->onDelete('cascade');
            $table->foreignId('depende_de_id')->constrained('tareas')->onDelete('cascade');
            $table->enum('tipo', ['fin_inicio', 'inicio_inicio', 'fin_fin', 'inicio_fin'])->default('fin_inicio');
            $table->timestamps();
            
            $table->unique(['tarea_id', 'depende_de_id']);
        });

        // Tabla de plantillas de proyecto
        Schema::connection('proyectos')->create('plantillas_proyecto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->json('configuracion'); // Estructura de tareas, etiquetas, etc.
            $table->unsignedBigInteger('creado_por');
            $table->boolean('publica')->default(false);
            $table->timestamps();
            
            $table->index('creado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('proyectos')->dropIfExists('tarea_dependencias');
        Schema::connection('proyectos')->dropIfExists('checklist_items');
        Schema::connection('proyectos')->dropIfExists('checklists');
        Schema::connection('proyectos')->dropIfExists('hitos');
        Schema::connection('proyectos')->dropIfExists('notificaciones');
        Schema::connection('proyectos')->dropIfExists('actividades');
        Schema::connection('proyectos')->dropIfExists('proyecto_miembro');
        Schema::connection('proyectos')->dropIfExists('equipo_miembro');
        Schema::connection('proyectos')->dropIfExists('equipos');
        Schema::connection('proyectos')->dropIfExists('etiquetables');
        Schema::connection('proyectos')->dropIfExists('etiquetas');
        Schema::connection('proyectos')->dropIfExists('adjuntos');
        Schema::connection('proyectos')->dropIfExists('comentarios');
        Schema::connection('proyectos')->dropIfExists('tareas');
        Schema::connection('proyectos')->dropIfExists('proyecto_ubicacion');
        Schema::connection('proyectos')->dropIfExists('ubicaciones');
        Schema::connection('proyectos')->dropIfExists('proyectos');
        Schema::connection('proyectos')->dropIfExists('plantillas_proyecto');
    }
};
