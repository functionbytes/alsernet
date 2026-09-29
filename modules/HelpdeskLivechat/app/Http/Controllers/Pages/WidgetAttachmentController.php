<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskLivechat\Concerns\VerifiesConversationToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve los adjuntos que sube el visitante del widget (29-sep-2026, A6-c).
 *
 * Antes se guardaban en el disco `public` conservando la extensión del
 * cliente (un .html/.svg servido desde /storage = XSS en el dominio del
 * panel). Ahora van al disco privado `local` con nombre hashName() y solo se
 * sirven por aquí, como descarga y con nosniff, a:
 *  - el visitante: URL firmada (la genera el API del widget tras comprobar el
 *    token de la conversación) o cabecera X-Conversation-Token;
 *  - el agente: sesión del panel con permiso `view` sobre la conversación.
 */
class WidgetAttachmentController extends Controller
{
    use VerifiesConversationToken;

    public const ROUTE_NAME = 'helpdesk-livechat.attachments.show';

    public function show(Request $request, int $conversation, string $file): StreamedResponse
    {
        $conv = Conversation::find($conversation);
        abort_unless($conv, 404);

        $allowed = $request->hasValidSignature()
            || $this->conversationTokenValid($conv, $request)
            || ($request->user() && Gate::forUser($request->user())->allows('view', $conv));

        abort_unless($allowed, 403);

        $path = "helpdesk-conversations/{$conv->id}/{$file}";
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        // Tipo real (finfo) solo si es imagen/audio/vídeo/PDF, para que <img>/<audio>
        // del widget (otro origen) no los bloquee ORB; el resto como binario.
        $mime = (string) ($disk->mimeType($path) ?: '');
        $safeMime = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true)
            || preg_match('#^(audio|video)/[a-z0-9.+-]+$#', $mime) === 1;

        return $disk->download($path, $this->originalName($conv->id, $file), [
            'Content-Type' => $safeMime ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Nombre original (solo para mostrar) guardado en attachment_urls; si no se
     * encuentra, el nombre generado.
     */
    private function originalName(int $conversationId, string $file): string
    {
        $item = ConversationItem::query()
            ->where('conversation_id', $conversationId)
            ->where('attachment_urls', 'like', '%'.$file.'%')
            ->first();

        foreach ((array) ($item?->attachment_urls ?? []) as $att) {
            if (is_array($att) && basename((string) ($att['path'] ?? '')) === $file) {
                $name = trim(str_replace(['/', '\\', "\0"], '_', (string) ($att['name'] ?? '')));

                return $name !== '' ? $name : $file;
            }
        }

        return $file;
    }
}
