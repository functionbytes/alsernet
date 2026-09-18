<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Jobs\RenewSocialAccountTokenJob;
use Modules\HelpdeskSocial\Models\SocialAccount;

class RenewExpiringSocialTokensCommand extends Command
{
    protected $signature = 'helpdesk-social:renew-tokens';

    protected $description = 'Encola la renovación de token para cuentas sociales activas cuyo token expira en menos de 10 días';

    public function handle(): int
    {
        $accounts = SocialAccount::active()
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addDays(10))
            ->get();

        foreach ($accounts as $account) {
            RenewSocialAccountTokenJob::dispatch($account->id);
        }

        $this->info("{$accounts->count()} cuenta(s) encoladas para renovación de token.");

        return self::SUCCESS;
    }
}
