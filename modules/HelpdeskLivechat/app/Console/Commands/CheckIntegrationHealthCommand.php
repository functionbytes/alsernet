<?php

namespace Modules\HelpdeskLivechat\Console\Commands;

use Illuminate\Console\Command;

class CheckIntegrationHealthCommand extends Command
{
    protected $signature = 'helpdesk-livechat:check-health';

    protected $description = 'Health check for HelpdeskLivechat integrations';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
