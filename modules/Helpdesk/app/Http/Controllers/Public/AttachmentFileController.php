<?php

namespace Modules\Helpdesk\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Services\ConversationAttachmentStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve adjuntos de conversación guardados en un disco privado mediante URL
 * firmada (middleware signed:relative). Ver ConversationAttachmentStorage.
 * Seguridad 29-sep-2026 (A9).
 */
class AttachmentFileController extends Controller
{
    public function __invoke(Request $request, ConversationAttachmentStorage $storage, string $path): StreamedResponse
    {
        $disk = $storage->allowedDisk($request->query('disk'));

        abort_unless($disk && $storage->safePath($path), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $storage->response($disk, $path);
    }
}
