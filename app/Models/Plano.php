<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use HasFactory;

    protected $table = 'planos';

    protected $fillable = [
        'nombre',
        'imagen',
        'sede',
        'descripcion',
        'usuario_creador_id',
    ];

    public function entidades()
    {
        return $this->hasMany(Entidad::class, 'plano_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_creador_id');
    }
}
