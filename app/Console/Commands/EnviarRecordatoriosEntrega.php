<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class EnviarRecordatoriosEntrega extends Command
{
    protected $signature = 'notificaciones:recordatorios-entrega';
    protected $description = 'Envía recordatorios de entregas próximas (día anterior)';

    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Enviando recordatorios de entrega...');
        
        try {
            $this->notificationService->enviarRecordatoriosEntrega();
            $this->info('✓ Recordatorios enviados correctamente');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al enviar recordatorios: ' . $e->getMessage());
            return 1;
        }
    }
}
