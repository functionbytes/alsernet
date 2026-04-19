<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Models\MediaFile;

class ModerationController extends Controller
{
    public function queue(): JsonResponse
    {
        $this->authorize('manage', MediaFile::class);

        $items = MediaFile::query()
            ->where(function ($q): void {
                $q->whereRaw("JSON_EXTRACT(metadata, '$.pii_warnings') IS NOT NULL")
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.ai_tags') IS NOT NULL");
            })
            ->where('visibility', 'public')
            ->with('user:id,name')
            ->paginate(20);

        return response()->json(['items' => $items]);
    }

    public function approve(MediaFile $file): JsonResponse
    {
        $this->authorize('manage', $file);

        $metadata = $file->metadata ?? [];
        $metadata['moderation'] = [
            'status' => 'approved',
            'by' => auth()->id(),
            'at' => now()->toIso8601String(),
        ];

        $file->update(['metadata' => $metadata]);

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('manage', $file);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $metadata = $file->metadata ?? [];
        $metadata['moderation'] = [
            'status' => 'rejected',
            'by' => auth()->id(),
            'reason' => $request->input('reason'),
            'at' => now()->toIso8601String(),
        ];

        $file->update([
            'metadata' => $metadata,
            'visibility' => 'private',
        ]);

        return response()->json(['success' => true]);
    }
}
