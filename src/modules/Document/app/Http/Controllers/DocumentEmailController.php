<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;
use Modules\Document\Services\DocumentEmailService;
use Modules\Document\Services\DocumentMailService;
use Modules\Document\Traits\SendsDocumentEmails;
use Modules\Document\Traits\ValidatesDocumentPermissions;

/**
 * Handles all email-related operations for documents
 * Extracted from DocumentsController for better separation of concerns
 *
 * ⚠️ All methods require proper authorization checks via ValidatesDocumentPermissions trait
 * ⚠️ Uses SendsDocumentEmails trait for rate limiting and standardized email sending
 */
class DocumentEmailController extends Controller
{
    use SendsDocumentEmails, ValidatesDocumentPermissions;

    protected DocumentEmailService $emailService;

    public function __construct(DocumentEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send notification email to customer (initial request)
     */
    public function sendNotificationEmail(Request $request, $uid): JsonResponse
    {
        // Check configuration setting
        if (Setting::get('documents.enable_initial_request', 'yes') !== 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud inicial de documentos está deshabilitada en la configuración.',
            ], 403);
        }

        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendInitialRequest($doc, $adminId)
        );
    }

    /**
     * Send reminder email to customer
     */
    public function sendReminderEmail(Request $request, $uid): JsonResponse
    {
        // Check configuration setting
        if (Setting::get('documents.enable_reminder', 'yes') !== 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'Los recordatorios automáticos están deshabilitados en la configuración.',
            ], 403);
        }

        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendReminder($doc, $adminId)
        );
    }

    /**
     * Resend reminder email
     */
    public function resendReminderEmail(Request $request, $uid): JsonResponse
    {
        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendReminder($doc, $adminId)
        );
    }

    /**
     * Send email for missing specific documents
     */
    public function sendMissingDocumentsEmail(Request $request, $uid): JsonResponse
    {
        // Validate request
        $validated = $this->validateEmailRequest($request, [
            'missing_docs' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendMissingDocumentsRequest(
                $doc,
                $validated['missing_docs'],
                $validated['notes'] ?? null,
                $adminId
            )
        );
    }

    /**
     * Send custom email to customer
     */
    public function sendCustomEmail(Request $request, $uid): JsonResponse
    {
        // Validate request
        $validated = $this->validateEmailRequest($request, [
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:10',
        ]);

        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendCustomEmail(
                $doc,
                $validated['subject'],
                $validated['message'],
                $adminId
            )
        );
    }

    /**
     * Send upload confirmation email
     */
    public function sendUploadConfirmationEmail(Request $request, $uid): JsonResponse
    {
        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendUploadConfirmation($doc, $adminId)
        );
    }

    /**
     * Send approval email
     */
    public function sendApprovalEmail(Request $request, $uid): JsonResponse
    {
        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendApprovalEmail($doc, $adminId)
        );
    }

    /**
     * Send rejection email
     */
    public function sendRejectionEmail(Request $request, $uid): JsonResponse
    {
        // Validate request
        $validated = $this->validateEmailRequest($request, [
            'reason' => 'required|string|min:10',
            'rejected_docs' => 'nullable|array',
        ]);

        // Use standardized email sending flow with rate limiting
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendRejectionEmail(
                $doc,
                $validated['reason'],
                $validated['rejected_docs'] ?? [],
                $adminId
            )
        );
    }

    /**
     * Get email history for a document
     */
    public function emailHistory($uid)
    {
        $document = Document::findByUid($uid);

        if (! $document) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        $emailHistory = DocumentMail::where('document_id', $document->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'emails' => $emailHistory,
        ]);
    }

    /**
     * Preview email content
     */
    public function emailPreview($mailUid)
    {
        $mail = DocumentMail::where('uid', $mailUid)->first();

        if (! $mail) {
            return response()->json([
                'success' => false,
                'message' => 'Email no encontrado.',
            ], 404);
        }

        return view('documents::emails.preview', compact('mail'));
    }

    /**
     * Send email based on document status
     * Private helper method used internally
     */
    private function sendEmailBasedOnStatus(Document $document, string $statusKey, Request $request): ?array
    {
        $mailService = new DocumentMailService;

        switch ($statusKey) {
            case 'pending':
            case 'awaiting_documents':
                // Enviar solicitud inicial o recordatorio
                $result = $mailService->sendInitialRequestMail($document);

                return [
                    'type' => 'initial_request',
                    'label' => $statusKey === 'pending' ? 'Solicitud inicial' : 'Recordatorio',
                    'recipient' => $document->customer_email,
                    'sent' => $result,
                ];

            case 'received':
                // Enviar confirmación de subida
                $result = $mailService->sendUploadConfirmationMail($document, '');

                return [
                    'type' => 'upload_confirmation',
                    'label' => 'Confirmación de subida',
                    'recipient' => $document->customer_email,
                    'sent' => $result,
                ];

            case 'incomplete':
                // Enviar email de documentos específicos
                $missingDocs = $request->input('missing_docs', []);
                if (empty($missingDocs)) {
                    $missingDocs = $document->getMissingDocuments();
                }
                $result = $mailService->sendMissingDocumentsMail($document, $missingDocs);

                return [
                    'type' => 'missing_documents',
                    'label' => 'Documentos específicos',
                    'recipient' => $document->customer_email,
                    'sent' => $result,
                    'missing_docs' => $missingDocs,
                ];

            case 'approved':
                // Enviar notificación de aprobación
                $result = $mailService->sendApprovalMail($document);

                return [
                    'type' => 'approval',
                    'label' => 'Notificación de aprobación',
                    'recipient' => $document->customer_email,
                    'sent' => $result,
                ];

            case 'rejected':
                // Enviar notificación de rechazo
                $reason = $request->input('rejection_reason', 'No especificado');
                $rejectedDocs = $request->input('rejected_docs', []);
                $result = $mailService->sendRejectionMail($document, $reason, $rejectedDocs);

                return [
                    'type' => 'rejection',
                    'label' => 'Notificación de rechazo',
                    'recipient' => $document->customer_email,
                    'sent' => $result,
                    'reason' => $reason,
                    'rejected_docs' => $rejectedDocs,
                ];

            default:
                return null;
        }
    }
}
