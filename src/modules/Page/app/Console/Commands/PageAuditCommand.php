<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Page\Models\Page;
use Modules\Template\Services\TemplateManager;

/**
 * Audits every page + translation for integrity problems that the editor's
 * runtime validations would catch on save, but that may already exist in
 * the catalog (e.g. created before the validation rules were added).
 */
class PageAuditCommand extends Command
{
    protected $signature = 'page:audit {--fix : Attempt safe auto-fixes (clear invalid template only)}';

    protected $description = 'Auditar integridad de páginas: templates inválidos, slugs duplicados, traducciones huérfanas';

    public function handle(TemplateManager $templates): int
    {
        $issues = 0;
        $fixes = 0;
        $fix = (bool) $this->option('fix');

        // ── Templates inválidos ──────────────────────────────────────────
        $this->info('→ Verificando templates…');
        $pages = Page::query()
            ->whereNotNull('template')
            ->where('template', '!=', '')
            ->get(['id', 'title', 'template']);

        foreach ($pages as $page) {
            if (! $templates->hasTemplate($page->template)) {
                $issues++;
                $this->warn("  · Página #{$page->id} '{$page->title}' usa template inexistente: {$page->template}");
                if ($fix) {
                    $page->update(['template' => null]);
                    $this->line('    ↳ corregido: template borrado');
                    $fixes++;
                }
            }
        }

        // ── Slugs duplicados por locale ──────────────────────────────────
        $this->info('→ Verificando slugs duplicados…');
        $duplicates = DB::table('page_translations')
            ->select('locale_id', 'slug', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(page_id) as page_ids'))
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->groupBy('locale_id', 'slug')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $issues++;
            $this->warn("  · Slug duplicado '{$dup->slug}' (locale_id={$dup->locale_id}) en páginas: {$dup->page_ids}");
        }

        // ── Traducciones huérfanas (sin página) ──────────────────────────
        $this->info('→ Verificando traducciones huérfanas…');
        $orphans = DB::table('page_translations as pt')
            ->leftJoin('pages as p', 'pt.page_id', '=', 'p.id')
            ->whereNull('p.id')
            ->count();

        if ($orphans > 0) {
            $issues++;
            $this->warn("  · {$orphans} traducciones apuntan a páginas inexistentes");
        }

        // ── Resumen ───────────────────────────────────────────────────────
        $this->newLine();
        if ($issues === 0) {
            $this->info('✔ Auditoría completada. Sin problemas detectados.');

            return self::SUCCESS;
        }

        $this->warn("{$issues} problema(s) detectado(s)".($fix ? ", {$fixes} corregido(s)" : ''));

        if (! $fix) {
            $this->line('Corre con --fix para aplicar correcciones automáticas seguras.');
        }

        return self::SUCCESS;
    }
}
