<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Locales\Models\Locale;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewTranslation;

class TranslateAllReviewsCommand extends Command
{
    protected $signature = 'reviews:translate-all
                            {--locale= : Traducir solo a este código de idioma}
                            {--force : Re-traducir aunque ya exista traducción}';

    protected $description = 'Traduce todas las reseñas a todos los idiomas activos usando MyMemory API';

    private const MYMEMORY_URL = 'https://api.mymemory.translated.net/get';

    private const GTRANS_URL = 'https://translate.googleapis.com/translate_a/single';

    public function handle(): int
    {
        $locales = Locale::where('is_active', true)->orderBy('order')->get();

        if ($this->option('locale')) {
            $locales = $locales->where('code', $this->option('locale'));
        }

        if ($locales->isEmpty()) {
            $this->error('No hay idiomas activos configurados.');

            return self::FAILURE;
        }

        $reviews = Review::query()
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with('translations')
            ->get(['id', 'comment']);

        $this->info("Reseñas con comentario: {$reviews->count()}");
        $this->info('Idiomas: '.$locales->pluck('code')->join(', '));
        $this->newLine();

        $total = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($locales as $locale) {
            $targetCode = strtoupper($locale->code);
            $this->line("── Traduciendo a <comment>{$targetCode}</comment> ({$locale->name})...");

            $bar = $this->output->createProgressBar($reviews->count());
            $bar->start();

            foreach ($reviews as $review) {
                $alreadyExists = $review->translations
                    ->where('locale_code', $targetCode)
                    ->isNotEmpty();

                if ($alreadyExists && ! $this->option('force')) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $translated = $this->translateText($review->comment, $targetCode);

                if ($translated === null) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                ReviewTranslation::updateOrCreate(
                    ['review_id' => $review->id, 'locale_code' => $targetCode],
                    [
                        'translated_text' => $translated,
                        'detected_language' => 'ES',
                        'translated_at' => now(),
                    ]
                );

                $total++;
                $bar->advance();

                // Pequeña pausa para respetar rate limits de MyMemory
                usleep(300000); // 300ms
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("✓ Traducidas:  {$total}");
        $this->line("  Saltadas:    {$skipped} (ya existían)");
        if ($errors > 0) {
            $this->warn("✗ Errores:     {$errors}");
        }

        return self::SUCCESS;
    }

    private function translateText(string $text, string $targetLang): ?string
    {
        // Si el idioma destino es el mismo que el origen, devolver el texto original
        if ($targetLang === 'ES') {
            return $text;
        }

        // MyMemory acepta max ~500 chars por request; si es más largo, trocear
        if (mb_strlen($text) > 480) {
            return $this->translateInChunks($text, $targetLang);
        }

        return $this->callMyMemory($text, 'ES', $targetLang);
    }

    private function translateInChunks(string $text, string $targetLang): ?string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text);
        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current) + mb_strlen($sentence) > 480) {
                if ($current !== '') {
                    $chunks[] = trim($current);
                }
                $current = $sentence;
            } else {
                $current .= ($current ? ' ' : '').$sentence;
            }
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        $parts = [];
        foreach ($chunks as $chunk) {
            $result = $this->callMyMemory($chunk, 'ES', $targetLang);
            if ($result === null) {
                return null;
            }
            $parts[] = $result;
            usleep(200000); // 200ms entre chunks
        }

        return implode(' ', $parts);
    }

    private function callMyMemory(string $text, string $from, string $to): ?string
    {
        try {
            $response = Http::timeout(15)->get(self::MYMEMORY_URL, [
                'q' => $text,
                'langpair' => "{$from}|{$to}",
                'de' => 'analyticfitcamps21@gmail.com',
            ]);

            if ($response->failed()) {
                return $this->callGoogleTranslate($text, $from, $to);
            }

            $data = $response->json();

            if (($data['responseStatus'] ?? 0) !== 200) {
                return $this->callGoogleTranslate($text, $from, $to);
            }

            return $data['responseData']['translatedText'] ?? null;
        } catch (\Throwable $e) {
            return $this->callGoogleTranslate($text, $from, $to);
        }
    }

    private function callGoogleTranslate(string $text, string $from, string $to): ?string
    {
        try {
            $response = Http::timeout(15)->get(self::GTRANS_URL, [
                'client' => 'gtx',
                'sl' => strtolower($from),
                'tl' => strtolower($to),
                'dt' => 't',
                'q' => $text,
            ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            $parts = array_column($data[0] ?? [], 0);

            return implode('', array_filter($parts)) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
