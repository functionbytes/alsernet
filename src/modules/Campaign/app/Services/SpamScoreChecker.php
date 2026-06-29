<?php

namespace Modules\Campaign\Services;

/**
 * Heurísticas básicas de spam scoring para contenido de email.
 * Devuelve puntuación 0-100 (menor es mejor).
 */
class SpamScoreChecker
{
    protected array $spamWords = [
        'viagra', 'cialis', 'lottery', 'winner', 'congratulations', 'free money',
        'act now', 'urgent', 'limited time', 'click here', 'buy now', 'order now',
        'risk free', 'no obligation', 'call now', ' Act now!', '$$$', '100% free',
        'additional income', 'be your own boss', 'big bucks', 'cash bonus',
        'earn extra cash', 'extra cash', 'earn $', 'fast cash', 'financial freedom',
        'home based', 'make money', 'million dollars', 'miracle', 'money back',
        'no catch', 'no experience', 'not junk', 'not spam', 'pure profit',
        'save big money', 'special promotion', 'web traffic', 'weight loss',
    ];

    public function score(string $html, ?string $subject = null): array
    {
        $score = 0;
        $reasons = [];
        $text = strip_tags($html);
        $lowerText = strtolower($text);
        $lowerSubject = strtolower($subject ?? '');

        // 1. Ratio imagen/texto
        $imgCount = substr_count(strtolower($html), '<img');
        $textLen = max(1, strlen(trim($text)));
        if ($imgCount > 0 && $textLen < 100) {
            $score += 15;
            $reasons[] = 'Muchas imágenes, poco texto ('.$imgCount.' imgs / '.$textLen.' chars)';
        }

        // 2. Palabras spam
        foreach ($this->spamWords as $word) {
            if (str_contains($lowerText, $word) || str_contains($lowerSubject, $word)) {
                $score += 3;
                $reasons[] = "Palabra spam detectada: '{$word}'";
            }
        }

        // 3. Todo mayúsculas en subject
        if ($subject && strtoupper($subject) === $subject && strlen($subject) > 5) {
            $score += 10;
            $reasons[] = 'Subject en MAYÚSCULAS';
        }

        // 4. Exceso de signos de exclamación
        $exclCount = substr_count($lowerText, '!');
        if ($exclCount > 5) {
            $score += 5;
            $reasons[] = "Exceso de signos de exclamación ({$exclCount})";
        }

        // 5. Faltan alt tags
        preg_match_all('/<img[^>]*>/i', $html, $imgTags);
        foreach ($imgTags[0] ?? [] as $tag) {
            if (! preg_match('/alt=/i', $tag)) {
                $score += 2;
                $reasons[] = 'Imagen sin atributo alt';
            }
        }

        // 6. Links sospechosos (URL cortas o IPs)
        if (preg_match('/href="https?:\/\/(bit\.ly|t\.co|tinyurl|goo\.gl)/i', $html)) {
            $score += 10;
            $reasons[] = 'URL acortador detectada';
        }

        // 7. Exceso de links
        preg_match_all('/<a[^>]*href=/i', $html, $links);
        $linkCount = count($links[0] ?? []);
        if ($linkCount > 10) {
            $score += min(10, $linkCount - 10);
            $reasons[] = "Muchos links ({$linkCount})";
        }

        $score = min(100, $score);

        return [
            'score' => $score,
            'risk' => match (true) {
                $score >= 70 => 'high',
                $score >= 40 => 'medium',
                $score >= 20 => 'low',
                default => 'none',
            },
            'reasons' => array_unique($reasons),
        ];
    }
}
