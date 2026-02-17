<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeCompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shortcode:compile
                            {content : The content containing shortcodes}
                            {--strip : Strip shortcodes instead of compiling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile or strip shortcodes from content';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $content = $this->argument('content');
        $strip = $this->option('strip');

        $this->line('');
        $this->info('Original Content:');
        $this->line($content);
        $this->line('');

        if ($strip) {
            $result = Shortcode::strip($content);
            $this->info('Stripped Content:');
        } else {
            $result = Shortcode::compile($content);
            $this->info('Compiled Content:');
        }

        $this->line($result);
        $this->line('');

        return 0;
    }
}
