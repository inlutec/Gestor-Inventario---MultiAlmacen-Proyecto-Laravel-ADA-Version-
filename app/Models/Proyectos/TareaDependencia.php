<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TareaDependencia extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'tarea_dependencias';

    protected $fillable = [
        'tarea_id',
        'depende_de_id',
        'tipo',
    ];

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'tarea_id');
    }

    public function dependeDe(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'depende_de_id');
    }
}
