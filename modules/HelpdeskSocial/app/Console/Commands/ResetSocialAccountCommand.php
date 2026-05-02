<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Models\SocialAccount;

class ResetSocialAccountCommand extends Command
{
    protected $signature = 'helpdesk-social:reset-account
                            {account : ID de la cuenta social}
                            {--activate : Reactivar la cuenta si está desactivada}';

    protected $description = 'Resetea el circuit breaker de una cuenta social';

    public function handle(): int
    {
        $accountId = $this->argument('account');
        $account = SocialAccount::withTrashed()->find($accountId);

        if (! $account) {
            $this->error("Cuenta #{$accountId} no encontrada.");

            return self::FAILURE;
        }

        $data = [
            'consecutive_failures' => 0,
            'last_error_at' => null,
            'last_error_message' => null,
        ];

        if ($this->option('activate')) {
            $data['is_active'] = true;
        }

        $account->update($data);

        $this->info("Cuenta #{$account->id} ({$account->name}) reseteada correctamente.");

        if (! $account->is_active && ! $this->option('activate')) {
            $this->warn('La cuenta sigue desactivada. Usa --activate para reactivarla.');
        }

        return self::SUCCESS;
    }
}
