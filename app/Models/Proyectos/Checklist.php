<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'checklists';

    protected $fillable = [
        'tarea_id',
        'titulo',
        'orden',
    ];

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('orden');
    }

    public function progreso()
    {
        $total = $this->items()->count();
        
        if ($total === 0) {
            return 0;
        }

        $completados = $this->items()->where('completado', true)->count();
        
        return round(($completados / $total) * 100, 2);
    }

    public function estaCompleta()
    {
        return $this->progreso() === 100.0;
    }
}
