<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attention\Exports\EmailHistoryExport;
use Modules\Attention\Http\Requests\SendCustomEmailRequest;
use Modules\Attention\Mail\AttentionAssignedMail;
use Modules\Attention\Mail\AttentionConfirmationMail;
use Modules\Attention\Mail\AttentionCustomMail;
use Modules\Attention\Mail\AttentionInProcessMail;
use Modules\Attention\Mail\AttentionResolutionMail;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionMail;
use Modules\Attention\Services\AttentionNotificationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller for PQRSF email management
 * Handles email history, previews, resends, and custom emails
 */
class AttentionEmailController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly AttentionNotificationService $notificationService,
    ) {}

    /**
     * Lista historial de emails de un PQRSF
     * GET /attentions/{uid}/emails
     */
    public function index(Request $request, string $uid): JsonResponse|View|RedirectResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            // Get emails with optional filters
            $query = $attention->mails()->orderBy('created_at', 'desc');

            // Filter by type
            if ($request->filled('type')) {
                $query->where('email_type', $request->type);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $mails = $query->paginate(paginationNumber());

            // Calculate stats
            $stats = [
                'total' => $attention->mails()->count(),
                'sent' => $attention->mails()->where('status', 'sent')->count(),
                'failed' => $attention->mails()->where('status', 'failed')->count(),
                'pending' => $attention->mails()->where('status', 'queued')->count(),
            ];

            // Return JSON for API or View for Web
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attention' => $attention,
                        'mails' => $mails,
                        'stats' => $stats,
                    ],
                ]);
            }

            return view('attention::attentions.emails.index', compact('attention', 'mails', 'stats'));

        } catch (Throwable $e) {
            Log::error('Failed to load email history', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cargar el historial de emails',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'No se pudo cargar el historial de emails');
        }
    }

    /**
     * Ver preview de email específico
     * GET /attentions/{uid}/emails/{mailUid}/preview
     */
    public function preview(Request $request, string $uid, string $mailUid): JsonResponse|View|RedirectResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();
            $mail = AttentionMail::with(['sender', 'template'])
                ->where('uid', $mailUid)
                ->where('attention_id', $attention->id)
                ->firstOrFail();

            // Return JSON for API or View for Web
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'mail' => $mail,
                        'attention' => $attention,
                    ],
                ]);
            }

            return view('attention::attentions.emails.preview', compact('mail', 'attention'));

        } catch (Throwable $e) {
            Log::error('Failed to load email preview', [
                'uid' => $uid,
                'mailUid' => $mailUid,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cargar el preview del email',
                ], Response::HTTP_NOT_FOUND);
            }

            return back()->with('error', 'Email no encontrado');
        }
    }

    /**
     * Reenviar email
     * POST /attentions/{uid}/emails/{mailUid}/resend
     */
    public function resend(Request $request, string $uid, string $mailUid): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::where('uid', $uid)->firstOrFail();
            $originalMail = AttentionMail::where('uid', $mailUid)
                ->where('attention_id', $attention->id)
                ->firstOrFail();

            // Check if recipient exists
            if (! $originalMail->recipient_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reenviar: destinatario no disponible',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create new mail record
            $newMail = AttentionMail::create([
                'attention_id' => $attention->id,
                'email_type' => $originalMail->email_type.'_resend',
                'recipient_email' => $originalMail->recipient_email,
                'subject' => '[REENVÍO] '.$originalMail->subject,
                'body_html' => $originalMail->body_html,
                'body_text' => $originalMail->body_text,
                'template_id' => $originalMail->template_id,
                'status' => 'queued',
                'sent_by' => auth()->id(),
            ]);

            // Send email based on original type
            $mailClass = $this->getMailClass($originalMail->email_type);
            if ($mailClass) {
                Mail::to($originalMail->recipient_email)->send(new $mailClass($attention));
                $newMail->markAsSent();
            } else {
                // Custom email resend
                if ($originalMail->body_html) {
                    Mail::to($originalMail->recipient_email)->send(
                        new AttentionCustomMail($attention, $originalMail->subject, $originalMail->body_html)
                    );
                    $newMail->markAsSent();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Email reenviado correctamente',
                'data' => ['mail' => $newMail],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to resend email', [
                'uid' => $uid,
                'mailUid' => $mailUid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo reenviar el email. Por favor, inténtalo de nuevo.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Enviar email personalizado
     * POST /attentions/{uid}/emails/send-custom
     */
    public function sendCustom(SendCustomEmailRequest $request, string $uid): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::where('uid', $uid)->firstOrFail();

            // Create mail record
            $mail = AttentionMail::create([
                'attention_id' => $attention->id,
                'email_type' => 'custom',
                'recipient_email' => $request->recipient,
                'subject' => $request->subject,
                'body_html' => $request->message,
                'body_text' => strip_tags($request->message),
                'template_id' => $request->template_id,
                'status' => 'queued',
                'sent_by' => auth()->id(),
            ]);

            // Send email
            Mail::to($request->recipient)->send(
                new AttentionCustomMail($attention, $request->subject, $request->message)
            );

            // Send copy to sender if requested
            if ($request->send_copy && $request->user()) {
                Mail::to($request->user()->email)->send(
                    new AttentionCustomMail($attention, '[COPIA] '.$request->subject, $request->message)
                );
            }

            $mail->markAsSent();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Email enviado correctamente',
                'data' => ['mail' => $mail],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to send custom email', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el email. Por favor, inténtalo de nuevo.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Enviar confirmación de recepción al ciudadano
     * POST /attentions/{uid}/emails/send-confirmation
     */
    public function sendConfirmation(string $uid): JsonResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar confirmación a un usuario anónimo o sin email',
                ], 422);
            }

            $sent = $this->notificationService->sendConfirmation($attention);

            if (! $sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo enviar el correo de confirmación',
                ], 500);
            }

            Log::info('Confirmation email sent manually', [
                'radicado' => $attention->radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de confirmación enviado exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending confirmation', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de confirmación',
            ], 500);
        }
    }

    /**
     * Enviar notificación de trámite (en proceso) al ciudadano
     * POST /attentions/{uid}/emails/send-in-process
     */
    public function sendInProcess(string $uid): JsonResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar notificación a un usuario anónimo o sin email',
                ], 422);
            }

            $sent = $this->notificationService->sendInProcess($attention);

            if (! $sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo enviar la notificación de trámite',
                ], 500);
            }

            Log::info('In-process email sent manually', [
                'radicado' => $attention->radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación de trámite enviada exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending in-process notification', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación de trámite',
            ], 500);
        }
    }

    /**
     * Enviar notificación de resolución al ciudadano
     * POST /attentions/{uid}/emails/send-resolution
     */
    public function sendResolution(string $uid): JsonResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar notificación a un usuario anónimo o sin email',
                ], 422);
            }

            if (! $attention->isResolved() && ! $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud debe estar resuelta antes de enviar notificación',
                ], 422);
            }

            $sent = $this->notificationService->sendResolution($attention);

            if (! $sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo enviar la notificación de resolución',
                ], 500);
            }

            Log::info('Resolution email sent manually', [
                'radicado' => $attention->radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación de resolución enviada exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending resolution notification', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación de resolución',
            ], 500);
        }
    }

    /**
     * Historial global de emails (settings)
     * GET /emails/history
     */
    public function history(Request $request): JsonResponse|View|RedirectResponse
    {
        try {
            $query = AttentionMail::with(['attention'])
                ->orderBy('created_at', 'desc');

            // Filter by PQRSF
            if ($request->filled('radicado')) {
                $query->whereHas('attention', function ($q) use ($request) {
                    $q->where('radicado', 'like', '%'.$request->radicado.'%');
                });
            }

            // Filter by type
            if ($request->filled('type')) {
                $query->where('email_type', $request->type);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $mails = $query->paginate(50);

            // Return JSON for API or View for Web
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => ['mails' => $mails],
                ]);
            }

            return view('attention::attentions.emails.history', compact('mails'));

        } catch (Throwable $e) {
            Log::error('Failed to load global email history', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cargar el historial de emails',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'No se pudo cargar el historial de emails');
        }
    }

    /**
     * Stats de emails
     * GET /emails/stats
     */
    public function stats(Request $request): JsonResponse|View
    {
        try {
            $stats = [
                'total' => AttentionMail::count(),
                'sent' => AttentionMail::where('status', 'sent')->count(),
                'failed' => AttentionMail::where('status', 'failed')->count(),
                'pending' => AttentionMail::where('status', 'queued')->count(),
                'today' => AttentionMail::whereDate('created_at', today())->count(),
                'this_week' => AttentionMail::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
                'this_month' => AttentionMail::whereMonth('created_at', now()->month)->count(),
                'by_type' => AttentionMail::query()
                    ->select('email_type', DB::raw('count(*) as count'))
                    ->groupBy('email_type')
                    ->get()
                    ->pluck('count', 'email_type'),
            ];

            $recent = AttentionMail::query()
                ->with(['attention:id,uid,radicado', 'sender:id,name,email'])
                ->latest()
                ->limit(20)
                ->get();

            $dailyData = AttentionMail::query()
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $stats,
                ]);
            }

            return view('attention::attentions.emails.stats', compact('stats', 'recent', 'dailyData'));

        } catch (Throwable $e) {
            Log::error('Failed to load email stats', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron cargar las estadísticas',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return view('attention::attentions.emails.stats', [
                'stats' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0, 'today' => 0, 'this_week' => 0, 'this_month' => 0, 'by_type' => collect()],
                'recent' => collect(),
                'dailyData' => collect(),
            ]);
        }
    }

    /**
     * Export email history to Excel or CSV
     */
    public function export(Request $request): JsonResponse|BinaryFileResponse
    {
        try {
            $validated = $request->validate([
                'format' => 'required|in:excel,csv',
                'attention_uid' => 'nullable|string|exists:attentions,uid',
                'email_type' => 'nullable|string|in:confirmation,assigned,in_process,resolution,custom',
                'status' => 'nullable|string|in:sent,failed,queued',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $query = AttentionMail::query()
                ->with(['attention.type', 'attention.category', 'sender', 'template']);

            // Aplicar filtros
            if ($request->filled('attention_uid')) {
                $attention = Attention::where('uid', $request->attention_uid)->firstOrFail();
                $query->where('attention_id', $attention->id);
            }

            if ($request->filled('email_type')) {
                $query->where('email_type', $request->email_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('created_at', [
                    $request->date_from.' 00:00:00',
                    $request->date_to.' 23:59:59',
                ]);
            }

            $mails = $query->orderBy('created_at', 'desc')->get();

            if ($mails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay emails para exportar con los filtros aplicados.',
                ], 404);
            }

            // Preparar datos para exportación
            $data = $mails->map(function ($mail) {
                return [
                    'Radicado' => $mail->attention->radicado ?? 'N/A',
                    'Tipo PQRSF' => $mail->attention->type->name ?? 'N/A',
                    'Destinatario' => $mail->recipient_email,
                    'Asunto' => $mail->subject,
                    'Tipo Email' => $mail->email_type_label,
                    'Estado' => $mail->status_label,
                    'Plantilla' => $mail->template->name ?? 'N/A',
                    'Enviado por' => $mail->sender?->name ?? 'Sistema',
                    'Fecha Envío' => $mail->sent_at?->format('Y-m-d H:i:s') ?? 'Pendiente',
                    'Fecha Creación' => $mail->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $filename = 'historial_emails_'.now()->format('Y-m-d_His');

            if ($validated['format'] === 'excel') {
                return Excel::download(
                    new EmailHistoryExport($data),
                    $filename.'.xlsx'
                );
            } else {
                return Excel::download(
                    new EmailHistoryExport($data),
                    $filename.'.csv',
                    \Maatwebsite\Excel\Excel::CSV
                );
            }

        } catch (\Exception $e) {
            \Log::error('Error exporting email history: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar historial de emails. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get mail class based on type
     */
    private function getMailClass(string $type): ?string
    {
        return match ($type) {
            'confirmation' => AttentionConfirmationMail::class,
            'assigned' => AttentionAssignedMail::class,
            'in_process' => AttentionInProcessMail::class,
            'resolution' => AttentionResolutionMail::class,
            default => null,
        };
    }
}
