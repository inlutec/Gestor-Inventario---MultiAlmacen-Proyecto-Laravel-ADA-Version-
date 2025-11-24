<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ubicacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'descripcion',
        'codigo_postal',
        'ciudad',
        'provincia',
        'telefono',
        'email',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_ubicacion')
            ->withPivot('principal')
            ->withTimestamps();
    }

    public function getDireccionCompletaAttribute()
    {
        $partes = array_filter([
            $this->direccion,
            $this->codigo_postal,
            $this->ciudad,
            $this->provincia,
        ]);

        return implode(', ', $partes);
    }

    public function tieneCooordenadas()
    {
        return !is_null($this->latitud) && !is_null($this->longitud);
    }

    public function getEnlaceMapsAttribute()
    {
        if (!$this->tieneCooordenadas()) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->latitud},{$this->longitud}";
    }
}
