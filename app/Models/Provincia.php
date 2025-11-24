<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'clave', 'activo'];

    public function sedes()
    {
        return $this->hasMany(Sede::class);
    }
}