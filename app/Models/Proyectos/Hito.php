<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hito extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'hitos';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
        'fecha_objetivo',
        'fecha_completada',
        'estado',
        'orden',
    ];

    protected $casts = [
        'fecha_objetivo' => 'date',
        'fecha_completada' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function completar()
    {
        $this->estado = 'completado';
        $this->fecha_completada = now();
        $this->save();
    }

    public function esRetrasado()
    {
        return $this->estado !== 'completado' && now()->gt($this->fecha_objetivo);
    }

    public function scopeRetrasados($query)
    {
        return $query->where('estado', '!=', 'completado')
            ->where('fecha_objetivo', '<', now());
    }
}
