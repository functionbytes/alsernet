<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\SendCustomEmailRequest;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionAction;
use Modules\Forms\Models\FormSubmissionEmail;

class FormSubmissionEmailController extends Controller
{
    public function index(Request $request, FormSubmission $submission): View
    {
        $this->authorize('view', $submission);

        $submission->load(['form', 'emails.sender']);

        $query = $submission->emails()->latest();

        if ($request->filled('type')) {
            $query->where('email_type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $emails = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => $submission->emails()->count(),
            'sent' => $submission->emails()->where('status', 'sent')->count(),
            'failed' => $submission->emails()->where('status', 'failed')->count(),
            'queued' => $submission->emails()->where('status', 'queued')->count(),
        ];

        $submitterEmail = $submission->getEmailValue();

        return view('forms::inbox.emails', compact('submission', 'emails', 'stats', 'submitterEmail'));
    }

    public function preview(FormSubmission $submission, FormSubmissionEmail $email): View
    {
        $this->authorize('view', $submission);

        if ($email->submission_id !== $submission->id) {
            abort(404);
        }

        $email->load('sender');
        $submission->load('form');

        return view('forms::inbox.email-preview', compact('submission', 'email'));
    }

    public function sendCustom(SendCustomEmailRequest $request, FormSubmission $submission): JsonResponse
    {
        $data = $request->validated();

        $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $safeMessage = strip_tags($data['message'], $allowedTags);

        $log = FormSubmissionEmail::log(
            $submission,
            'custom',
            $data['recipient'],
            $data['subject'],
            $safeMessage,
            auth()->id()
        );

        try {
            Mail::send([], [], function ($m) use ($data, $safeMessage) {
                $m->to($data['recipient'])
                    ->subject($data['subject'])
                    ->html($safeMessage);
            });

            $log->markAsSent();

            return response()->json(['success' => true, 'message' => 'Email enviado correctamente.']);
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());

            return response()->json(['success' => false, 'message' => 'No se pudo enviar el email.'], 500);
        }
    }

    public function sendConfirmation(Request $request, FormSubmission $submission): JsonResponse
    {
        $this->authorize('update', $submission);
        $recipientEmail = $submission->getEmailValue();

        if (! $recipientEmail) {
            return response()->json(['success' => false, 'message' => 'No se encontró email del solicitante.'], 422);
        }

        $subject = 'Confirmación de recepción - Solicitud #'.$submission->id;
        $bodyHtml = '<p>Estimado solicitante,</p>'
            .'<p>Le confirmamos que hemos recibido correctamente su solicitud con el número de radicado <strong>#'.$submission->id.'</strong>.</p>'
            .'<p>Nuestro equipo la revisará y le responderemos a la brevedad posible.</p>'
            .'<p>Gracias por contactarnos.</p>';

        $log = FormSubmissionEmail::log(
            $submission,
            'confirmation',
            $recipientEmail,
            $subject,
            $bodyHtml,
            auth()->id()
        );

        try {
            Mail::send([], [], function ($m) use ($recipientEmail, $subject, $bodyHtml) {
                $m->to($recipientEmail)->subject($subject)->html($bodyHtml);
            });

            $log->markAsSent();

            FormSubmissionAction::create([
                'submission_id' => $submission->id,
                'user_id' => auth()->id(),
                'action' => 'email_sent',
                'description' => 'Confirmación de recepción enviada a: '.$recipientEmail,
            ]);

            return response()->json(['success' => true, 'message' => 'Confirmación enviada correctamente.']);
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());

            return response()->json(['success' => false, 'message' => 'No se pudo enviar la confirmación.'], 500);
        }
    }

    public function resend(Request $request, FormSubmission $submission, FormSubmissionEmail $email): JsonResponse
    {
        $this->authorize('update', $submission);
        if ($email->submission_id !== $submission->id) {
            return response()->json(['success' => false, 'message' => 'Email no pertenece a esta submission.'], 403);
        }

        if (! $email->recipient_email) {
            return response()->json(['success' => false, 'message' => 'Destinatario no disponible.'], 422);
        }

        if (! $email->body_html) {
            return response()->json(['success' => false, 'message' => 'No hay contenido HTML para reenviar.'], 422);
        }

        $log = FormSubmissionEmail::log(
            $submission,
            'resend',
            $email->recipient_email,
            '[REENVÍO] '.$email->subject,
            $email->body_html,
            auth()->id()
        );

        try {
            Mail::send([], [], function ($m) use ($email) {
                $m->to($email->recipient_email)
                    ->subject('[REENVÍO] '.$email->subject)
                    ->html($email->body_html);
            });

            $log->markAsSent();

            return response()->json(['success' => true, 'message' => 'Email reenviado correctamente.']);
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());

            return response()->json(['success' => false, 'message' => 'No se pudo reenviar el email.'], 500);
        }
    }
}
