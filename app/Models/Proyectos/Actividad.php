<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Actividad extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'actividades';

    protected $fillable = [
        'activable_type',
        'activable_id',
        'usuario_id',
        'accion',
        'descripcion',
        'datos_antiguos',
        'datos_nuevos',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_antiguos' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function activable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class);
    }

    public function scopeRecientes($query, $limite = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limite);
    }

    public function scopeDelProyecto($query, $proyectoId)
    {
        return $query->where(function($q) use ($proyectoId) {
            $q->where('activable_type', Proyecto::class)
              ->where('activable_id', $proyectoId)
              ->orWhereHas('activable', function($q2) use ($proyectoId) {
                  if ($q2->getModel() instanceof Tarea) {
                      $q2->where('proyecto_id', $proyectoId);
                  }
              });
        });
    }
}
