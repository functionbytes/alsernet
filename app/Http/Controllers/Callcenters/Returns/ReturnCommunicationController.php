<?php

namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Models\Return\ReturnCommunication;
use App\Models\Return\ReturnRequest;
use App\Services\Return\ReturnNotificationService;
use App\Http\Requests\SendCustomEmailRequest;
use App\Http\Requests\ResendCommunicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReturnCommunicationController extends Controller
{
    private ReturnNotificationService $notificationService;

    public function __construct(ReturnNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Listar todas las comunicaciones de una devolución
     */
    public function index(ReturnRequest $return)
    {
        $this->authorize('view', $return);

        $communications = $return->communications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [
            'total' => $return->communications()->count(),
            'sent' => $return->communications()->sent()->count(),
            'failed' => $return->communications()->failed()->count(),
            'pending' => $return->communications()->pending()->count(),
            'read' => $return->communications()->where('status', 'read')->count(),
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'communications' => $communications,
                'stats' => $stats
            ]);
        }

    return view('returns.communications.index', compact('return', 'communications', 'stats'));
    }

/**
 * Mostrar formulario para enviar email personalizado
 */
public function create(ReturnRequest $return)
    {
        $this->authorize('manageCommunications', $return);

        $templates = [
            'custom' => 'Email personalizado',
            'update' => 'Actualización de estado',
            'request_info' => 'Solicitar información adicional',
            'shipping_reminder' => 'Recordatorio de envío'
        ];

        return view('returns.communications.create', compact('return', 'templates'));
    }

    /**
     * Enviar email personalizado
     */
    public function store(SendCustomEmailRequest $request, ReturnRequest $return)
    {
        $this->authorize('manageCommunications', $return);

        try {
            $communication = DB::transaction(function () use ($request, $return) {
                // Preparar datos del email
                $emailData = $request->validated();

                // Manejar archivos adjuntos si existen
                if ($request->hasFile('attachments')) {
                    $emailData['attachments'] = [];
                    foreach ($request->file('attachments') as $file) {
                        $path = $file->store('temp/attachments');
                        $emailData['attachments'][] = storage_path('app/' . $path);
                    }
                }

                // Enviar email
                return $this->notificationService->sendCustomEmail($return, $emailData);
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Email enviado exitosamente',
                    'communication' => $communication
                ], 201);
            }

            return redirect()
                ->route('returns.communications.index', $return)
                ->with('success', 'Email enviado exitosamente');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Error al enviar el email: ' . $e->getMessage()
                ], 422);
            }

            return back()
                ->withErrors(['error' => 'Error al enviar el email: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Ver detalles de una comunicación
     */
    public function show(ReturnRequest $return, ReturnCommunication $communication)
    {
        $this->authorize('view', $return);

        if ($communication->return_id !== $return->id) {
            abort(404);
        }

        if (request()->wantsJson()) {
            return response()->json($communication);
        }

        return view('returns.communications.show', compact('return', 'communication'));
    }

    /**
     * Reenviar una comunicación fallida
     */
    public function resend(ResendCommunicationRequest $request, ReturnRequest $return, ReturnCommunication $communication)
    {
        $this->authorize('manageCommunications', $return);

        if ($communication->return_id !== $return->id) {
            abort(404);
        }

        if ($communication->status === ReturnCommunication::STATUS_SENT) {
            return response()->json([
                'error' => 'Esta comunicación ya fue enviada exitosamente'
            ], 422);
        }

        try {
            // Marcar como pendiente para reenvío
            $communication->update(['status' => ReturnCommunication::STATUS_PENDING]);

            // Reenviar según el tipo original
            if ($communication->template_used && $communication->template_used !== 'custom') {
                // Reenviar notificación de estado
                $this->notificationService->notifyStatusChange($return);
            } else {
                // Reenviar email personalizado
                $this->notificationService->sendCustomEmail($return, [
                    'recipient' => $request->input('recipient', $communication->recipient),
                    'subject' => $communication->subject,
                    'content' => $communication->content
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Comunicación reenviada exitosamente',
                    'communication' => $communication->fresh()
                ]);
            }

            return redirect()
                ->route('returns.communications.index', $return)
                ->with('success', 'Comunicación reenviada exitosamente');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Error al reenviar: ' . $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => 'Error al reenviar: ' . $e->getMessage()]);
        }
    }

    /**
     * Previsualizar plantilla de email
     */
    public function preview(Request $request, ReturnRequest $return)
    {
        $this->authorize('view', $return);

        $template = $request->input('template', 'pending');
        $validTemplates = ['pending', 'approved', 'rejected', 'processing', 'completed'];

        if (!in_array($template, $validTemplates)) {
            abort(404);
        }

        // Preparar datos para la vista
        $emailData = [
            'return' => $return,
            'return_url' => route('returns.show', $return),
            'costs_summary' => [
                'total_deductions' => $return->total_costs ?? 0,
                'final_refund' => $return->final_refund ?? $return->original_amount
            ]
        ];

        return view('emails.returns.' . $template, $emailData);
    }

    /**
     * Marcar comunicación como leída (endpoint para tracking)
     */
    public function track(Request $request)
{
    $trackingId = $request->input('id');

    if ($trackingId) {
        $this->notificationService->markAsRead($trackingId);
    }

    // Retornar pixel transparente 1x1
    return response()->file(public_path('images/pixel.png'), [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'no-cache, no-store, must-revalidate'
    ]);
}

    /**
     * Obtener estadísticas de comunicaciones
     */
    public function stats(ReturnRequest $return): JsonResponse
    {
        $this->authorize('view', $return);

        $stats = [
            'by_type' => $return->communications()
                ->select('type', DB::raw('count(*) as total'))
                ->groupBy('type')
                ->get(),
            'by_status' => $return->communications()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get(),
            'by_template' => $return->communications()
                ->whereNotNull('template_used')
                ->select('template_used', DB::raw('count(*) as total'))
                ->groupBy('template_used')
                ->get(),
            'response_rate' => [
                'sent' => $return->communications()->sent()->count(),
                'read' => $return->communications()->where('status', 'read')->count(),
                'rate' => $return->communications()->sent()->count() > 0
                    ? round(($return->communications()->where('status', 'read')->count() / $return->communications()->sent()->count()) * 100, 2)
                    : 0
            ],
            'timeline' => $return->communications()
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as total'),
                    'status'
                )
                ->groupBy('date', 'status')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
        ];

        return response()->json($stats);
    }
}
