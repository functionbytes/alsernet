<?php

namespace Modules\HelpdeskSocial\Services\Classifiers;

use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Models\SocialComment;

class RulesIntentClassifier implements IntentClassifierInterface
{
    private const RULES = [
        'complaint' => [
            'keywords' => ['malo', 'pesimo', 'terrible', 'horrible', 'defectuoso', 'roto', 'falla', 'queja', 'reclamo', 'insatisfecho', 'decepcionado', 'nunca', 'tarda', 'demora', 'no funciona', 'problema', 'error', 'fraude', 'estafa', 'engano'],
            'urgency' => 'high',
        ],
        'purchase_interest' => [
            'keywords' => ['precio', 'cuanto cuesta', 'disponible', 'comprar', 'ordenar', 'pedido', 'cotizar', 'cotizacion', 'descuento', 'promocion', 'oferta', 'stock', 'envio', 'despacho', 'cuanto vale', 'me interesa', 'quiero'],
            'urgency' => 'medium',
        ],
        'query' => [
            'keywords' => ['como', 'donde', 'cuando', 'por que', 'que', 'cual', 'info', 'informacion', 'consulta', 'pregunta', 'saber', 'ayuda', 'duda', 'tutorial', 'guia'],
            'urgency' => 'low',
        ],
        'positive' => [
            'keywords' => ['excelente', 'genial', 'maravilloso', 'increible', 'fantastico', 'perfecto', 'me encanta', 'muy bueno', 'recomiendo', 'gracias', 'feliz', 'satisfecho', 'bravo', 'me gusta', 'love', 'amazing', 'great'],
            'urgency' => 'low',
        ],
        'spam' => [
            'keywords' => ['gratis', 'click aqui', 'gana', 'premio', 'loteria', 'sorteo', 'dinero facil', 'trabajo desde casa', 'cripto', 'bitcoin', 'mineria', 'mlm', 'piramide', 'sexo', 'hot', 'xxx', 'porno', 'viagra'],
            'urgency' => 'low',
        ],
    ];

    public function getIdentifier(): string
    {
        return 'rules';
    }

    public function classify(SocialComment $comment): array
    {
        return $this->classifyText($comment->body);
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getConfidenceThreshold(): float
    {
        return config('helpdesksocial.intent_classification.confidence_threshold', 0.75);
    }

    /**
     * @return array<string, mixed>
     */
    public function classifyText(string $text): array
    {
        $textLower = mb_strtolower($text);
        $bestIntent = 'neutral';
        $bestScore = 0;
        $matchedKeywords = [];
        $bestUrgency = 'low';

        foreach (self::RULES as $intent => $rule) {
            $score = 0;
            $matched = [];

            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($textLower, $keyword)) {
                    $score += 1;
                    $matched[] = $keyword;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIntent = $intent;
                $matchedKeywords = $matched;
                $bestUrgency = $rule['urgency'];
            }
        }

        $confidence = min($bestScore / 3, 1.0);

        return [
            'intent' => $bestIntent,
            'confidence' => round($confidence, 2),
            'classifier' => $this->getIdentifier(),
            'urgency' => $bestUrgency,
            'keywords_matched' => $matchedKeywords,
        ];
    }
}
