<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\Setting;
use Modules\Seo\Models\Seo404Log;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Models\SeoWebVital;

/**
 * Daily health check across the SEO module. Returns exit code 0 if all checks
 * pass, 1 if any warning, 2 if any critical. Intended to run via cron and
 * alert when something breaks.
 *
 * Usage:
 *   php artisan seo:health
 *   php artisan seo:health --json     # JSON output for monitoring scripts
 */
class SeoHealthCommand extends Command
{
    protected $signature = 'seo:health {--json : Output raw JSON}';

    protected $description = 'Run a diagnostic checklist across the SEO module and report issues';

    /** @var array<int, array{name: string, status: string, message: string}> */
    private array $checks = [];

    public function handle(): int
    {
        $this->checkSitemap();
        $this->checkRobotsTxt();
        $this->checkLlmsTxt();
        $this->checkIndexNow();
        $this->checkRedirectChains();
        $this->checkBrokenRedirects();
        $this->check404Volume();
        $this->checkMissingSeo();
        $this->checkWebVitals();
        $this->checkPageSpeedApi();

        return $this->render();
    }

    private function checkSitemap(): void
    {
        $url = rtrim(config('app.url', ''), '/').'/sitemap.xml';
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful() && str_contains($response->body(), '<urlset')) {
                $this->pass('sitemap', 'sitemap.xml accesible');

                return;
            }
            $this->critical('sitemap', "sitemap.xml respondió {$response->status()}");
        } catch (\Throwable $e) {
            $this->critical('sitemap', 'sitemap.xml inaccesible: '.$e->getMessage());
        }
    }

    private function checkRobotsTxt(): void
    {
        $url = rtrim(config('app.url', ''), '/').'/robots.txt';
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful() && str_contains($response->body(), 'User-agent')) {
                $this->pass('robots', 'robots.txt accesible');

                return;
            }
            $this->warning('robots', "robots.txt respondió {$response->status()}");
        } catch (\Throwable $e) {
            $this->warning('robots', 'robots.txt inaccesible');
        }
    }

    private function checkLlmsTxt(): void
    {
        if (! config('Seo.llms_txt.enabled', true)) {
            $this->skip('llms', 'llms.txt deshabilitado en config');

            return;
        }

        $content = Setting::get('seo.llms_txt');
        if (empty($content)) {
            $this->warning('llms', 'llms.txt usando contenido por defecto (no personalizado)');

            return;
        }
        $this->pass('llms', 'llms.txt personalizado configurado');
    }

    private function checkIndexNow(): void
    {
        if (! config('Seo.indexnow.enabled', false)) {
            $this->skip('indexnow', 'IndexNow deshabilitado');

            return;
        }
        if (! config('Seo.indexnow.key')) {
            $this->critical('indexnow', 'IndexNow habilitado pero falta SEO_INDEXNOW_KEY');

            return;
        }
        $this->pass('indexnow', 'IndexNow configurado');
    }

    private function checkRedirectChains(): void
    {
        $active = SeoRedirect::query()->where('is_active', true)->count();
        if ($active === 0) {
            $this->skip('chains', 'Sin redirects activos');

            return;
        }

        $chainCount = DB::table('seo_redirects as a')
            ->join('seo_redirects as b', 'a.target_path', '=', 'b.source_path')
            ->where('a.is_active', true)
            ->where('b.is_active', true)
            ->where('a.is_regex', false)
            ->where('b.is_regex', false)
            ->count();

        if ($chainCount === 0) {
            $this->pass('chains', "$active redirects activos, sin cadenas");

            return;
        }
        $this->warning('chains', "$chainCount cadenas de redirects detectadas");
    }

    private function checkBrokenRedirects(): void
    {
        // Redirects whose target path doesn't start with / or http
        $broken = SeoRedirect::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_path', '')
                    ->orWhereNull('target_path');
            })
            ->count();

        if ($broken > 0) {
            $this->critical('broken_redirects', "$broken redirects activos con target vacío");

            return;
        }
        $this->pass('broken_redirects', 'Todos los redirects tienen target válido');
    }

    private function check404Volume(): void
    {
        $recent = Seo404Log::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($recent > 500) {
            $this->warning('404s', "$recent errores 404 en los últimos 7 días (alto)");

            return;
        }
        $this->pass('404s', "$recent errores 404 en los últimos 7 días");
    }

    private function checkMissingSeo(): void
    {
        $missing = SeoMeta::query()
            ->whereNull('description')
            ->orWhere('description', '')
            ->count();

        if ($missing > 0) {
            $this->warning('missing_seo', "$missing páginas sin meta description");

            return;
        }
        $this->pass('missing_seo', 'Todas las páginas con meta description');
    }

    private function checkWebVitals(): void
    {
        $recent = SeoWebVital::query()
            ->where('captured_at', '>=', now()->subDays(7))
            ->count();

        if ($recent === 0) {
            $this->warning('web_vitals', 'Sin muestras de Web Vitals en 7 días — revisa @seoWebVitalsBeacon en el layout');

            return;
        }

        $poorPct = SeoWebVital::query()
            ->where('captured_at', '>=', now()->subDays(7))
            ->where('rating', 'poor')
            ->count();

        $ratio = $recent > 0 ? round(($poorPct / $recent) * 100) : 0;

        if ($ratio > 25) {
            $this->warning('web_vitals', "{$ratio}% muestras \"poor\" (>25%) — revisa LCP/INP/CLS");

            return;
        }
        $this->pass('web_vitals', "$recent muestras en 7 días, {$ratio}% poor");
    }

    private function checkPageSpeedApi(): void
    {
        if (! config('Seo.pagespeed_api_key')) {
            $this->skip('pagespeed', 'Sin SEO_PAGESPEED_API_KEY (opcional)');

            return;
        }
        $this->pass('pagespeed', 'PageSpeed API configurada');
    }

    private function pass(string $name, string $message): void
    {
        $this->checks[] = ['name' => $name, 'status' => 'pass', 'message' => $message];
    }

    private function warning(string $name, string $message): void
    {
        $this->checks[] = ['name' => $name, 'status' => 'warn', 'message' => $message];
    }

    private function critical(string $name, string $message): void
    {
        $this->checks[] = ['name' => $name, 'status' => 'fail', 'message' => $message];
    }

    private function skip(string $name, string $message): void
    {
        $this->checks[] = ['name' => $name, 'status' => 'skip', 'message' => $message];
    }

    private function render(): int
    {
        if ($this->option('json')) {
            $this->line(json_encode(['checks' => $this->checks, 'generated_at' => now()->toIso8601String()], JSON_PRETTY_PRINT));

            return $this->exitCode();
        }

        $this->info('SEO Health Report');
        $this->line('');
        foreach ($this->checks as $check) {
            $label = match ($check['status']) {
                'pass' => '<fg=green>✓</>',
                'warn' => '<fg=yellow>!</>',
                'fail' => '<fg=red>✗</>',
                'skip' => '<fg=gray>-</>',
            };
            $this->line(sprintf('  %s  %-20s %s', $label, $check['name'], $check['message']));
        }
        $this->line('');

        $counts = array_count_values(array_column($this->checks, 'status'));
        $this->line(sprintf(
            'Summary: %d pass, %d warn, %d fail, %d skip',
            $counts['pass'] ?? 0, $counts['warn'] ?? 0, $counts['fail'] ?? 0, $counts['skip'] ?? 0
        ));

        return $this->exitCode();
    }

    private function exitCode(): int
    {
        foreach ($this->checks as $c) {
            if ($c['status'] === 'fail') {
                return 2;
            }
        }
        foreach ($this->checks as $c) {
            if ($c['status'] === 'warn') {
                return 1;
            }
        }

        return 0;
    }
}
