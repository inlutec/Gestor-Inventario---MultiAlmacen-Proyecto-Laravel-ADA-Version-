<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CheckmkConfig;
use Carbon\Carbon;

class CheckSyncInterval extends Command
{
    protected $signature = 'checkmk:check-sync';
    protected $description = 'Verifica si es necesario ejecutar sincronización según el intervalo configurado';

    public function handle()
    {
        $config = CheckmkConfig::first();
        
        if (!$config) {
            $this->error('No se encontró configuración de CheckMK');
            return 1;
        }

        $intervalMinutes = $config->sync_interval_minutes;
        $lastSync = $config->last_sync;

        // Si nunca se ha sincronizado, ejecutar ahora
        if (!$lastSync) {
            $this->info('Primera sincronización, ejecutando...');
            $this->call('checkmk:sync');
            return 0;
        }

        $lastSyncTime = Carbon::parse($lastSync);
        $minutesSinceLastSync = $lastSyncTime->diffInMinutes(Carbon::now());

        // Si ha pasado el intervalo configurado, sincronizar
        if ($minutesSinceLastSync >= $intervalMinutes) {
            $this->info("Han pasado {$minutesSinceLastSync} minutos desde la última sincronización (intervalo: {$intervalMinutes}). Ejecutando...");
            $this->call('checkmk:sync');
            return 0;
        }

        $minutosRestantes = $intervalMinutes - $minutesSinceLastSync;
        $this->line("Próxima sincronización en {$minutosRestantes} minutos");
        return 0;
    }
}
