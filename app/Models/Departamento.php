<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $fillable = ['sede_id', 'nombre', 'clave', 'es_almacen'];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function provincia()
    {
        return $this->hasOneThrough(Provincia::class, Sede::class, 'id', 'id', 'sede_id', 'provincia_id');
    }

    public function esAlmacen()
    {
        return $this->es_almacen;
    }

    // Relación con usuarios
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'user_almacen', 'departamento_id', 'user_id');
    }
}
