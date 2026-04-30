<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\ConversationAttachmentsService;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class AttachmentSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
        private readonly ConversationAttachmentsService $attachments,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.conversation_attachments', [
            'enabled' => true,
            'max_file_size_mb' => 10,
            'max_attachments' => 5,
            'storage_disk' => 'public',
            'allowed_types' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $disksMeta = $this->attachments->buildDisksMeta();

        return view('Chat::settings.conversation-attachments', compact('settings', 'disksMeta'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'max_file_size_mb' => 'required|integer|min:1|max:100',
            'max_attachments' => 'required|integer|min:1|max:20',
            'storage_disk' => 'required|string|in:'.implode(',', array_keys(config('filesystems.disks', []))),
            'allowed_types' => 'nullable|array',
            'allowed_types.*' => 'string',
        ]);

        $old = $this->settings->get('chat.conversation_attachments', []);

        $validated['enabled'] = (bool) ($validated['enabled'] ?? false);
        $validated['allowed_types'] = $validated['allowed_types'] ?? [];

        $this->settings->save('chat.conversation_attachments', $validated);

        activity()
            ->withProperties(['old_disk' => $old['storage_disk'] ?? null, 'new_disk' => $validated['storage_disk']])
            ->log('Configuración de adjuntos actualizada');

        return back()->with('success', 'Configuración de adjuntos actualizada correctamente');
    }

    public function diskStats(string $disk): JsonResponse
    {
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            return response()->json(['success' => false, 'message' => 'Disco no permitido'], 403);
        }

        $stats = $this->attachments->diskStats($disk);

        return response()->json(['success' => true, 'stats' => $stats]);
    }

    public function history(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'history' => $this->attachments->recentHistory(),
        ]);
    }
}
