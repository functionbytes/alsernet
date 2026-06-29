<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Services\ReviewCsvImportService;

class ReviewImportController extends Controller
{
    public function __construct(
        private readonly ReviewCsvImportService $csvImportService
    ) {}

    public function create(ReviewGoogleLocation $location): View
    {
        $this->authorize('reviews.settings.update');

        return view('reviews::settings.locations.import', compact('location'));
    }

    public function storeCsv(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('reviews.settings.update');

        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ], [
            'file.required' => 'Debe seleccionar un archivo CSV.',
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no puede superar los 5MB.',
            'file.mimes' => 'El archivo debe ser de tipo CSV o TXT.',
        ]);

        $path = $request->file('file')->getRealPath();
        $imported = $this->csvImportService->import($location, $path);

        return response()->json([
            'success' => true,
            'message' => "Se importaron {$imported} reseñas correctamente.",
            'imported' => $imported,
        ]);
    }

    public function storeManual(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('reviews.settings.update');

        $validated = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:255'],
            'star_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
            'review_date' => ['nullable', 'date'],
        ], [
            'reviewer_name.required' => 'El nombre del revisor es obligatorio.',
            'reviewer_name.max' => 'El nombre no puede superar los 255 caracteres.',
            'star_rating.required' => 'La calificación es obligatoria.',
            'star_rating.integer' => 'La calificación debe ser un número entero.',
            'star_rating.min' => 'La calificación mínima es 1.',
            'star_rating.max' => 'La calificación máxima es 5.',
            'review_date.date' => 'La fecha de la reseña no tiene un formato válido.',
        ]);

        $starRatingMap = [1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', 5 => 'FIVE'];

        $googleReviewId = 'manual_'.$location->id.'_'.time().'_'.Str::random(6);

        $review = Review::query()->create([
            'location_id' => $location->id,
            'google_review_id' => $googleReviewId,
            'reviewer_name' => $validated['reviewer_name'],
            'reviewer_photo_url' => null,
            'star_rating' => $starRatingMap[$validated['star_rating']],
            'comment' => $validated['comment'] ?? null,
            'review_time' => isset($validated['review_date']) ? $validated['review_date'] : now(),
            'source' => ReviewSource::Manual->value,
            'synced_at' => now(),
        ]);

        ReviewModeration::query()->create([
            'review_id' => $review->id,
            'is_visible' => config('reviews.general.default_moderation_visible', true),
            'is_featured' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reseña creada correctamente.',
            'review_id' => $review->id,
        ]);
    }
}
