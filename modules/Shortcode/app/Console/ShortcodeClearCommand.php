<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shortcode:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear shortcode compilation cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing shortcode cache...');

        Shortcode::clearCache();

        $this->info('Shortcode cache cleared successfully!');

        return 0;
    }
}
