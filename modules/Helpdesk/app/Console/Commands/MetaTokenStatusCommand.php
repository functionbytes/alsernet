<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Services\Channels\MetaTokenHealth;

/**
 * Estado de validez de los tokens de Meta por canal. Pensado para monitorización
 * (código de salida ≠ 0 si algún canal tiene el token inválido).
 */
class MetaTokenStatusCommand extends Command
{
    protected $signature = 'helpdesk:meta-token-status';

    protected $description = 'Muestra el estado de validez de los tokens de Meta (WhatsApp/Facebook/Instagram).';

    public function handle(): int
    {
        $anyInvalid = false;

        foreach (MetaTokenHealth::statuses() as $channel => $invalidSince) {
            if ($invalidSince) {
                $this->error("✗ {$channel}: token INVÁLIDO desde {$invalidSince} — requiere re-autenticación");
                $anyInvalid = true;
            } else {
                $this->info("✓ {$channel}: OK");
            }
        }

        return $anyInvalid ? self::FAILURE : self::SUCCESS;
    }
}
