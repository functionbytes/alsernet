<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Jobs\SyncSocialMentionsJob;

class SyncSocialMentionsCommand extends Command
{
    protected $signature = 'helpdesksocial:sync-mentions {--platform= : Filter by platform (facebook, instagram, etc.)}';

    protected $description = 'Sync social mentions based on active listening keywords';

    public function handle(): int
    {
        $platform = $this->option('platform');

        SyncSocialMentionsJob::dispatch($platform);

        $this->info('Social mentions sync job dispatched'.($platform ? " for platform: {$platform}" : ' for all platforms').'.');

        return self::SUCCESS;
    }
}
