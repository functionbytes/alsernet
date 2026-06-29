<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Reviews\Jobs\ExportGdprDataJob;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewGdprController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        ExportGdprDataJob::dispatch($request->user());

        return response()->json([
            'message' => 'Exportacion en proceso. Recibirá un email cuando esté lista.',
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'message' => 'La contraseña proporcionada no es correcta.',
            ], 422);
        }

        $user = $request->user();

        // Anonymize reviewer names in reviews linked to user's connections
        ReviewGoogleConnection::query()
            ->where('user_id', $user->id)
            ->with('locations.reviews')
            ->each(function (ReviewGoogleConnection $connection) {
                foreach ($connection->locations as $location) {
                    $location->reviews()->update(['reviewer_name' => 'Anonymous']);
                }
            });

        // Delete replies written by the user
        ReviewReply::query()
            ->where('created_by', $user->id)
            ->delete();

        // Delete templates created by the user
        ReviewReplyTemplate::query()
            ->where('created_by', $user->id)
            ->delete();

        // Soft-delete connections (cascade to locations via model events if configured)
        ReviewGoogleConnection::query()
            ->where('user_id', $user->id)
            ->each(fn (ReviewGoogleConnection $c) => $c->delete());

        return response()->json([
            'message' => 'Datos eliminados correctamente.',
        ]);
    }
}
