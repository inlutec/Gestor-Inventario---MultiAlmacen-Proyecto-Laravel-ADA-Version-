<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class NotificarEntregasVencidas extends Command
{
    protected $signature = 'notificaciones:entregas-vencidas';
    protected $description = 'Notifica a administradores sobre entregas vencidas';

    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Notificando entregas vencidas...');
        
        try {
            $this->notificationService->notificarEntregasVencidas();
            $this->info('✓ Notificaciones de entregas vencidas enviadas correctamente');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al enviar notificaciones: ' . $e->getMessage());
            return 1;
        }
    }
}
