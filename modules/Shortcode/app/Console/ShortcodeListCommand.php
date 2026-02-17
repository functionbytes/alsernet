<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shortcode:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered shortcodes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shortcodes = Shortcode::all();

        if (empty($shortcodes)) {
            $this->warn('No shortcodes registered.');

            return 0;
        }

        $this->info('Registered Shortcodes:');
        $this->line('');

        $tableData = [];
        foreach ($shortcodes as $index => $name) {
            $tableData[] = [
                'Index' => $index + 1,
                'Name' => $name,
                'Usage' => "[{$name}]content[/{$name}]",
            ];
        }

        $this->table(['Index', 'Name', 'Usage'], $tableData);

        $this->line('');
        $this->info('Total: '.count($shortcodes).' shortcode(s)');

        return 0;
    }
}
