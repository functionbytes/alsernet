<?php

namespace Modules\Page\Console;

use Illuminate\Console\Command;
use Modules\Page\Jobs\PublishScheduledPagesJob;
use Modules\Page\Jobs\UnpublishScheduledPagesJob;

class PublishScheduledPagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:publish-scheduled
                            {--type=all : Type of operation: all, publish, unpublish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and unpublish pages scheduled for automatic publishing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');

        $this->info('Processing scheduled pages...');

        try {
            if ($type === 'all' || $type === 'publish') {
                $this->info('Publishing scheduled pages...');
                PublishScheduledPagesJob::dispatch();
                $this->info('✓ Publish job dispatched successfully');
            }

            if ($type === 'all' || $type === 'unpublish') {
                $this->info('Unpublishing scheduled pages...');
                UnpublishScheduledPagesJob::dispatch();
                $this->info('✓ Unpublish job dispatched successfully');
            }

            if (! in_array($type, ['all', 'publish', 'unpublish'])) {
                $this->error('Invalid type option. Use: all, publish, or unpublish');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('Scheduled page processing jobs have been dispatched.');
            $this->info('Check the queue logs for detailed results.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing scheduled pages: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
