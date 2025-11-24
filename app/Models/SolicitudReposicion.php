<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudReposicion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_reposicion';

    protected $fillable = [
        'usuario_id',
        'entidad_id',
        'cantidad_solicitada',
        'estado',
        'fecha_solicitud',
        'fecha_notificacion',
        'prevision_llegada',
        'notas',
        'telefono_solicitante',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_notificacion' => 'datetime',
        'prevision_llegada' => 'date',
    ];

    /**
     * Relación con el usuario que solicitó
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con el material solicitado
     */
    public function material()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para solicitudes por material
     */
    public function scopePorMaterial($query, $entidadId)
    {
        return $query->where('entidad_id', $entidadId);
    }
}
