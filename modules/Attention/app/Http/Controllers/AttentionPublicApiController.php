<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Http\Requests\SubmitAttentionRequest;
use Modules\Attention\Http\Requests\SubmitSatisfactionRequest;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionNotificationService;
use Throwable;

/**
 * Public endpoints — no authentication required.
 * Handles citizen-facing submit, tracking, and satisfaction survey.
 */
class AttentionPublicApiController extends Controller
{
    public function __construct(
        protected ?AttentionNotificationService $notificationService = null
    ) {}

    /**
     * Submit a new peticion (public endpoint)
     * POST /api/peticiones/submit
     */
    public function submit(SubmitAttentionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::create([
                'radicado' => Attention::generateRadicado(),
                'type_id' => $request->type_id,
                'category_id' => $request->category_id,
                'sede_id' => $request->sede_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'is_anonymous' => $request->boolean('is_anonymous', false),
                'customer_firstname' => $request->customer_firstname,
                'customer_lastname' => $request->customer_lastname,
                'customer_email' => $request->customer_email,
                'customer_cellphone' => $request->customer_cellphone,
                'customer_dni' => $request->customer_dni,
                'customer_address' => $request->customer_address,
                'response_type' => $request->response_type ? ResponseType::from($request->response_type) : ResponseType::EMAIL,
                'status' => AttentionStatus::RECEIVED,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attention->addMedia($file)->toMediaCollection('attachments');
                }
            }

            $attention->logAction('created', 'peticiones creado por el ciudadano', null);

            if (! $attention->is_anonymous && $attention->customer_email && $this->notificationService) {
                try {
                    $this->notificationService->sendConfirmation($attention);
                } catch (Throwable $e) {
                    Log::error('Failed to send confirmation email', [
                        'radicado' => $attention->radicado,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Su solicitud ha sido radicada exitosamente',
                'data' => [
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'created_at' => $attention->created_at->toIso8601String(),
                    'tracking_url' => route('api.peticiones.track', ['radicado' => $attention->radicado]),
                ],
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error creating attention', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar su solicitud. Por favor intente nuevamente.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Track a peticion by radicado number (public endpoint)
     * GET /api/peticiones/track/{radicado}
     */
    public function track(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::with(['type', 'category', 'sede'])
                ->byRadicado($radicado)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'radicado' => $attention->radicado,
                    'type' => $attention->type->name ?? null,
                    'category' => $attention->category->name ?? null,
                    'subject' => $attention->subject,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'status_color' => $attention->status->color(),
                    'created_at' => $attention->created_at->toIso8601String(),
                    'updated_at' => $attention->updated_at->toIso8601String(),
                    'resolved_at' => $attention->resolved_at?->toIso8601String(),
                    'closed_at' => $attention->closed_at?->toIso8601String(),
                    'has_resolution' => ! empty($attention->resolution),
                    'resolution' => $attention->isResolved() || $attention->isClosed() ? $attention->resolution : null,
                ],
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna solicitud con el radicado proporcionado',
            ], 404);
        }
    }

    /**
     * Submit satisfaction survey (public endpoint)
     * POST /api/peticiones/{radicado}/satisfaction
     */
    public function submitSatisfaction(SubmitSatisfactionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if (! $attention->isResolved() && ! $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puede calificar solicitudes resueltas o cerradas',
                ], 422);
            }

            $attention->update(['satisfaction_rating' => $request->rating]);

            $survey = $attention->satisfactionSurveys()->create([
                'rating' => $request->rating,
                'comment' => $request->comment,
                'submitted_at' => now(),
            ]);

            $attention->logAction(
                'satisfaction_submitted',
                "Calificación de satisfacción: {$request->rating}/5",
                null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gracias por su calificación',
                'data' => $survey,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error submitting satisfaction', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la calificación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
