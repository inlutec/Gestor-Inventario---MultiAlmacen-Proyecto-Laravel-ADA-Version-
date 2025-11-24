<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialFirma extends Model
{
    protected $table = 'material_firmas';

    protected $fillable = [
        'movimiento_id',
        'tipo_firmante',
        'nombre',
        'apellidos',
        'dni',
        'firma_rubrica',
        'tipo_firma',
        'cert_subject',
        'cert_issuer',
        'cert_serial',
        'cert_thumbprint',
        'raw_cert_pem',
        'algoritmo',
        'challenge_hash',
        'ip_address',
        'fecha_firma',
        'datos_adicionales',
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
        'datos_adicionales' => 'array',
    ];

    public function movimiento()
    {
        return $this->belongsTo(MaterialMovimiento::class, 'movimiento_id');
    }

    public function getNombreCompletoAttribute()
    {
        return trim($this->nombre . ' ' . $this->apellidos);
    }
}
