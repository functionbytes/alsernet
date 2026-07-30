<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Base común para los webhooks de Meta (WhatsApp / Facebook / Instagram): el
 * challenge de verificación (GET) es idéntico en los tres canales. Cada subclase
 * aporta cómo resolver el challenge (delegando en su servicio) y su etiqueta, y
 * define su propio handle() porque el parseo/despacho sí difiere por canal.
 */
abstract class MetaWebhookController extends Controller
{
    abstract protected function verifyChallenge(string $mode, string $challenge, string $token): string|false;

    abstract protected function channelLabel(): string;

    /**
     * Handle Meta webhook verification challenge (GET).
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->verifyChallenge(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            Log::warning($this->channelLabel().' webhook verify failed — bad token', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Ejecuta el parseo/despacho del webhook y SIEMPRE responde 200, incluso si
     * un payload con shape inesperado (campos que Meta añade o envía en casos
     * borde) lanza una excepción. Un 500 haría que Meta reintente el mismo
     * evento indefinidamente; aquí se registra y se descarta ese evento.
     *
     * @param  callable(): void  $handler
     */
    protected function safelyHandle(callable $handler): JsonResponse
    {
        try {
            $handler();
        } catch (\Throwable $e) {
            Log::error($this->channelLabel().' webhook handling failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
