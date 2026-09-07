<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Modules\Forms\Models\FormAccessToken;

class CleanupFormTokensCommand extends Command
{
    protected $signature = 'forms:cleanup-tokens';

    protected $description = 'Eliminar tokens de acceso a formularios expirados o agotados';

    public function handle(): int
    {
        $expiredDeleted = $this->deleteExpiredTokens();
        $exhaustedDeleted = $this->deleteExhaustedTokens();

        $this->info("Deleted {$expiredDeleted} expired tokens and {$exhaustedDeleted} exhausted tokens");

        return self::SUCCESS;
    }

    private function deleteExpiredTokens(): int
    {
        $deleted = 0;

        FormAccessToken::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->chunkById(500, function ($tokens) use (&$deleted) {
                foreach ($tokens as $token) {
                    $token->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    private function deleteExhaustedTokens(): int
    {
        $deleted = 0;

        FormAccessToken::query()
            ->whereNotNull('max_uses')
            ->whereColumn('times_used', '>=', 'max_uses')
            ->chunkById(500, function ($tokens) use (&$deleted) {
                foreach ($tokens as $token) {
                    $token->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
