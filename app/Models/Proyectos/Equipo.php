<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'equipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'lider_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function lider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'lider_id');
    }

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Usuario::class, 'equipo_miembro', 'equipo_id', 'usuario_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_miembro')
            ->withPivot('rol', 'notificaciones')
            ->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
