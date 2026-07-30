<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Jobs\SyncSocialCommentsJob;
use Modules\HelpdeskSocial\Models\SocialAccount;

class SyncSocialCommentsCommand extends Command
{
    protected $signature = 'helpdesk-social:sync-comments
                            {account? : ID de la cuenta social}
                            {--post-id= : ID del post/media a sincronizar}
                            {--since= : Fecha desde (Y-m-d)}';

    protected $description = 'Sincroniza comentarios desde las redes sociales';

    public function handle(): int
    {
        $accountId = $this->argument('account');

        if ($accountId) {
            $account = SocialAccount::find($accountId);

            if (! $account) {
                $this->error("Cuenta #{$accountId} no encontrada.");

                return self::FAILURE;
            }

            $this->syncAccount($account);

            return self::SUCCESS;
        }

        $accounts = SocialAccount::active()
            ->where('comments_enabled', true)
            ->get();

        if ($accounts->isEmpty()) {
            $this->warn('No hay cuentas activas con comentarios habilitados.');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->syncAccount($account);
        }

        $this->info("{$accounts->count()} cuenta(s) encoladas para sincronización.");

        return self::SUCCESS;
    }

    private function syncAccount(SocialAccount $account): void
    {
        $postId = $this->option('post-id');

        SyncSocialCommentsJob::dispatch($account->id, $postId);
        $this->info("Encolada sincronización para {$account->name} ({$account->platform}).");
    }
}
