<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tarea extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'tareas';

    protected $fillable = [
        'proyecto_id',
        'tarea_padre_id',
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_completada',
        'horas_estimadas',
        'horas_reales',
        'orden',
        'asignado_a',
        'creado_por',
        'completado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_completada' => 'datetime',
        'horas_estimadas' => 'decimal:2',
        'horas_reales' => 'decimal:2',
    ];

    protected $appends = ['estado_badge', 'prioridad_badge', 'es_retrasada'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function tareaPadre(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'tarea_padre_id');
    }

    public function subTareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'tarea_padre_id');
    }

    public function asignado(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'asignado_a');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'creado_por');
    }

    public function completador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'completado_por');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(TareaDependencia::class, 'tarea_id');
    }

    public function dependeDeEstasTareas(): HasMany
    {
        return $this->hasMany(TareaDependencia::class, 'depende_de_id');
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
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'en_progreso']);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeAsignadasA($query, $usuarioId)
    {
        return $query->where('asignado_a', $usuarioId);
    }

    public function scopeRetrasadas($query)
    {
        return $query->whereNotIn('estado', ['completada', 'cancelada'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now());
    }

    public function scopeSinAsignar($query)
    {
        return $query->whereNull('asignado_a');
    }

    // Accessors
    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'pendiente' => ['color' => 'bg-gray-100 text-gray-800', 'texto' => 'Pendiente'],
            'en_progreso' => ['color' => 'bg-blue-100 text-blue-800', 'texto' => 'En Progreso'],
            'revision' => ['color' => 'bg-purple-100 text-purple-800', 'texto' => 'En Revisión'],
            'completada' => ['color' => 'bg-green-100 text-green-800', 'texto' => 'Completada'],
            'bloqueada' => ['color' => 'bg-red-100 text-red-800', 'texto' => 'Bloqueada'],
            'cancelada' => ['color' => 'bg-gray-100 text-gray-600', 'texto' => 'Cancelada'],
        ];

        return $badges[$this->estado] ?? $badges['pendiente'];
    }

    public function getPrioridadBadgeAttribute()
    {
        $badges = [
            'baja' => ['color' => 'bg-gray-100 text-gray-600', 'texto' => 'Baja'],
            'media' => ['color' => 'bg-blue-100 text-blue-600', 'texto' => 'Media'],
            'alta' => ['color' => 'bg-orange-100 text-orange-600', 'texto' => 'Alta'],
            'critica' => ['color' => 'bg-red-100 text-red-600', 'texto' => 'Crítica'],
        ];

        return $badges[$this->prioridad] ?? $badges['media'];
    }

    public function getEsRetrasadaAttribute()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        return !in_array($this->estado, ['completada', 'cancelada']) && 
               now()->gt($this->fecha_vencimiento);
    }

    // Métodos auxiliares
    public function completar($usuarioId = null)
    {
        $this->estado = 'completada';
        $this->fecha_completada = now();
        $this->completado_por = $usuarioId;
        $this->save();

        // Actualizar progreso del proyecto
        $this->proyecto->actualizarProgreso();
    }

    public function reabrir()
    {
        $this->estado = 'en_progreso';
        $this->fecha_completada = null;
        $this->completado_por = null;
        $this->save();

        $this->proyecto->actualizarProgreso();
    }

    public function progresoChecklists()
    {
        $totalItems = 0;
        $itemsCompletados = 0;

        foreach ($this->checklists as $checklist) {
            $totalItems += $checklist->items()->count();
            $itemsCompletados += $checklist->items()->where('completado', true)->count();
        }

        if ($totalItems === 0) {
            return 0;
        }

        return round(($itemsCompletados / $totalItems) * 100, 2);
    }

    public function puedeBloquearse()
    {
        // Verificar si tiene dependencias no completadas
        return $this->dependencias()
            ->whereHas('dependeDe', function($q) {
                $q->where('estado', '!=', 'completada');
            })
            ->exists();
    }
}
