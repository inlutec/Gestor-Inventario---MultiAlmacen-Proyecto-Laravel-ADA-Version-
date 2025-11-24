<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Proyecto extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'proyectos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'estado',
        'prioridad',
        'fecha_inicio',
        'fecha_fin_estimada',
        'fecha_fin_real',
        'progreso',
        'color',
        'responsable_id',
        'creado_por',
        'archivado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin_estimada' => 'date',
        'fecha_fin_real' => 'date',
        'progreso' => 'decimal:2',
        'archivado' => 'boolean',
    ];

    protected $appends = ['estado_badge', 'prioridad_badge'];

    // Relaciones
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'responsable_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'creado_por');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    public function hitos(): HasMany
    {
        return $this->hasMany(Hito::class);
    }

    public function ubicaciones(): BelongsToMany
    {
        return $this->belongsToMany(Ubicacion::class, 'proyecto_ubicacion')
            ->withPivot('principal')
            ->withTimestamps();
    }

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Usuario::class, 'proyecto_miembro', 'proyecto_id', 'usuario_id')
            ->withPivot('rol', 'notificaciones')
            ->withTimestamps();
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'proyecto_miembro')
            ->withPivot('rol', 'notificaciones')
            ->withTimestamps();
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentable');
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    public function actividades(): MorphMany
    {
        return $this->morphMany(Actividad::class, 'activable');
    }

    public function etiquetas(): MorphToMany
    {
        return $this->morphToMany(Etiqueta::class, 'etiquetable');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('archivado', false);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where(function($q) use ($usuarioId) {
            $q->where('responsable_id', $usuarioId)
              ->orWhereHas('miembros', function($q2) use ($usuarioId) {
                  $q2->where('usuario_id', $usuarioId);
              });
        });
    }

    // Accessors
    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'planificacion' => ['color' => 'bg-blue-100 text-blue-800', 'texto' => 'Planificación'],
            'en_progreso' => ['color' => 'bg-yellow-100 text-yellow-800', 'texto' => 'En Progreso'],
            'pausado' => ['color' => 'bg-gray-100 text-gray-800', 'texto' => 'Pausado'],
            'completado' => ['color' => 'bg-green-100 text-green-800', 'texto' => 'Completado'],
            'cancelado' => ['color' => 'bg-red-100 text-red-800', 'texto' => 'Cancelado'],
        ];

        return $badges[$this->estado] ?? $badges['planificacion'];
    }

    public function getPrioridadBadgeAttribute()
    {
        $badges = [
            'baja' => ['color' => 'bg-gray-100 text-gray-600', 'texto' => 'Baja', 'icono' => '↓'],
            'media' => ['color' => 'bg-blue-100 text-blue-600', 'texto' => 'Media', 'icono' => '→'],
            'alta' => ['color' => 'bg-orange-100 text-orange-600', 'texto' => 'Alta', 'icono' => '↑'],
            'critica' => ['color' => 'bg-red-100 text-red-600', 'texto' => 'Crítica', 'icono' => '⚠'],
        ];

        return $badges[$this->prioridad] ?? $badges['media'];
    }

    // Métodos auxiliares
    public function calcularProgreso()
    {
        $totalTareas = $this->tareas()->count();
        
        if ($totalTareas === 0) {
            return 0;
        }

        $tareasCompletadas = $this->tareas()->where('estado', 'completada')->count();
        
        return round(($tareasCompletadas / $totalTareas) * 100, 2);
    }

    public function actualizarProgreso()
    {
        $this->progreso = $this->calcularProgreso();
        $this->save();
    }

    public function esRetrasado()
    {
        if (!$this->fecha_fin_estimada) {
            return false;
        }

        return $this->estado !== 'completado' && 
               $this->estado !== 'cancelado' && 
               now()->gt($this->fecha_fin_estimada);
    }

    public function puedeEditar($usuario)
    {
        // El creador, responsable y miembros con rol gestor o coordinador pueden editar
        if ($this->creado_por === $usuario->id || $this->responsable_id === $usuario->id) {
            return true;
        }

        return $this->miembros()
            ->where('usuario_id', $usuario->id)
            ->whereIn('rol', ['gestor', 'coordinador'])
            ->exists();
    }

    public function puedeVer($usuario)
    {
        // Cualquier miembro puede ver
        return $this->creado_por === $usuario->id || 
               $this->responsable_id === $usuario->id ||
               $this->miembros()->where('usuario_id', $usuario->id)->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proyecto) {
            if (empty($proyecto->codigo)) {
                $proyecto->codigo = 'PRY-' . strtoupper(substr(md5(uniqid()), 0, 8));
            }
        });
    }
}
