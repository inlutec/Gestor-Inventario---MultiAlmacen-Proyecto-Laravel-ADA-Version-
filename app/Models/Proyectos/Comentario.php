<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comentario extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'comentarios';

    protected $fillable = [
        'comentable_type',
        'comentable_id',
        'contenido',
        'usuario_id',
        'comentario_padre_id',
        'editado',
        'editado_at',
    ];

    protected $casts = [
        'editado' => 'boolean',
        'editado_at' => 'datetime',
    ];

    public function comentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class);
    }

    public function padre()
    {
        return $this->belongsTo(Comentario::class, 'comentario_padre_id');
    }

    public function respuestas()
    {
        return $this->hasMany(Comentario::class, 'comentario_padre_id');
    }

    public function marcarComoEditado()
    {
        $this->editado = true;
        $this->editado_at = now();
        $this->save();
    }
}
