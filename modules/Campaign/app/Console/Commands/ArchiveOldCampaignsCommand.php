<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Services\CampaignArchiver;

class ArchiveOldCampaignsCommand extends Command
{
    protected $signature = 'campaign:archive-old {--days=365 : Campañas finalizadas hace más de N días}';

    protected $description = 'Archiva tracking logs de campañas antiguas';

    public function handle(): int
    {
        $archiver = new CampaignArchiver;
        $total = $archiver->archiveOlderThan((int) $this->option('days'));
        $this->info("{$total} tracking logs archivados.");

        return self::SUCCESS;
    }
}
