<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoUbicacion extends Model
{
    use HasFactory;

    protected $table = 'planos_ubicaciones';

    protected $fillable = [
        'plano_id',
        'hostname',
        'x',
        'y',
    ];

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }
}
