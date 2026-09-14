<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Events\MediaFileWorkflowChanged;
use Modules\Media\Models\MediaFile;

class MediaWorkflowController extends Controller
{
    public function submit(MediaFile $file): JsonResponse
    {
        if ($file->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'No puedes enviar este archivo a revision.'], 403);
        }

        if ($file->workflow_status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Solo archivos en borrador pueden enviarse a revision.'], 422);
        }

        $from = $file->workflow_status;
        $file->update(['workflow_status' => 'review']);

        MediaFileWorkflowChanged::dispatch($file, $from, 'review', auth()->id());

        return response()->json(['success' => true, 'message' => 'Archivo enviado a revision.', 'data' => $file]);
    }

    public function approve(MediaFile $file): JsonResponse
    {
        $user = auth()->user();

        if (! $user->can('media.manage')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para aprobar archivos.'], 403);
        }

        if ($file->workflow_status !== 'review') {
            return response()->json(['success' => false, 'message' => 'Solo archivos en revision pueden aprobarse.'], 422);
        }

        $from = $file->workflow_status;
        $file->update([
            'workflow_status' => 'approved',
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        MediaFileWorkflowChanged::dispatch($file, $from, 'approved', $user->id);

        return response()->json(['success' => true, 'message' => 'Archivo aprobado.', 'data' => $file]);
    }

    public function reject(Request $request, MediaFile $file): JsonResponse
    {
        if (! $request->user()->can('media.manage')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para rechazar archivos.'], 403);
        }

        if ($file->workflow_status !== 'review') {
            return response()->json(['success' => false, 'message' => 'Solo archivos en revision pueden rechazarse.'], 422);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $from = $file->workflow_status;
        $file->update(['workflow_status' => 'draft']);

        MediaFileWorkflowChanged::dispatch($file, $from, 'draft', $request->user()->id);

        return response()->json(['success' => true, 'message' => 'Archivo devuelto a borrador.', 'data' => $file]);
    }

    public function archive(MediaFile $file): JsonResponse
    {
        $user = auth()->user();

        if ($file->user_id !== $user->id && ! $user->can('media.manage')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para archivar este archivo.'], 403);
        }

        $from = $file->workflow_status;
        $file->update(['workflow_status' => 'archived']);

        MediaFileWorkflowChanged::dispatch($file, $from, 'archived', $user->id);

        return response()->json(['success' => true, 'message' => 'Archivo archivado.', 'data' => $file]);
    }
}
