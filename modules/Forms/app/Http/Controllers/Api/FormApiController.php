<?php

namespace Modules\Forms\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class FormApiController extends Controller
{
    public function index(Form $form): JsonResponse
    {
        $submissions = $form->submissions()
            ->with('values')
            ->latest()
            ->paginate(20);

        return response()->json($submissions);
    }

    public function show(Form $form, FormSubmission $submission): JsonResponse
    {
        $submission->load('values');

        return response()->json(['data' => $submission]);
    }

    public function destroy(Form $form, FormSubmission $submission): JsonResponse
    {
        $submission->delete();

        return response()->json(['message' => 'Envío eliminado correctamente.']);
    }

    public function stats(Form $form): JsonResponse
    {
        return response()->json([
            'form_id' => $form->id,
            'form_name' => $form->name,
            'total_submissions' => $form->submissions()->count(),
            'unread' => $form->submissions()->unread()->count(),
            'spam' => $form->submissions()->spam()->count(),
            'by_status' => $form->submissions()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'last_7_days' => $form->submissions()
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'avg_time_to_complete' => $form->submissions()
                ->whereNotNull('time_to_complete')
                ->avg('time_to_complete'),
        ]);
    }
}
