<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeCompileCommand extends Command
{
    protected $signature = 'shortcode:compile
                            {content : Contenido con shortcodes a procesar}
                            {--strip : Eliminar shortcodes en vez de compilarlos}';

    protected $description = 'Compilar o eliminar shortcodes de un texto';

    public function handle(): int
    {
        $content = (string) $this->argument('content');

        if (strlen($content) > ShortcodeCompiler::MAX_CONTENT_SIZE) {
            $this->error('El contenido excede el tamaño máximo permitido ('.ShortcodeCompiler::MAX_CONTENT_SIZE.' bytes).');

            return self::FAILURE;
        }

        $this->line('');
        $this->info(__('shortcode::shortcode.cmd_original'));
        $this->line($content);
        $this->line('');

        if ($this->option('strip')) {
            $result = Shortcode::strip($content);
            $this->info(__('shortcode::shortcode.cmd_stripped'));
        } else {
            $result = Shortcode::compile($content);
            $this->info(__('shortcode::shortcode.cmd_compiled'));
        }

        $this->line($result);
        $this->line('');

        return self::SUCCESS;
    }
}
