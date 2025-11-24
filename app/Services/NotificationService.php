<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\Usuario;
use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Aplicar configuración SMTP antes de enviar emails
     */
    private function aplicarConfigSmtp()
    {
        try {
            $config = SmtpConfig::getActive();
            if ($config) {
                $config->apply();
                
                // Limpiar instancias del mailer para forzar recreación
                app()->forgetInstance('mail.manager');
                app()->forgetInstance(\Illuminate\Contracts\Mail\Mailer::class);
                app()->forgetInstance(\Illuminate\Contracts\Mail\Factory::class);
                
                Log::info('Configuración SMTP aplicada', [
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username') ?: 'sin autenticación',
                    'from' => config('mail.from.address')
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo aplicar configuración SMTP: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de petición creada
     */
    public function notificarPeticionCreada($peticion)
    {
        $evento = 'peticion_creada';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        // Obtener administradores
        $admins = Usuario::where('rol', 'admin')->get();
        
        // Buscar usuario por email si existe
        $usuario = null;
        if ($peticion->email_solicitante) {
            $usuario = Usuario::where('email', $peticion->email_solicitante)->first();
        }
        
        // Obtener destinatarios según configuración
        $destinatarios = NotificationSetting::obtenerDestinatarios($evento, $usuario, $admins);
        
        // Si el pedido tiene email_solicitante y debe notificar usuarios, agregarlo aunque no esté registrado
        if (NotificationSetting::debeNotificarUsuario($evento) && 
            $peticion->email_solicitante && 
            filter_var($peticion->email_solicitante, FILTER_VALIDATE_EMAIL)) {
            $destinatarios[] = $peticion->email_solicitante;
        }
        
        // Eliminar duplicados
        $destinatarios = array_unique($destinatarios);
        
        if (empty($destinatarios)) {
            return;
        }

        $urlPeticion = config('app.url') . '/gestionmaterial/#/pedidos';
        
        foreach ($destinatarios as $email) {
            $esAdmin = Usuario::where('email', $email)->where('rol', 'admin')->exists();
            $nombreUsuario = $peticion->usuario_solicitante ?? 'Usuario';
            
            // Si es un usuario registrado, usar su nombre de la BD
            $usuarioRegistrado = Usuario::where('email', $email)->first();
            if ($usuarioRegistrado) {
                $nombreUsuario = $usuarioRegistrado->nombre;
            }
            
            try {
                Mail::send('emails.peticion-creada', [
                    'titulo' => 'Nueva Petición de Material',
                    'nombreUsuario' => $nombreUsuario,
                    'peticion' => $peticion,
                    'urlPeticion' => $urlPeticion,
                    'esAdmin' => $esAdmin
                ], function ($message) use ($email) {
                    $message->to($email)
                           ->subject('Nueva Petición de Material - ADA Córdoba');
                });
                
                Log::info("Notificación enviada: $evento a $email");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación $evento a $email: " . $e->getMessage());
            }
        }
    }

    /**
     * Enviar notificación de petición aprobada
     */
    public function notificarPeticionAprobada($peticion, $aprobadoPor)
    {
        $evento = 'peticion_aprobada';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarUsuario($evento)) {
            return;
        }
        
        // Determinar email del destinatario
        $emailDestinatario = null;
        $nombreDestinatario = 'Usuario';
        
        if ($peticion->email_solicitante && filter_var($peticion->email_solicitante, FILTER_VALIDATE_EMAIL)) {
            $emailDestinatario = $peticion->email_solicitante;
            $nombreDestinatario = $peticion->usuario_solicitante ?? 'Usuario';
        } elseif ($peticion->usuarioCreador && $peticion->usuarioCreador->email) {
            $emailDestinatario = $peticion->usuarioCreador->email;
            $nombreDestinatario = $peticion->usuarioCreador->nombre;
        }
        
        if (!$emailDestinatario) {
            return;
        }

        $urlPeticion = config('app.url') . '/gestionmaterial/#/pedidos';
        
        try {
            Mail::send('emails.peticion-aprobada', [
                'titulo' => 'Petición Aprobada',
                'nombreUsuario' => $nombreDestinatario,
                'peticion' => $peticion,
                'aprobadoPor' => $aprobadoPor,
                'urlPeticion' => $urlPeticion
            ], function ($message) use ($emailDestinatario) {
                $message->to($emailDestinatario)
                       ->subject('Tu Petición ha sido Aprobada - ADA Córdoba');
            });
            
            Log::info("Notificación enviada: $evento a {$emailDestinatario}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación $evento: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de petición denegada
     */
    public function notificarPeticionDenegada($peticion, $denegadoPor, $motivo = null)
    {
        $evento = 'peticion_denegada';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarUsuario($evento)) {
            return;
        }
        
        // Determinar email del destinatario
        $emailDestinatario = null;
        $nombreDestinatario = 'Usuario';
        
        if ($peticion->email_solicitante && filter_var($peticion->email_solicitante, FILTER_VALIDATE_EMAIL)) {
            $emailDestinatario = $peticion->email_solicitante;
            $nombreDestinatario = $peticion->usuario_solicitante ?? 'Usuario';
        } elseif ($peticion->usuarioCreador && $peticion->usuarioCreador->email) {
            $emailDestinatario = $peticion->usuarioCreador->email;
            $nombreDestinatario = $peticion->usuarioCreador->nombre;
        }
        
        if (!$emailDestinatario) {
            return;
        }

        $urlPeticion = config('app.url') . '/gestionmaterial/#/pedidos';
        
        try {
            Mail::send('emails.peticion-denegada', [
                'titulo' => 'Petición Denegada',
                'nombreUsuario' => $nombreDestinatario,
                'peticion' => $peticion,
                'denegadoPor' => $denegadoPor,
                'motivo' => $motivo,
                'urlPeticion' => $urlPeticion
            ], function ($message) use ($emailDestinatario) {
                $message->to($emailDestinatario)
                       ->subject('Petición Denegada - ADA Córdoba');
            });
            
            Log::info("Notificación enviada: $evento a {$emailDestinatario}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación $evento: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de movimiento creado
     */
    public function notificarMovimientoCreado($movimiento)
    {
        $evento = 'movimiento_creado';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarAdmin($evento)) {
            return;
        }

        // Cargar relaciones necesarias
        $movimiento->load(['detalles', 'usuario']);

        $admins = Usuario::where('rol', 'admin')->get();
        $urlMovimiento = config('app.url') . '/gestionmaterial/#/historial';
        
        foreach ($admins as $admin) {
            if (!$admin->email) continue;
            
            try {
                Mail::send('emails.movimiento-creado', [
                    'titulo' => 'Nuevo Movimiento de Material',
                    'nombreUsuario' => $admin->nombre ?? 'Administrador',
                    'movimiento' => $movimiento,
                    'urlMovimiento' => $urlMovimiento
                ], function ($message) use ($admin) {
                    $message->to($admin->email)
                           ->subject('Nuevo Movimiento de Material - ADA Córdoba');
                });
                
                Log::info("Notificación enviada: $evento a {$admin->email}");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación $evento a {$admin->email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Enviar notificación de material entregado
     */
    public function notificarMovimientoEntregado($movimiento)
    {
        $evento = 'movimiento_entregado';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        // Cargar relaciones necesarias
        $movimiento->load(['detalles', 'usuario', 'usuarioEntrega']);
        
        if (!NotificationSetting::debeNotificarUsuario($evento) || !$movimiento->usuario || !$movimiento->usuario->email) {
            return;
        }

        $urlMovimiento = config('app.url') . '/gestionmaterial/#/historial';
        
        try {
            Mail::send('emails.movimiento-entregado', [
                'titulo' => 'Material Entregado',
                'nombreUsuario' => $movimiento->usuario->nombre ?? 'Usuario',
                'movimiento' => $movimiento,
                'urlMovimiento' => $urlMovimiento
            ], function ($message) use ($movimiento) {
                $message->to($movimiento->usuario->email)
                       ->subject('Material Entregado - ADA Córdoba');
            });
            
            Log::info("Notificación enviada: $evento a {$movimiento->usuario->email}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación $evento: " . $e->getMessage());
        }
    }

    /**
     * Enviar recordatorio de entrega próxima (llamar desde comando programado)
     */
    public function enviarRecordatoriosEntrega()
    {
        $evento = 'recordatorio_entrega';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarUsuario($evento) && !NotificationSetting::debeNotificarAdmin($evento)) {
            return;
        }

        // Buscar movimientos con fecha de entrega mañana
        $movimientos = \App\Models\MaterialMovimiento::whereDate('fecha_prevista_entrega', '=', now()->addDay()->toDateString())
            ->whereNull('fecha_entrega')
            ->with(['material', 'usuario', 'proveedor'])
            ->get();

        $urlBase = config('app.url') . '/gestionmaterial/#/historial';
        
        foreach ($movimientos as $movimiento) {
            $destinatarios = NotificationSetting::obtenerDestinatarios(
                $evento, 
                $movimiento->usuario, 
                Usuario::where('rol', 'admin')->get()
            );
            
            foreach ($destinatarios as $email) {
                try {
                    Mail::send('emails.recordatorio-entrega', [
                        'titulo' => 'Recordatorio de Entrega',
                        'nombreUsuario' => Usuario::where('email', $email)->first()->nombre ?? 'Usuario',
                        'movimiento' => $movimiento,
                        'urlMovimiento' => $urlBase
                    ], function ($message) use ($email) {
                        $message->to($email)
                               ->subject('Recordatorio: Entrega de Material Mañana - ADA Córdoba');
                    });
                    
                    Log::info("Recordatorio enviado: $evento a $email para movimiento {$movimiento->id}");
                } catch (\Exception $e) {
                    Log::error("Error enviando recordatorio a $email: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Enviar notificación de entregas vencidas (llamar desde comando programado)
     */
    public function notificarEntregasVencidas()
    {
        $evento = 'entrega_vencida';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarAdmin($evento)) {
            return;
        }

        // Buscar movimientos con fecha de entrega vencida
        $movimientos = \App\Models\MaterialMovimiento::whereDate('fecha_prevista_entrega', '<', now()->toDateString())
            ->whereNull('fecha_entrega')
            ->with(['material', 'usuario', 'proveedor'])
            ->get();

        $admins = Usuario::where('rol', 'admin')->get();
        $urlBase = config('app.url') . '/gestionmaterial/#/historial';
        
        foreach ($movimientos as $movimiento) {
            foreach ($admins as $admin) {
                if (!$admin->email) continue;
                
                try {
                    Mail::send('emails.entrega-vencida', [
                        'titulo' => 'Fecha de Entrega Vencida',
                        'nombreUsuario' => $admin->nombre ?? 'Administrador',
                        'movimiento' => $movimiento,
                        'urlMovimiento' => $urlBase
                    ], function ($message) use ($admin) {
                        $message->to($admin->email)
                               ->subject('Alerta: Fecha de Entrega Vencida - ADA Córdoba');
                    });
                    
                    Log::info("Notificación vencimiento enviada a {$admin->email} para movimiento {$movimiento->id}");
                } catch (\Exception $e) {
                    Log::error("Error enviando notificación vencimiento: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Enviar notificación de firma solicitada
     */
    public function notificarFirmaSolicitada($firma, $solicitadoPor)
    {
        $evento = 'firma_solicitada';
        
        // Aplicar configuración SMTP
        $this->aplicarConfigSmtp();
        
        if (!NotificationSetting::debeNotificarUsuario($evento) || !$firma->usuario_destino || !$firma->usuario_destino->email) {
            return;
        }

        $urlFirma = config('app.url') . '/gestionmaterial/#/firmas';
        
        try {
            Mail::send('emails.firma-solicitada', [
                'titulo' => 'Solicitud de Firma',
                'nombreUsuario' => $firma->usuario_destino->nombre ?? 'Usuario',
                'firma' => $firma,
                'solicitadoPor' => $solicitadoPor,
                'urlFirma' => $urlFirma
            ], function ($message) use ($firma) {
                $message->to($firma->usuario_destino->email)
                       ->subject('Solicitud de Firma - ADA Córdoba');
            });
            
            Log::info("Notificación enviada: $evento a {$firma->usuario_destino->email}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación $evento: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de fecha prevista de entrega
     */
    public function notificarFechaPrevistaEntrega($movimiento, $fecha)
    {
        try {
            // Aplicar configuración SMTP
            $this->aplicarConfigSmtp();
            
            // Cargar relaciones necesarias
            $movimiento->load(['pedido', 'usuario']);
            
            // Determinar email del destinatario
            $emailUsuario = null;
            $nombreUsuario = 'Usuario';
            
            // Prioridad 1: Si viene de petición web, usar email del solicitante
            if ($movimiento->pedido) {
                $emailUsuario = $movimiento->pedido->email_solicitante;
                $nombreUsuario = $movimiento->pedido->usuario_solicitante ?? 'Usuario';
            }
            // Prioridad 2: Si fue creado manualmente, usar email del creador
            elseif ($movimiento->usuario) {
                $emailUsuario = $movimiento->usuario->email;
                $nombreUsuario = $movimiento->usuario->nombre ?? 'Usuario';
            }
            // Prioridad 3: Intentar extraer email del campo destino
            elseif ($movimiento->destino && filter_var($movimiento->destino, FILTER_VALIDATE_EMAIL)) {
                $emailUsuario = $movimiento->destino;
                $nombreUsuario = $movimiento->destino;
            }
            
            // Validar email
            if (!$emailUsuario || !filter_var($emailUsuario, FILTER_VALIDATE_EMAIL)) {
                Log::warning('No se pudo enviar notificación de fecha de entrega: email no válido', [
                    'movimiento_id' => $movimiento->id,
                    'destino' => $movimiento->destino,
                    'tiene_pedido' => $movimiento->pedido ? 'si' : 'no',
                    'tiene_usuario' => $movimiento->usuario ? 'si' : 'no'
                ]);
                return;
            }

            // Formatear fecha con hora
            $fechaFormateada = \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] HH:mm');

            // Datos para el email
            $datos = [
                'titulo' => 'Fecha de Entrega Establecida',
                'nombre_usuario' => $nombreUsuario,
                'numero_documento' => $movimiento->numero_documento,
                'tipo_movimiento' => $movimiento->tipo === 'entrada' ? 'Entrada' : 'Salida',
                'fecha_entrega' => $fechaFormateada,
                'origen' => $movimiento->origen,
                'destino' => $movimiento->destino,
                'observaciones' => $movimiento->observaciones,
            ];

            // Enviar email
            Mail::send('emails.fecha_entrega', $datos, function ($message) use ($emailUsuario, $datos) {
                $message->to($emailUsuario)
                        ->subject('📅 Fecha de Entrega Establecida - ' . $datos['numero_documento']);
            });

            Log::info('Notificación de fecha de entrega enviada', [
                'movimiento_id' => $movimiento->id,
                'email' => $emailUsuario,
                'nombre' => $nombreUsuario,
                'fecha' => $fecha
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de fecha de entrega', [
                'error' => $e->getMessage(),
                'movimiento_id' => $movimiento->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Enviar notificación genérica a un usuario
     */
    public function notificarUsuario($usuario, $asunto, $vista, $datos = [])
    {
        try {
            // Aplicar configuración SMTP
            $this->aplicarConfigSmtp();
            
            // Validar email
            if (!$usuario || !$usuario->email || !filter_var($usuario->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('No se pudo enviar notificación: email no válido', [
                    'usuario_id' => $usuario->id ?? null,
                    'email' => $usuario->email ?? null
                ]);
                return;
            }

            // Agregar título si no existe
            if (!isset($datos['titulo'])) {
                $datos['titulo'] = $asunto;
            }

            // Enviar email
            Mail::send('emails.' . $vista, $datos, function ($message) use ($usuario, $asunto) {
                $message->to($usuario->email)
                        ->subject($asunto);
            });

            Log::info('Notificación genérica enviada', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
                'asunto' => $asunto,
                'vista' => $vista
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación genérica', [
                'error' => $e->getMessage(),
                'usuario_id' => $usuario->id ?? null,
                'asunto' => $asunto ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
