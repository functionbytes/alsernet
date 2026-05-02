<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaLibraryController extends Controller
{
    public function index()
    {
        $mediaPath = 'social/media/'.auth()->user()->account_id;

        $files = collect(Storage::files($mediaPath))->map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'url' => Storage::url($file),
                'size' => Storage::size($file),
                'modified' => Storage::lastModified($file),
                'type' => $this->getFileType($file),
            ];
        })->sortByDesc('modified')->values();

        return view('social::media.index', compact('files'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480', // 20MB max
        ]);

        $mediaPath = 'social/media/'.auth()->user()->account_id;
        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs($mediaPath, $filename, 'public');

            $uploadedFiles[] = [
                'path' => $path,
                'name' => $filename,
                'url' => Storage::url($path),
                'size' => $file->getSize(),
                'type' => $this->getFileType($path),
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'files' => $uploadedFiles,
            ]);
        }

        return redirect()
            ->route('admin.social.media.index')
            ->with('success', count($uploadedFiles).' archivo(s) subido(s) exitosamente');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $mediaPath = 'social/media/'.auth()->user()->account_id;

        // Security check: ensure file belongs to user's account
        if (! str_starts_with($validated['path'], $mediaPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado',
            ], 403);
        }

        if (Storage::exists($validated['path'])) {
            Storage::delete($validated['path']);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo eliminado exitosamente',
                ]);
            }

            return redirect()
                ->route('admin.social.media.index')
                ->with('success', 'Archivo eliminado exitosamente');
        }

        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado',
        ], 404);
    }

    protected function getFileType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'mp4', 'mov', 'avi', 'wmv', 'flv' => 'video',
            default => 'file',
        };
    }
}
