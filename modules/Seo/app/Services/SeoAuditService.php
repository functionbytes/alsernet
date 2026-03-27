<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Modules\Seo\Models\SeoMeta;

class SeoAuditService
{
    /**
     * Audit a URL and return SEO issues and score.
     */
    public function auditUrl(string $url): array
    {
        try {
            $this->validatePublicUrl($url);

            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SeoAuditBot/1.0)'])
                ->get($url);

            if (! $response->successful()) {
                return $this->errorResult("La URL retornó HTTP {$response->status()}");
            }

            return $this->buildResult($url, $this->runChecks($response->body(), $url));
        } catch (\Exception $e) {
            return $this->errorResult('No se pudo acceder a la URL: '.$e->getMessage());
        }
    }

    /**
     * Audit a SeoMeta model record (without HTTP request).
     */
    public function auditMeta(SeoMeta $meta): array
    {
        return $this->buildResult(null, $this->runMetaChecks($meta));
    }

    /**
     * Bulk audit all SeoMeta records.
     */
    public function auditAllMetas(): Collection
    {
        return SeoMeta::all()
            ->map(function (SeoMeta $meta) {
                $audit = $this->auditMeta($meta);

                return [
                    'id' => $meta->id,
                    'type' => class_basename($meta->seoable_type ?? ''),
                    'title' => $meta->title ?? '(sin título)',
                    'score' => $audit['score'],
                    'grade' => $audit['grade'],
                    'issues_count' => count($audit['issues']),
                    'issues' => $audit['issues'],
                ];
            })
            ->sortBy('score');
    }

    private function buildResult(?string $url, array $checks): array
    {
        $score = 100;
        $issues = [];

        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                $score -= $check['weight'];
                $issues[] = $check;
            } elseif ($check['status'] === 'warning') {
                $score -= (int) ($check['weight'] / 2);
                $issues[] = $check;
            }
        }

        $score = max(0, $score);
        $result = [
            'score' => $score,
            'grade' => $this->scoreToGrade($score),
            'issues' => $issues,
            'passed' => collect($checks)->where('status', 'ok')->values()->toArray(),
            'total_checks' => count($checks),
            'audited_at' => now()->toIso8601String(),
        ];

        if ($url !== null) {
            $result['url'] = $url;
        }

        return $result;
    }

    private function runChecks(string $html, string $url): array
    {
        return [
            ...$this->checkTitle($html),
            ...$this->checkDescription($html),
            ...$this->checkH1($html),
            ...$this->checkImages($html),
            $this->checkCanonical($html),
            $this->checkOpenGraph($html),
            $this->checkHttps($url),
            $this->checkStructuredData($html),
        ];
    }

    private function checkTitle(string $html): array
    {
        preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $match);
        $title = strip_tags($match[1] ?? '');
        $len = mb_strlen($title);

        if ($len === 0) {
            return [$this->issue('error', 'title_missing', 'Falta el título de la página', 'Agrega una etiqueta <title> con el título principal de la página', 20)];
        }

        if ($len < 30) {
            return [$this->issue('warning', 'title_short', "Título muy corto ({$len} caracteres)", 'El título debe tener entre 30 y 60 caracteres para mejores resultados en buscadores', 10)];
        }

        if ($len > 60) {
            return [$this->issue('warning', 'title_long', "Título muy largo ({$len} caracteres)", 'Reduce el título a menos de 60 caracteres para evitar recortes en los resultados de búsqueda', 5)];
        }

        return [$this->ok('title_ok', "Título correcto ({$len} caracteres)")];
    }

    private function checkDescription(string $html): array
    {
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/si', $html, $match);

        if (empty($match[1])) {
            preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\'][^>]*>/si', $html, $match);
        }

        $desc = $match[1] ?? '';
        $len = mb_strlen($desc);

        if ($len === 0) {
            return [$this->issue('error', 'description_missing', 'Falta la meta descripción', 'Agrega una meta descripción entre 120 y 160 caracteres', 15)];
        }

        if ($len < 80) {
            return [$this->issue('warning', 'description_short', "Meta descripción muy corta ({$len} caracteres)", 'Extiende la descripción a al menos 120 caracteres', 8)];
        }

        if ($len > 160) {
            return [$this->issue('warning', 'description_long', "Meta descripción muy larga ({$len} caracteres)", 'Reduce la descripción a menos de 160 caracteres', 5)];
        }

        return [$this->ok('description_ok', "Meta descripción correcta ({$len} caracteres)")];
    }

    private function checkH1(string $html): array
    {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/si', $html, $matches);
        $count = count($matches[0]);

        if ($count === 0) {
            return [$this->issue('error', 'h1_missing', 'Falta el encabezado H1', 'Agrega un encabezado H1 principal con la keyword principal de la página', 15)];
        }

        if ($count > 1) {
            return [$this->issue('warning', 'h1_multiple', "Múltiples H1 ({$count}) encontrados", 'Usa solo un H1 por página', 8)];
        }

        return [$this->ok('h1_ok', 'H1 presente (1 único)')];
    }

    private function checkImages(string $html): array
    {
        preg_match_all('/<img[^>]*>/si', $html, $matches);
        $total = count($matches[0]);

        if ($total === 0) {
            return [];
        }

        $withoutAlt = collect($matches[0])
            ->filter(fn ($img) => ! preg_match('/alt=["\'][^"\']+["\']/i', $img))
            ->count();

        if ($withoutAlt > 0) {
            return [$this->issue('warning', 'images_alt', "{$withoutAlt} imágenes sin atributo alt", 'Agrega textos alt descriptivos a todas las imágenes para accesibilidad y SEO', 8)];
        }

        return [$this->ok('images_alt_ok', "Todas las imágenes ({$total}) tienen alt")];
    }

    private function checkCanonical(string $html): array
    {
        $hasCanonical = str_contains($html, 'rel="canonical"') || str_contains($html, "rel='canonical'");

        return $hasCanonical
            ? [$this->ok('canonical_ok', 'URL canónica definida')]
            : [$this->issue('warning', 'canonical_missing', 'Falta la URL canónica', 'Agrega una etiqueta <link rel="canonical"> para evitar contenido duplicado', 5)];
    }

    private function checkOpenGraph(string $html): array
    {
        $hasOg = str_contains($html, 'og:title') && str_contains($html, 'og:description');

        return $hasOg
            ? [$this->ok('og_ok', 'Open Graph presente (og:title, og:description)')]
            : [$this->issue('warning', 'og_missing', 'Faltan etiquetas Open Graph', 'Agrega og:title, og:description, og:image para mejor apariencia en redes sociales', 5)];
    }

    private function checkHttps(string $url): array
    {
        return str_starts_with($url, 'https://')
            ? [$this->ok('https_ok', 'La página usa HTTPS')]
            : [$this->issue('error', 'https_missing', 'La página no usa HTTPS', 'Migra a HTTPS — es un factor de ranking en Google y protege a los usuarios', 12)];
    }

    private function checkStructuredData(string $html): array
    {
        return str_contains($html, 'application/ld+json')
            ? [$this->ok('schema_ok', 'Datos estructurados (Schema.org/JSON-LD) presentes')]
            : [$this->issue('warning', 'schema_missing', 'No hay datos estructurados', 'Agrega Schema.org markup para mejorar la apariencia en resultados de búsqueda', 5)];
    }

    private function runMetaChecks(SeoMeta $meta): array
    {
        return [
            ...$this->checkMetaTitle($meta),
            ...$this->checkMetaDescription($meta),
            $this->checkMetaOgImage($meta),
            $this->checkMetaRobots($meta),
        ];
    }

    private function checkMetaTitle(SeoMeta $meta): array
    {
        $len = mb_strlen($meta->title ?? '');

        if ($len === 0) {
            return [$this->issue('error', 'title_missing', 'Falta el título SEO', 'Define un título SEO para esta página', 20)];
        }

        if ($len < 30) {
            return [$this->issue('warning', 'title_short', "Título muy corto ({$len} car.)", 'El título debe tener 30-60 caracteres', 10)];
        }

        if ($len > 60) {
            return [$this->issue('warning', 'title_long', "Título muy largo ({$len} car.)", 'Reduce a menos de 60 caracteres', 5)];
        }

        return [$this->ok('title_ok', "Título correcto ({$len} car.)")];
    }

    private function checkMetaDescription(SeoMeta $meta): array
    {
        $len = mb_strlen($meta->description ?? '');

        if ($len === 0) {
            return [$this->issue('error', 'description_missing', 'Falta la meta descripción', 'Define una descripción de 120-160 caracteres', 15)];
        }

        if ($len < 80) {
            return [$this->issue('warning', 'description_short', "Descripción corta ({$len} car.)", 'Extiende a al menos 120 caracteres', 8)];
        }

        if ($len > 160) {
            return [$this->issue('warning', 'description_long', "Descripción larga ({$len} car.)", 'Reduce a menos de 160 caracteres', 5)];
        }

        return [$this->ok('description_ok', "Descripción correcta ({$len} car.)")];
    }

    private function checkMetaOgImage(SeoMeta $meta): array
    {
        return ! empty($meta->og_image)
            ? $this->ok('og_image_ok', 'Imagen Open Graph definida')
            : $this->issue('warning', 'og_image_missing', 'Falta imagen Open Graph', 'Agrega una imagen para compartir en redes sociales (1200×630px recomendado)', 5);
    }

    private function checkMetaRobots(SeoMeta $meta): array
    {
        $robots = $meta->robots ?? 'index, follow';

        return str_contains($robots, 'noindex')
            ? $this->issue('warning', 'noindex', 'Página marcada como noindex', 'Esta página no será indexada por los buscadores. Verifica si es intencional.', 0)
            : $this->ok('robots_ok', "Robots: {$robots}");
    }

    private function issue(string $status, string $code, string $message, string $recommendation, int $weight): array
    {
        return compact('status', 'code', 'message', 'recommendation', 'weight');
    }

    private function ok(string $code, string $message): array
    {
        return ['status' => 'ok', 'code' => $code, 'message' => $message, 'recommendation' => '', 'weight' => 0];
    }

    private function scoreToGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }

    /**
     * Validate that a URL resolves to a public (non-private) IP address to prevent SSRF.
     *
     * @throws \RuntimeException
     */
    private function validatePublicUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new \RuntimeException('Solo se permiten URLs http/https');
        }

        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);

        $isPrivate = ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivate) {
            throw new \RuntimeException('No se permiten URLs de redes privadas o reservadas');
        }
    }

    private function errorResult(string $message): array
    {
        return [
            'url' => '',
            'score' => 0,
            'grade' => 'F',
            'issues' => [[
                'status' => 'error',
                'code' => 'access_error',
                'message' => $message,
                'recommendation' => '',
                'weight' => 100,
            ]],
            'passed' => [],
            'total_checks' => 1,
            'audited_at' => now()->toIso8601String(),
        ];
    }
}
