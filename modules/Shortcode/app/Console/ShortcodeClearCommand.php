<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeClearCommand extends Command
{
    protected $signature = 'shortcode:clear';

    protected $description = 'Limpiar la caché de compilación de shortcodes';

    public function handle(): int
    {
        $this->info(__('shortcode::shortcode.cmd_clearing'));

        Shortcode::clearCache();

        $this->info(__('shortcode::shortcode.cmd_cleared'));

        return self::SUCCESS;
    }
}
