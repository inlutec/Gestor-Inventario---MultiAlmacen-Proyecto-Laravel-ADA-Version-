<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();
        
        // Verificar intentos de login
        $lockoutMinutes = config('auth.lockout_time', 15);
        $maxAttempts = config('auth.max_attempts', 5);
        
        $intentos = DB::table('intentos_login')
            ->where('ip', $ip)
            ->where('fecha', '>', now()->subMinutes($lockoutMinutes))
            ->count();

        if ($intentos >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos de inicio de sesión. Por favor, espera {$lockoutMinutes} minutos."
            ], 429);
        }

        // Verificar credenciales
        $usuario = Usuario::where('email', $request->email)
            ->where('activo', true)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            // Registrar intento fallido
            DB::table('intentos_login')->insert([
                'ip' => $ip,
                'fecha' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Login exitoso - crear token y sesión
        $token = $usuario->createToken('auth_token')->plainTextToken;
        // IMPORTANTE: También hacer login con el guard web para que funcione la autenticación de sesión
        Auth::login($usuario);

        
        $sessionId = session()->getId();
        $expiracion = now()->addHours(8);

        Sesion::create([
            'id' => $sessionId,
            'usuario_id' => $usuario->id,
            'ip' => $ip,
            'fecha_expiracion' => $expiracion,
            'activa' => true,
        ]);

        // Limpiar intentos fallidos
        DB::table('intentos_login')->where('ip', $ip)->delete();

        // Actualizar último acceso
        $usuario->update(['ultimo_acceso' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'email' => $usuario->email,
                    'rol' => $usuario->rol,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $usuario = $request->user();
        
        if ($usuario) {
            // Desactivar sesión
            Sesion::where('id', session()->getId())->update(['activa' => false]);
            
            // Revocar tokens
            $usuario->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function me(Request $request)
    {
        $usuario = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'ultimo_acceso' => $usuario->ultimo_acceso,
            ],
        ]);
    }

    public function checkSession(Request $request)
    {
        $sessionId = session()->getId();
        $sesion = Sesion::where('id', $sessionId)
            ->where('activa', true)
            ->where('fecha_expiracion', '>', now())
            ->first();

        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión expirada',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión activa',
        ]);
    }
}
