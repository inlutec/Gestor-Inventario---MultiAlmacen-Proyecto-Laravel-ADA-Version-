<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'checklist_items';

    protected $fillable = [
        'checklist_id',
        'descripcion',
        'completado',
        'completado_por',
        'completado_at',
        'orden',
    ];

    protected $casts = [
        'completado' => 'boolean',
        'completado_at' => 'datetime',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function completador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'completado_por');
    }

    public function marcarComoCompletado($usuarioId = null)
    {
        $this->completado = true;
        $this->completado_por = $usuarioId;
        $this->completado_at = now();
        $this->save();
    }

    public function desmarcar()
    {
        $this->completado = false;
        $this->completado_por = null;
        $this->completado_at = null;
        $this->save();
    }
}
