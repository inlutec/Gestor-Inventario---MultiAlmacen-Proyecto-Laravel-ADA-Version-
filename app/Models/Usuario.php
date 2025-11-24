<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Departamento;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_acceso' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sesiones()
    {
        return $this->hasMany(Sesion::class, 'usuario_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function entidadesCreadas()
    {
        return $this->hasMany(Entidad::class, 'usuario_creador_id');
    }

    public function pedidosCreados()
    {
        return $this->hasMany(Pedido::class, 'usuario_creador_id');
    }

    public function planosCreados()
    {
        return $this->hasMany(Plano::class, 'usuario_creador_id');
    }

    public function registrosCambios()
    {
        return $this->hasMany(RegistroCambio::class, 'usuario_id');
    }

    // Relaciones con Proyectos
    public function proyectos()
    {
        return $this->belongsToMany(\App\Models\Proyectos\Proyecto::class, 'proyecto_miembro', 'usuario_id', 'proyecto_id')
            ->using(\App\Models\Proyectos\ProyectoMiembro::class)
            ->withPivot('rol', 'notificaciones')
            ->withTimestamps();
    }

    public function proyectosResponsable()
    {
        return $this->hasMany(\App\Models\Proyectos\Proyecto::class, 'responsable_id');
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(\App\Models\Proyectos\Tarea::class, 'asignado_a');
    }

    public function equipos()
    {
        return $this->belongsToMany(\App\Models\Proyectos\Equipo::class, 'equipo_miembro', 'usuario_id', 'equipo_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function notificacionesProyectos()
    {
        return $this->hasMany(\App\Models\Proyectos\Notificacion::class, 'usuario_id');
    }

    // Relación con almacenes
    public function almacenes()
    {
        return $this->belongsToMany(Departamento::class, 'user_almacen', 'user_id', 'departamento_id')
            ->where('es_almacen', 1)
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Verificar si el usuario tiene acceso a un almacén específico
     */
    public function tieneAccesoAlmacen($almacenId): bool
    {
        // Los administradores tienen acceso a todos los almacenes
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verificar si el almacén está en los almacenes asignados al usuario
        return $this->almacenes()->where('departamento_id', $almacenId)->exists();
    }
}
