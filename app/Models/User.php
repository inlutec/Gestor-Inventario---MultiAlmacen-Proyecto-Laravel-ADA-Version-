<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relación con almacenes (departamentos)
    public function almacenes()
    {
        return $this->belongsToMany(Departamento::class, 'user_almacen');
    }

    // Método para verificar si el usuario tiene acceso a un almacén específico
    public function tieneAccesoAlmacen($departamentoId)
    {
        return $this->almacenes()->where('departamento_id', $departamentoId)->exists();
    }

    // Método para obtener los IDs de los almacenes del usuario
    public function getAlmacenIdsAttribute()
    {
        return $this->almacenes->pluck('id');
    }

    // Métodos para verificar roles
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isGestor()
    {
        return $this->role === 'gestor';
    }

    public function isAdminOrGestor()
    {
        return $this->isAdmin() || $this->isGestor();
    }
}