<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entidad extends Model
{
    use HasFactory;

    protected $table = 'entidades';

    protected $fillable = [
        'tipo_entidad_id',
        'categoria_id',
        'foto',
        'visible_publico',
        'datos',
        'custom_fields',
        'plano_id',
        'posicion_x',
        'posicion_y',
        'fotos',
        'usuario_creador_id',
        // Campos específicos de impresoras
        'referencia',
        'numero_serie',
        'ip',
        'marca',
        'modelo',
        'division',
        'planta',
        'ubicacion',
        'host_checkmk',
        'sede',
        'departamento',
    ];

    protected $casts = [
        'datos' => 'array',
        'custom_fields' => 'array',
        'fotos' => 'array',
        'visible_publico' => 'boolean',
        'posicion_x' => 'decimal:2',
        'posicion_y' => 'decimal:2',
    ];

    public function tipoEntidad()
    {
        return $this->belongsTo(TipoEntidad::class, 'tipo_entidad_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento', 'nombre');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_creador_id');
    }

    public function detallesPedido()
    {
        return $this->hasMany(DetallePedido::class, 'entidad_id');
    }

    public function ubicaciones()
    {
        return $this->hasMany(EntidadUbicacion::class, 'entidad_id');
    }

    public function ubicacionAlmacen($almacenId)
    {
        return $this->ubicaciones()->where('almacen_id', $almacenId)->first();
    }

    // Scope para obtener solo entidades activas
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // Scope para filtrar por almacenes (departamentos)
    public function scopePorAlmacenes($query, $almacenIds)
    {
        if (empty($almacenIds)) {
            return $query;
        }
        
        // Obtener los nombres de los departamentos usando los IDs
        $departamentos = \DB::table('departamentos')
            ->whereIn('id', $almacenIds)
            ->pluck('nombre');
            
        if ($departamentos->isNotEmpty()) {
            return $query->whereIn('departamento', $departamentos);
        }
        
        return $query;
    }
}
