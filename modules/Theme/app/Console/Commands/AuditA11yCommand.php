<?php

namespace Modules\Theme\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Escanea los blades de un theme en `platform/themes/{slug}` reportando
 * problemas básicos de accesibilidad (los mismos que penaliza PageSpeed):
 *
 *   1. <img>     sin atributo alt
 *   2. <a>       sin texto ni aria-label (solo ícono)
 *   3. <button>  sin texto ni aria-label (solo ícono)
 *   4. <html>    sin atributo lang
 *
 * Imprime ruta:línea para cada hallazgo y un resumen. No modifica archivos;
 * sirve como backlog accionable para el equipo.
 */
class AuditA11yCommand extends Command
{
    protected $signature = 'theme:audit-a11y {slug : nombre de la carpeta del theme, p. ej. caixilhariablanco}';

    protected $description = 'Auditar accesibilidad de blades de un theme: alt en imágenes, nombres accesibles en enlaces/botones, lang en <html>';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $themeDir = base_path("platform/themes/{$slug}");
        if (! is_dir($themeDir)) {
            $this->error("Theme no encontrado: {$themeDir}");

            return self::FAILURE;
        }

        $this->info("Escaneando {$themeDir}/**/*.blade.php…");
        $this->newLine();

        $finder = (new Finder)->files()->in($themeDir)->name('*.blade.php');

        $issues = [
            'img_without_alt' => [],
            'link_without_name' => [],
            'button_without_name' => [],
            'html_without_lang' => [],
        ];

        foreach ($finder as $file) {
            $relative = str_replace(base_path().'/', '', $file->getRealPath());
            $lines = file($file->getRealPath());

            foreach ($lines as $i => $line) {
                $lineNo = $i + 1;
                $trim = trim($line);

                // <img> sin alt. Permite alt vacío intencional (alt="").
                if (preg_match('/<img\s[^>]*\bsrc=[^>]*>/i', $line)
                    && ! preg_match('/<img[^>]+\balt=/i', $line)) {
                    $issues['img_without_alt'][] = "{$relative}:{$lineNo}  ".self::snippet($trim);
                }

                // <a href="..."> sin texto, sin aria-label, sin título.
                if (preg_match('/<a\s[^>]*\bhref=[^>]*>\s*(?:<i[^>]*>[^<]*<\/i>\s*)?<\/a>/i', $line)
                    && ! preg_match('/<a[^>]+(?:aria-label|title)=/i', $line)) {
                    $issues['link_without_name'][] = "{$relative}:{$lineNo}  ".self::snippet($trim);
                }

                // <button> sin texto ni aria-label.
                if (preg_match('/<button\b[^>]*>\s*(?:<i[^>]*>[^<]*<\/i>\s*)?<\/button>/i', $line)
                    && ! preg_match('/<button[^>]+(?:aria-label|title)=/i', $line)) {
                    $issues['button_without_name'][] = "{$relative}:{$lineNo}  ".self::snippet($trim);
                }

                // <html> sin lang.
                if (preg_match('/<html\b/i', $line) && ! preg_match('/<html[^>]*\blang=/i', $line)) {
                    $issues['html_without_lang'][] = "{$relative}:{$lineNo}  ".self::snippet($trim);
                }
            }
        }

        $labels = [
            'img_without_alt' => 'Imágenes sin alt',
            'link_without_name' => 'Enlaces sin nombre accesible (solo ícono, sin aria-label)',
            'button_without_name' => 'Botones sin nombre accesible (solo ícono, sin aria-label)',
            'html_without_lang' => '<html> sin atributo lang',
        ];

        $total = 0;
        foreach ($issues as $k => $rows) {
            if (empty($rows)) {
                continue;
            }
            $this->newLine();
            $this->warn('▸ '.$labels[$k].' ('.count($rows).')');
            foreach ($rows as $row) {
                $this->line('   · '.$row);
            }
            $total += count($rows);
        }

        $this->newLine();
        if ($total === 0) {
            $this->info('✔ Sin hallazgos en el theme. Accesibilidad básica OK.');

            return self::SUCCESS;
        }

        $this->warn("Total de hallazgos: {$total}");
        $this->line('Recomendación: añadir aria-label / alt / lang según corresponda y volver a ejecutar.');

        return self::SUCCESS;
    }

    private static function snippet(string $line, int $max = 110): string
    {
        $line = preg_replace('/\s+/', ' ', $line);

        return strlen($line) > $max ? substr($line, 0, $max - 1).'…' : $line;
    }
}
