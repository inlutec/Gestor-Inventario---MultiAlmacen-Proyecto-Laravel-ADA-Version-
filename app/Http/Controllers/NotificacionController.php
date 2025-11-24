<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificacionController extends Controller
{
    /**
     * Obtener las notificaciones de las últimas 24 horas del usuario autenticado
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        
        // Los administradores ven sus notificaciones personales Y las globales (usuario_id NULL)
        $query = Notificacion::where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc');
        
        if ($usuario->rol === 'admin' || $usuario->rol === 'superadmin') {
            // Ver notificaciones propias O globales
            $query->where(function($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)
                  ->orWhereNull('usuario_id');
            });
        } else {
            // Usuarios normales solo ven sus notificaciones
            $query->where('usuario_id', $usuario->id);
        }
        
        $notificaciones = $query->get();
        
        return response()->json($notificaciones);
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida($id)
    {
        $usuario = Auth::user();
        
        $query = Notificacion::where('id', $id);
        
        // Verificar que la notificación pertenece al usuario o es global
        if ($usuario->rol === 'admin' || $usuario->rol === 'superadmin') {
            $query->where(function($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)
                  ->orWhereNull('usuario_id');
            });
        } else {
            $query->where('usuario_id', $usuario->id);
        }
        
        $notificacion = $query->firstOrFail();
        
        $notificacion->leido = true;
        $notificacion->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas()
    {
        $usuario = Auth::user();
        
        $query = Notificacion::where('leido', false);
        
        if ($usuario->rol === 'admin' || $usuario->rol === 'superadmin') {
            // Marcar notificaciones propias y globales
            $query->where(function($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)
                  ->orWhereNull('usuario_id');
            });
        } else {
            $query->where('usuario_id', $usuario->id);
        }
        
        $query->update(['leido' => true]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Obtener el conteo de notificaciones no leídas
     */
    public function conteoNoLeidas()
    {
        $usuario = Auth::user();
        
        $query = Notificacion::where('leido', false)
            ->where('created_at', '>=', Carbon::now()->subDay());
        
        if ($usuario->rol === 'admin' || $usuario->rol === 'superadmin') {
            $query->where(function($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)
                  ->orWhereNull('usuario_id');
            });
        } else {
            $query->where('usuario_id', $usuario->id);
        }
        
        $count = $query->count();
        
        return response()->json(['count' => $count]);
    }
}
