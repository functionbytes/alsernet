<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Get all media for a conversation.
     */
    public function index(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $media = $conversation->messages()
            ->with('media')
            ->get()
            ->pluck('media')
            ->flatten()
            ->groupBy(function ($item) {
                if (str_starts_with($item->mime_type, 'image/')) {
                    return 'images';
                } elseif (str_starts_with($item->mime_type, 'video/')) {
                    return 'videos';
                } elseif (str_starts_with($item->mime_type, 'audio/')) {
                    return 'audio';
                } else {
                    return 'files';
                }
            });

        return response()->json([
            'images' => $media->get('images', collect())->values(),
            'videos' => $media->get('videos', collect())->values(),
            'audio' => $media->get('audio', collect())->values(),
            'files' => $media->get('files', collect())->values(),
        ]);
    }

    /**
     * Download a media file.
     */
    public function download(Media $media): Response
    {
        // Authorize - check if user has access to the conversation this media belongs to
        $message = $media->model;
        if ($message && $message->conversation) {
            $this->authorize('view', $message->conversation);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Delete a media file.
     */
    public function destroy(Media $media): JsonResponse
    {
        // Authorize - check if user has access to the conversation this media belongs to
        $message = $media->model;
        if ($message && $message->conversation) {
            $this->authorize('update', $message->conversation);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get media preview URL.
     */
    public function preview(Media $media): Response
    {
        // Authorize - check if user has access to the conversation this media belongs to
        $message = $media->model;
        if ($message && $message->conversation) {
            $this->authorize('view', $message->conversation);
        }

        $path = $media->getPath();
        $mimeType = $media->mime_type;

        // For images, return medium-sized conversion if available
        if (str_starts_with($mimeType, 'image/')) {
            try {
                $path = $media->getPath('medium');
            } catch (\Exception $e) {
                // Fallback to optimized, then original
                try {
                    $path = $media->getPath('optimized');
                } catch (\Exception $e) {
                    $path = $media->getPath();
                }
            }
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }
}
