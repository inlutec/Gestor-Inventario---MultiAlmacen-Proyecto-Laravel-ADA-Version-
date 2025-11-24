<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Etiqueta extends Model
{
    use HasFactory;

    protected $connection = 'proyectos';
    protected $table = 'etiquetas';

    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'descripcion',
    ];

    public function proyectos(): MorphToMany
    {
        return $this->morphedByMany(Proyecto::class, 'etiquetable');
    }

    public function tareas(): MorphToMany
    {
        return $this->morphedByMany(Tarea::class, 'etiquetable');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($etiqueta) {
            if (empty($etiqueta->slug)) {
                $etiqueta->slug = Str::slug($etiqueta->nombre);
            }
        });
    }
}
