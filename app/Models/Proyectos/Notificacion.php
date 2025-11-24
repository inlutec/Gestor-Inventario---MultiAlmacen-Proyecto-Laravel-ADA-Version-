<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'notificable_type',
        'notificable_id',
        'mensaje',
        'datos',
        'leida',
        'leida_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'leida' => 'boolean',
        'leida_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class);
    }

    public function notificable(): MorphTo
    {
        return $this->morphTo();
    }

    public function marcarComoLeida()
    {
        if (!$this->leida) {
            $this->leida = true;
            $this->leida_at = now();
            $this->save();
        }
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
