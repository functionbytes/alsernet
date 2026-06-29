<?php

namespace Modules\Campaign\Library;

/**
 * Análisis local de spam score para HTML/asunto de email.
 *
 * No reemplaza SpamAssassin/mail-tester, pero detecta los problemas más
 * comunes en segundos sin coste:
 *   - Keywords clasificados como spam (FREE, GUARANTEED, $$$, etc.)
 *   - Exceso de mayúsculas / signos exclamación
 *   - Asunto demasiado largo o vacío
 *   - HTML sin {{UNSUBSCRIBE_URL}} o List-Unsubscribe
 *   - Ratio imagen/texto desbalanceado
 *   - Ausencia de versión plain
 *
 * Devuelve [
 *   'score' => 0..10  (0=ok, 10=muy spammy),
 *   'warnings' => [...]
 * ]
 */
class SpamAnalyzer
{
    /** Keywords típicamente clasificados como spam por filtros bayesianos. */
    public const SPAM_KEYWORDS = [
        'free', 'gratis', '100% free', 'guaranteed', 'guarantee', 'no risk', 'risk-free',
        'click here', 'click below', 'order now', 'buy now', 'act now', 'urgent',
        'limited time', 'offer expires', 'congratulations', 'winner', 'you have won',
        'cash', 'money back', 'million dollars', 'earn money', 'work from home',
        'increase sales', 'increase traffic', 'mlm', 'opt-in', 'this is not spam',
        'remove subject', 'as seen on', 'investment opportunity', 'pre-approved',
        '$$$', 'viagra', 'cialis', 'prescription', 'pharmacy',
    ];

    public static function analyze(string $subject, string $html, ?string $plain = null): array
    {
        $score = 0.0;
        $warnings = [];

        // ── 1. Asunto ──
        $subjectLen = mb_strlen(trim($subject));
        if ($subjectLen === 0) {
            $score += 3;
            $warnings[] = ['severity' => 'critical', 'message' => 'Asunto vacío'];
        } elseif ($subjectLen > 100) {
            $score += 1;
            $warnings[] = ['severity' => 'warning', 'message' => "Asunto muy largo ({$subjectLen} chars). Recomendado <70."];
        }

        $subjectUppers = preg_match_all('/[A-ZÁÉÍÓÚÑ]/', $subject);
        if ($subjectUppers > 0 && $subjectLen > 0 && ($subjectUppers / $subjectLen) > 0.5) {
            $score += 1.5;
            $warnings[] = ['severity' => 'warning', 'message' => 'Asunto con demasiadas mayúsculas'];
        }

        if (substr_count($subject, '!') > 1) {
            $score += 1;
            $warnings[] = ['severity' => 'warning', 'message' => 'Múltiples signos de exclamación en asunto'];
        }

        // ── 2. Spam keywords ──
        $haystack = strtolower($subject.' '.strip_tags($html));
        $keywordHits = [];
        foreach (self::SPAM_KEYWORDS as $kw) {
            if (str_contains($haystack, $kw)) {
                $keywordHits[] = $kw;
            }
        }
        if (! empty($keywordHits)) {
            $score += min(3, count($keywordHits) * 0.5);
            $warnings[] = ['severity' => 'warning', 'message' => 'Spam keywords detectados: '.implode(', ', array_slice($keywordHits, 0, 5))];
        }

        // ── 3. Unsubscribe link ──
        if (! preg_match('/(unsubscribe|baja|UNSUBSCRIBE_URL|List-Unsubscribe)/i', $html)) {
            $score += 2;
            $warnings[] = ['severity' => 'critical', 'message' => 'No se encontró enlace de desuscripción. Imprescindible para CAN-SPAM/GDPR.'];
        }

        // ── 4. Plain text ──
        if (empty($plain) || mb_strlen(trim($plain)) < 50) {
            $score += 1;
            $warnings[] = ['severity' => 'warning', 'message' => 'Sin versión plain text. Filtros prefieren multipart.'];
        }

        // ── 5. Ratio imagen/texto ──
        $imgCount = preg_match_all('/<img\b/i', $html);
        $textLen = mb_strlen(trim(strip_tags($html)));
        if ($imgCount > 0 && $textLen < 100) {
            $score += 2;
            $warnings[] = ['severity' => 'warning', 'message' => "Email solo imagen sin texto suficiente ({$imgCount} imgs / {$textLen} chars de texto)"];
        }

        // ── 6. Emoji / caracteres especiales en asunto ──
        if (preg_match('/[\x{1F000}-\x{1F9FF}]/u', $subject)) {
            // No penalizamos: emojis están aceptados, solo informamos
            $warnings[] = ['severity' => 'info', 'message' => 'Asunto con emojis. Algunos clientes los tratan distinto.'];
        }

        // ── 7. Tracking pixel ──
        if (! preg_match('/track\/open\/|tracking pixel/i', $html)) {
            $warnings[] = ['severity' => 'info', 'message' => 'No se detectó tracking pixel. Si quieres medir aperturas, asegúrate de tener track_open=true en la campaña.'];
        }

        $score = min(10, round($score, 1));

        return [
            'score' => $score,
            'rating' => self::ratingFor($score),
            'warnings' => $warnings,
        ];
    }

    public static function ratingFor(float $score): string
    {
        return match (true) {
            $score < 2 => 'excellent',
            $score < 4 => 'good',
            $score < 6 => 'warning',
            $score < 8 => 'poor',
            default => 'critical',
        };
    }
}
