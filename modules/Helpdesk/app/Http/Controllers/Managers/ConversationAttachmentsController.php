<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Http\Requests\ForwardAttachmentRequest;
use Modules\Helpdesk\Http\Requests\StoreContactItemRequest;
use Modules\Helpdesk\Http\Requests\StoreLocationItemRequest;
use Modules\Helpdesk\Http\Requests\UploadAttachmentRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AttachmentSecurityService;
use Modules\Helpdesk\Services\HelpdeskSettings;
use Modules\Helpdesk\Services\OutboundMessageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File/contact/location attachments for a conversation: upload, forced
 * download, cross-conversation forward, contact cards and location pins
 * (extracted from ConversationsController — QUAL-03).
 */
class ConversationAttachmentsController extends Controller
{
    public function __construct(
        private readonly AttachmentSecurityService $attachmentSecurity,
        private readonly HelpdeskSettings $settings,
    ) {
        $this->middleware('can:helpdesk.conversations.update')->only(['uploadAttachments', 'storeContact', 'storeLocation']);
    }

    /**
     * Upload one or more file attachments to a conversation.
     * Images > 1 MB are compressed (max 1920px, JPEG 80%) before saving.
     */
    public function uploadAttachments(UploadAttachmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->loadMissing('customer');
        $customerId = $conversation->customer_id;
        $dateFolder = now()->format('Y-m-d');

        $attachments = [];

        foreach ($request->file('files') as $file) {
            // The request validates extension/MIME. This final check runs just
            // before persistence so the conversation inbox has the same
            // malware boundary as tickets and portal uploads.
            $this->attachmentSecurity->assertSafe($file);

            $mime = $file->getMimeType() ?? 'application/octet-stream';
            $folder = "helpdesk/customers/{$customerId}/conversations/{$conversation->id}/{$dateFolder}";
            $disk = Storage::disk('public');

            if ($this->shouldCompressImage($mime, $file->getSize())) {
                [$filename, $storedMime, $storedSize] = $this->compressAndStoreImage($file, $folder, $disk);
            } else {
                $filename = $file->hashName();
                $disk->putFileAs($folder, $file, $filename);
                $storedMime = $mime;
                $storedSize = $file->getSize();
            }

            $attachments[] = [
                'url' => $disk->url("{$folder}/{$filename}"),
                'name' => $file->getClientOriginalName(),
                'size' => $storedSize,
                'mime' => $storedMime,
                'mime_type' => $storedMime,
                'type' => $this->resolveAttachmentType($storedMime),
                'path' => "{$folder}/{$filename}",
            ];
        }

        $item = DB::transaction(function () use ($conversation, $attachments): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'message',
                'body' => '',
                'is_internal' => false,
                // Store rich objects (matches widget format) so the thread renderer
                // has {url, name, size, mime_type} for image/audio/video/document.
                'attachment_urls' => $attachments,
                'metadata' => ['attachments' => $attachments],
            ]);
        });

        // Forward each attachment to the customer through the channel API.
        $outbound = app(OutboundMessageService::class);
        if ($outbound->supports($conversation)) {
            $externalIds = [];
            foreach ($attachments as $att) {
                try {
                    $absUrl = $this->absoluteUrl((string) $att['url']);
                    $externalId = $outbound->sendAttachment(
                        $conversation,
                        (string) ($att['type'] ?? 'file'),
                        $absUrl,
                        null,
                        $att['name'] ?? null,
                    );
                    if ($externalId) {
                        $externalIds[] = $externalId;
                    }
                } catch (\Throwable $e) {
                    Log::channel('helpdesk')->error('uploadAttachments: outbound send failed', [
                        'conversation_id' => $conversation->id,
                        'channel' => $conversation->channel,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            if ($externalIds) {
                $item->external_id = $externalIds[0];
                $item->save();
            }
        }

        broadcast(new ConversationMessageCreated($item, false))->toOthers();

        // NOTE: MessageReceived (widget channel) is now broadcast by the
        // ConversationItemLinkPreviewObserver — single source of truth.

        return response()->json([
            'success' => true,
            'message' => count($attachments).' archivo(s) adjunto(s) correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'type' => $item->type,
                'attachment_urls' => $attachments,
                'attachments' => $attachments,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    public function downloadAttachment(Request $request): StreamedResponse
    {
        $url = trim((string) $request->input('url', ''));
        if ($url === '') {
            abort(400, 'URL requerida');
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            abort(400, 'URL inválida');
        }

        // Solo permitimos paths bajo /storage/helpdesk/ por seguridad
        if (! preg_match('#^/storage/(helpdesk/.+)$#', $path, $m)) {
            abort(403, 'Path no permitido');
        }
        $relPath = $m[1];

        // El adjunto debe pertenecer a una conversación a la que el agente
        // tenga acceso (evita IDOR: descargar adjuntos de inboxes ajenos).
        $item = ConversationItem::query()
            ->where('attachment_urls', 'like', '%'.$relPath.'%')
            ->with('conversation')
            ->first();

        if (! $item?->conversation) {
            abort(404, 'Archivo no encontrado');
        }

        $this->authorize('view', $item->conversation);

        $disk = Storage::disk('public');
        if (! $disk->exists($relPath)) {
            abort(404, 'Archivo no encontrado');
        }

        $filename = basename($relPath);

        return $disk->download($relPath, $filename, [
            'Content-Type' => $disk->mimeType($relPath) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Forward an attachment from one conversation to another.
     */
    public function forwardAttachment(ForwardAttachmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        // Extract path from source URL — only allow our own storage
        $url = $validated['source_url'];
        $storageBase = config('app.url').'/storage/';
        $altBase = url('/storage/').'/';
        $path = null;

        foreach ([$storageBase, $altBase] as $base) {
            if (str_starts_with($url, $base)) {
                $path = substr($url, strlen($base));
                break;
            }
        }
        // Also accept raw path within helpdesk/customers/...
        if (! $path && preg_match('#/storage/(helpdesk/customers/.+)$#', $url, $m)) {
            $path = $m[1];
        }

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Archivo no encontrado en almacenamiento.'], 404);
        }

        // Autorizar sobre la conversación ORIGEN del adjunto (defense-in-depth,
        // igual que downloadAttachment): se busca el ConversationItem dueño del
        // path por attachment_urls en vez de confiar en el formato del path —
        // rutas de social-media/attachments no llevan .../conversations/{id}/...
        // y antes se copiaban SIN comprobación alguna (IDOR entre inboxes).
        $sourceItem = ConversationItem::query()
            ->where('attachment_urls', 'like', '%'.$path.'%')
            ->with('conversation')
            ->first();

        if (! $sourceItem?->conversation) {
            return response()->json(['success' => false, 'message' => 'Archivo no encontrado en almacenamiento.'], 404);
        }

        $this->authorize('view', $sourceItem->conversation);

        // Legacy files may predate ClamAV being enabled. Re-scan on copy so a
        // forward cannot become a bypass of the current security policy.
        $this->attachmentSecurity->assertSafeStored('public', $path, basename($path));

        $customerId = $conversation->customer_id ?: 0;
        $convId = $conversation->id;
        $newPath = "helpdesk/customers/{$customerId}/conversations/{$convId}/".now()->format('Y-m-d').'/'.basename($path);

        Storage::disk('public')->copy($path, $newPath);
        $newUrl = Storage::disk('public')->url($newPath);

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'type' => 'message',
            'body' => 'Archivo reenviado: '.($validated['original_name'] ?? basename($path)),
            'attachment_urls' => [$newUrl],
            'is_internal' => false,
            'metadata' => ['forwarded_from' => $validated['source_url']],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Archivo reenviado correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'attachment_urls' => $item->attachment_urls,
                'time' => $item->created_at->format('H:i'),
            ],
        ], 201);
    }

    /**
     * Store a contact card as a conversation item.
     */
    public function storeContact(StoreContactItemRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        $item = DB::transaction(function () use ($conversation, $validated): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'contact',
                'body' => $validated['name'],
                'is_internal' => false,
                'metadata' => [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Contacto compartido.',
            'item' => [
                'id' => $item->id,
                'type' => 'contact',
                'metadata' => $item->metadata,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    /**
     * Store a location pin as a conversation item.
     */
    public function storeLocation(StoreLocationItemRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        $item = DB::transaction(function () use ($conversation, $validated): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'location',
                'body' => $validated['address'] ?? "{$validated['lat']},{$validated['lng']}",
                'is_internal' => false,
                'metadata' => [
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                    'address' => $validated['address'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ubicación compartida.',
            'item' => [
                'id' => $item->id,
                'type' => 'location',
                'metadata' => $item->metadata,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    /**
     * Resolve a possibly-relative storage URL to an absolute one, rewriting
     * the app URL to the configured public URL when they differ (e.g. the
     * container-internal APP_URL vs the public-facing helpdesk.public_url).
     * Shared with ConversationsController::deliverForwardedItem() — kept as a
     * duplicated private helper rather than a trait to match this module's
     * existing pattern of small, single-purpose Managers controllers.
     */
    private function absoluteUrl(string $url): string
    {
        $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (preg_match('#^https?://#i', $url)) {
            if ($appUrl && $base !== $appUrl && str_starts_with($url, $appUrl.'/')) {
                return $base.substr($url, strlen($appUrl));
            }

            return $url;
        }

        return $base.'/'.ltrim($url, '/');
    }

    /**
     * Whether an uploaded image should be compressed before storage.
     * GIFs are skipped (animation). PNGs with alpha are skipped (transparency).
     */
    private function shouldCompressImage(string $mime, int $bytes): bool
    {
        if (! $this->settings->imageCompressionEnabled() || ! str_starts_with($mime, 'image/')) {
            return false;
        }

        if ($bytes <= 1 * 1024 * 1024) {
            return false;
        }

        // Skip GIF (animation)
        if ($mime === 'image/gif') {
            return false;
        }

        return true;
    }

    /**
     * Compress an image: resize to max 1920px on the longest side, save as JPEG 80%.
     * PNG images with alpha channel are also saved as JPEG (alpha is not preserved).
     *
     * Returns [filename, mime, size] of the stored file.
     */
    private function compressAndStoreImage(UploadedFile $file, string $folder, Filesystem $disk): array
    {
        $originalBytes = $file->getSize();
        $manager = new ImageManager(new GdDriver);

        $image = $manager->read($file->getRealPath());

        // Skip PNG with alpha channel (preserve transparency)
        if ($file->getMimeType() === 'image/png') {
            $gd = $image->core()->native();
            if (imageistruecolor($gd) && imagecolorsforindex($gd, 0)['alpha'] > 0) {
                $filename = $file->hashName();
                $disk->putFileAs($folder, $file, $filename);

                return [$filename, 'image/png', $originalBytes];
            }
        }

        // Resize: max 1920px on the longest side, keep aspect ratio
        $image->scaleDown(
            width: $this->settings->imageMaxWidth(),
            height: $this->settings->imageMaxHeight(),
        );

        $encoded = $image->toJpeg(quality: $this->settings->imageQuality());
        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME).'.jpg';
        $disk->put("{$folder}/{$filename}", $encoded->toString());

        $storedSize = $disk->size("{$folder}/{$filename}");
        $savedKb = round(($originalBytes - $storedSize) / 1024);

        Log::info('Helpdesk: image compressed', [
            'original_bytes' => $originalBytes,
            'stored_bytes' => $storedSize,
            'saved_kb' => $savedKb,
            'file' => $filename,
        ]);

        return [$filename, 'image/jpeg', $storedSize];
    }

    /**
     * Determine the semantic attachment type from a MIME type.
     */
    private function resolveAttachmentType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }
}
