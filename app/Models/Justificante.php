<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Justificante extends Model
{
    protected $table = 'justificantes';
    
    protected $fillable = [
        'tipo',
        'nombre',
        'descripcion',
        'activo',
        'orden',
    ];
    
    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
    
    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
    
    /**
     * Scope para obtener solo activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
    
    /**
     * Scope para ordenar
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
