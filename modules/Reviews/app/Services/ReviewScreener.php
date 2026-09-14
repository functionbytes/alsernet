<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Reviews\Models\Review;

/**
 * Cribado automático de las opiniones pendientes.
 *
 * No aprueba ni rechaza nada: marca lo que parece spam, insulto, dato personal
 * o texto que no habla del producto, y dice por qué. Con 1.769 esperando, sirve
 * para no empezar siempre por la primera de la lista.
 *
 * Se puede apagar desde los ajustes del módulo; apagado, todo sigue funcionando
 * exactamente igual, solo que nadie marca nada.
 */
class ReviewScreener
{
    public const CLEAN = 'clean';

    public const SPAM = 'spam';

    public const OFFENSIVE = 'offensive';

    public const PERSONAL_DATA = 'personal_data';

    public const OFF_TOPIC = 'off_topic';

    public const UNCLEAR = 'unclear';

    /** Cómo se lee cada veredicto en el panel. */
    public const LABELS = [
        self::CLEAN => 'Sin problemas',
        self::SPAM => 'Parece spam',
        self::OFFENSIVE => 'Lenguaje ofensivo',
        self::PERSONAL_DATA => 'Contiene datos personales',
        self::OFF_TOPIC => 'No habla del producto',
        self::UNCLEAR => 'Dudosa',
    ];

    public function __construct(private readonly AiClient $ai) {}

    public static function isEnabled(): bool
    {
        return (bool) Setting::get('reviews.screening.enabled', false);
    }

    /** Qué se marca y qué no, según los ajustes. */
    public static function watchedFlags(): array
    {
        $guardados = Setting::get('reviews.screening.flags');

        if (is_string($guardados)) {
            $guardados = json_decode($guardados, true);
        }

        return is_array($guardados) && $guardados
            ? $guardados
            : [self::SPAM, self::OFFENSIVE, self::PERSONAL_DATA, self::OFF_TOPIC];
    }

    public function isAvailable(): bool
    {
        return self::isEnabled() && $this->ai->isEnabled();
    }

    /**
     * @return array{ok: bool, verdict: ?string, message: string}
     */
    public function screen(Review $review): array
    {
        if (! self::isEnabled()) {
            return ['ok' => false, 'verdict' => null, 'message' => 'La revisión asistida está desactivada en los ajustes.'];
        }

        if (! $this->ai->isEnabled()) {
            return ['ok' => false, 'verdict' => null, 'message' => 'El servicio de IA no está configurado.'];
        }

        $texto = trim(strip_tags((string) $review->comment));

        if ($texto === '') {
            return ['ok' => false, 'verdict' => null, 'message' => 'La opinión no tiene texto.'];
        }

        $respuesta = $this->ai->chat([
            ['role' => 'system', 'content' => $this->instructions()],
            ['role' => 'user', 'content' => $this->prompt($review, $texto)],
        ]);

        if (! $respuesta) {
            return ['ok' => false, 'verdict' => null, 'message' => 'La IA no respondió.'];
        }

        $datos = $this->parse($respuesta);

        if (! $datos) {
            Log::warning('Reviews: respuesta de cribado ilegible.', [
                'review_id' => $review->id,
                'respuesta' => mb_substr($respuesta, 0, 200),
            ]);

            return ['ok' => false, 'verdict' => null, 'message' => 'No se entendió la respuesta de la IA.'];
        }

        $review->update([
            'screening' => $datos['verdict'],
            'screening_reason' => $datos['reason'],
            'screening_confidence' => $datos['confidence'],
            'screened_at' => now(),
        ]);

        return [
            'ok' => true,
            'verdict' => $datos['verdict'],
            'message' => self::LABELS[$datos['verdict']] ?? $datos['verdict'],
        ];
    }

    private function instructions(): string
    {
        $marcar = implode(', ', self::watchedFlags());

        return <<<TXT
        Revisas opiniones de clientes de una tienda de caza, pesca y deportes de
        montaña, antes de que un humano decida si se publican.

        No decides tú: solo señalas problemas. Ante la duda, "unclear".

        Responde SOLO con un JSON: {"verdict": "...", "reason": "...", "confidence": 0-100}

        Valores posibles de verdict:
        - clean: se puede publicar, no ves problema
        - spam: publicidad, enlaces, texto repetido sin sentido
        - offensive: insultos o lenguaje que no debe publicarse
        - personal_data: teléfonos, correos, direcciones o números de pedido
        - off_topic: no habla del producto ni de la compra
        - unclear: no lo tienes claro

        Marca solo estos casos: {$marcar}. El resto, clean.

        "reason": una frase corta en español explicando por qué, para quien lo
        revise. Una opinión muy negativa pero legítima es clean: criticar el
        producto no es un problema.
        TXT;
    }

    private function prompt(Review $review, string $texto): string
    {
        $producto = $review->product_name ?: 'un producto de la tienda';

        return "Producto: {$producto}\nValoración: {$review->rating} de 5\nOpinión: {$texto}";
    }

    private function parse(string $respuesta): ?array
    {
        // La IA a veces envuelve el JSON en un bloque de código.
        if (preg_match('/\{.*\}/s', $respuesta, $m)) {
            $respuesta = $m[0];
        }

        $datos = json_decode($respuesta, true);

        if (! is_array($datos) || ! isset($datos['verdict'])) {
            return null;
        }

        $verdict = (string) $datos['verdict'];

        if (! array_key_exists($verdict, self::LABELS)) {
            return null;
        }

        return [
            'verdict' => $verdict,
            'reason' => mb_substr((string) ($datos['reason'] ?? ''), 0, 500) ?: null,
            'confidence' => max(0, min(100, (int) ($datos['confidence'] ?? 0))),
        ];
    }
}
