<?php

namespace Modules\HelpdeskLivechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class CheckIntegrationHealthCommand extends Command
{
    protected $signature = 'helpdesk-livechat:check-health';

    protected $description = 'Health check for HelpdeskLivechat integrations';

    public function handle(): int
    {
        $this->info('HelpdeskLivechat — Health Check');
        $this->line(str_repeat('─', 50));

        $failed = 0;

        $failed += $this->checkDatabase();
        $failed += $this->checkWidgetAsset();
        $failed += $this->checkReverbConfig();
        $failed += $this->checkWebInboxes();

        $this->line('');

        if ($failed === 0) {
            $this->info('✔  All checks passed.');

            return self::SUCCESS;
        }

        $this->error("✘  {$failed} check(s) failed.");

        return self::FAILURE;
    }

    private function checkDatabase(): int
    {
        try {
            DB::connection('helpdesk')->getPdo();
            $this->line('✔  Database (helpdesk): connected');

            return 0;
        } catch (\Throwable $e) {
            $this->error('✘  Database (helpdesk): '.$e->getMessage());

            return 1;
        }
    }

    private function checkWidgetAsset(): int
    {
        $path = public_path('build-helpdesklivechat/widget.js');

        if (File::exists($path)) {
            $kb = round(File::size($path) / 1024);
            $this->line("✔  Widget JS bundle: exists ({$kb} KB)");

            return 0;
        }

        $this->error('✘  Widget JS bundle: NOT found at '.$path);
        $this->warn('   Run: npm run widget:build');

        return 1;
    }

    private function checkReverbConfig(): int
    {
        $key = config('broadcasting.connections.reverb.key');

        if (! empty($key)) {
            $host = config('broadcasting.connections.reverb.options.host');
            $port = config('broadcasting.connections.reverb.options.port');
            $this->line("✔  Reverb config: key set, {$host}:{$port}");

            return 0;
        }

        $this->warn('⚠  Reverb config: REVERB_APP_KEY not set — real-time chat disabled');

        return 0;
    }

    private function checkWebInboxes(): int
    {
        try {
            $count = Web::count();
            $this->line("✔  Web channels in DB: {$count}");

            return 0;
        } catch (\Throwable $e) {
            $this->error('✘  Web channels table: '.$e->getMessage());

            return 1;
        }
    }
}
