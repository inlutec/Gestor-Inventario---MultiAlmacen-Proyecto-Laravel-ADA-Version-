<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $fillable = ['sede_id', 'nombre', 'clave', 'es_almacen_central'];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function provincia()
    {
        return $this->hasOneThrough(Provincia::class, Sede::class, 'id', 'id', 'sede_id', 'provincia_id');
    }

    public function esAlmacenCentral()
    {
        return $this->es_almacen_central;
    }

    // Relación con usuarios
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'user_almacen');
    }
}
