<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Services\LinkRotChecker;

class CheckLinksCommand extends Command
{
    protected $signature = 'campaign:check-links {--template-id= : ID específico de plantilla}';

    protected $description = 'Verifica links rotos en plantillas de campaña';

    public function handle(): int
    {
        $checker = new LinkRotChecker;
        $query = Template::query();

        if ($this->option('template-id')) {
            $query->where('id', $this->option('template-id'));
        }

        $brokenFound = false;
        $query->chunk(50, function ($templates) use ($checker, &$brokenFound): void {
            foreach ($templates as $template) {
                $results = $checker->check($template->content ?? '');
                $broken = collect($results)->filter(fn ($r) => ! $r['ok'])->all();

                if (! empty($broken)) {
                    $brokenFound = true;
                    $this->error("Plantilla #{$template->id}: ".count($broken).' links rotos');
                    foreach ($broken as $b) {
                        $this->warn("  - {$b['url']} (status: {$b['status']})");
                    }
                }
            }
        });

        if (! $brokenFound) {
            $this->info('No se encontraron links rotos.');
        }

        return self::SUCCESS;
    }
}
