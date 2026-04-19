<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodePreviewCommand extends Command
{
    protected $signature = 'shortcode:preview {name : Nombre del shortcode a previsualizar}';

    protected $description = 'Renderiza el ejemplo registrado del shortcode';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (! Shortcode::has($name)) {
            $this->error("Shortcode [{$name}] no está registrado.");

            return self::FAILURE;
        }

        $registered = collect(Shortcode::getRegistered())
            ->firstWhere('name', $name);

        if (! $registered) {
            $this->error("No hay metadata para [{$name}].");

            return self::FAILURE;
        }

        $example = $registered['example'] ?? "[{$name}][/{$name}]";

        $this->line('');
        $this->info('Shortcode: '.$name);
        if (! empty($registered['description'])) {
            $this->line('Descripción: '.$registered['description']);
        }
        $this->line('');
        $this->info('Ejemplo:');
        $this->line($example);
        $this->line('');
        $this->info('Resultado:');
        $this->line(Shortcode::compile($example));
        $this->line('');

        return self::SUCCESS;
    }
}
