<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeListCommand extends Command
{
    protected $signature = 'shortcode:list';

    protected $description = 'Listar todos los shortcodes registrados';

    public function handle(): int
    {
        $shortcodes = Shortcode::all();

        if (empty($shortcodes)) {
            $this->warn(__('shortcode::shortcode.cmd_no_shortcodes'));

            return self::SUCCESS;
        }

        $this->info(__('shortcode::shortcode.cmd_registered'));
        $this->line('');

        $tableData = [];
        foreach ($shortcodes as $index => $name) {
            $tableData[] = [
                __('shortcode::shortcode.cmd_col_index') => $index + 1,
                __('shortcode::shortcode.cmd_col_name') => $name,
                __('shortcode::shortcode.cmd_col_usage') => "[{$name}]content[/{$name}]",
            ];
        }

        $this->table([
            __('shortcode::shortcode.cmd_col_index'),
            __('shortcode::shortcode.cmd_col_name'),
            __('shortcode::shortcode.cmd_col_usage'),
        ], $tableData);

        $this->line('');
        $this->info(__('shortcode::shortcode.cmd_total', ['count' => count($shortcodes)]));

        return self::SUCCESS;
    }
}
