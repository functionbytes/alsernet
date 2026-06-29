<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewExportHistory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewExportHistoryController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $stats = [
            'total' => ReviewExportHistory::query()->forUser($user)->count(),
            'completed' => ReviewExportHistory::query()->forUser($user)->completed()->count(),
            'processing' => ReviewExportHistory::query()->forUser($user)->whereIn('status', ['processing', 'pending'])->count(),
            'failed' => ReviewExportHistory::query()->forUser($user)->where('status', 'failed')->count(),
        ];

        $exports = ReviewExportHistory::query()
            ->forUser($user)
            ->latest()
            ->paginate(20);

        return view('reviews::exports.history', compact('exports', 'stats'));
    }

    public function download(ReviewExportHistory $history): BinaryFileResponse|RedirectResponse
    {
        if ($history->user_id !== auth()->id()) {
            abort(403, 'No tiene permiso para descargar este archivo.');
        }

        if (! $history->isDownloadable()) {
            return redirect()->route('reviews.exports.history')
                ->with('error', 'El archivo no está disponible para descargar.');
        }

        if (! $history->file_path || ! Storage::disk('local')->exists($history->file_path)) {
            return redirect()->route('reviews.exports.history')
                ->with('error', 'El archivo no fue encontrado en el servidor.');
        }

        return response()->download(Storage::disk('local')->path($history->file_path), $history->file_name);
    }

    public function destroy(ReviewExportHistory $exportHistory): JsonResponse
    {
        if ($exportHistory->user_id !== auth()->id()) {
            abort(403, 'No tiene permiso para eliminar este registro.');
        }

        if ($exportHistory->file_path && Storage::disk('local')->exists($exportHistory->file_path)) {
            Storage::disk('local')->delete($exportHistory->file_path);
        }

        $exportHistory->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $exports = ReviewExportHistory::query()
            ->whereIn('id', $validated['ids'])
            ->where('user_id', auth()->id())
            ->get();

        foreach ($exports as $export) {
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }
            $export->delete();
        }

        return response()->json(['success' => true, 'count' => $exports->count()]);
    }
}
