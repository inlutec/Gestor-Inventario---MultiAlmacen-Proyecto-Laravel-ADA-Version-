<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestSessionController extends Controller
{
    public function test(Request $request)
    {
        $sessionId = session()->getId();
        $hasSession = session()->has('_token');
        
        Log::info('Test Session', [
            'session_id' => $sessionId,
            'has_session' => $hasSession,
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all(),
        ]);
        
        $sesion = Sesion::where('id', $sessionId)
            ->where('activa', true)
            ->where('fecha_expiracion', '>', now())
            ->first();

        return response()->json([
            'session_id' => $sessionId,
            'has_session_token' => $hasSession,
            'found_in_db' => $sesion ? true : false,
            'sesion' => $sesion,
            'all_active_sessions' => Sesion::where('activa', true)->count(),
        ]);
    }
}
