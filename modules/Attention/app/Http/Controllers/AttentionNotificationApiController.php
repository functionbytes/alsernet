<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionNotificationService;
use Throwable;

/**
 * Legacy notification endpoints — authentication required.
 *
 * @deprecated Confirmations and resolution notifications are now sent automatically.
 */
class AttentionNotificationApiController extends Controller
{
    public function __construct(
        protected ?AttentionNotificationService $notificationService = null
    ) {}

    /**
     * Manually send confirmation notification to citizen
     * POST /api/attentions/{radicado}/send-confirmation
     *
     * @deprecated Confirmations are now sent automatically on creation.
     */
    public function sendConfirmation(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar confirmación a un usuario anónimo o sin email',
                ], 422);
            }

            if (! $this->notificationService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio de notificaciones no disponible',
                ], 503);
            }

            $this->notificationService->sendConfirmation($attention);

            Log::info('Confirmation email sent manually', [
                'radicado' => $radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de confirmación enviado exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending confirmation', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de confirmación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Manually send resolution notification to citizen
     * POST /api/attentions/{radicado}/send-resolution
     *
     * @deprecated Resolution notifications are now sent automatically when resolving.
     */
    public function sendResolution(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if (! $attention->isResolved() && ! $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud debe estar resuelta antes de enviar notificación',
                ], 422);
            }

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar notificación a un usuario anónimo o sin email',
                ], 422);
            }

            if (! $this->notificationService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio de notificaciones no disponible',
                ], 503);
            }

            $this->notificationService->sendResolution($attention);

            Log::info('Resolution email sent manually', [
                'radicado' => $radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de resolución enviado exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending resolution', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de resolución',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
