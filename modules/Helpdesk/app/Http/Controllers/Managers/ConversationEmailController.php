<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Helpdesk\Http\Requests\SendEmailFromConversationRequest;
use Modules\Helpdesk\Mail\CustomerOutboundEmail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

/**
 * Outbound email for a conversation: template listing/preview, sending, and
 * the "sent emails" log (extracted from ConversationsController — QUAL-03).
 */
class ConversationEmailController extends Controller
{
    /**
     * Mailer template keys reserved for internal/staff notifications (never selectable to email a customer).
     */
    private const INTERNAL_ONLY_TEMPLATE_KEYS = [
        'helpdesk.new_ticket_agent',
        'helpdesk.sla_escalation',
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.conversations.view')->only(['emailLogIndex', 'emailLogShow']);
        $this->middleware('can:helpdesk.conversations.update')->only(['sendEmail']);
    }

    /**
     * List enabled Mailer templates for the helpdesk module.
     */
    public function emailTemplates(): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $templates = MailerTemplate::query()
            ->module('helpdesk')
            ->enabled()
            ->whereNotIn('key', self::INTERNAL_ONLY_TEMPLATE_KEYS)
            ->with('translations')
            ->orderBy('name')
            ->get()
            ->map(fn (MailerTemplate $t) => [
                'id' => $t->id,
                'key' => $t->key,
                'name' => $t->name,
                'subject' => $t->subject ?? '',
            ]);

        return response()->json(['templates' => $templates]);
    }

    /**
     * Preview a Mailer template rendered with conversation variables.
     */
    public function previewEmailTemplate(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $templateId = (int) $request->input('template_id');
        $template = MailerTemplate::module('helpdesk')->enabled()
            ->whereNotIn('key', self::INTERNAL_ONLY_TEMPLATE_KEYS)
            ->with(['translations', 'layout'])
            ->find($templateId);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Plantilla no encontrada.'], 404);
        }

        $conversation->loadMissing(['customer', 'inbox']);
        $agent = auth()->user();

        $variables = [
            'CUSTOMER_NAME' => e($conversation->customer?->name ?? 'Cliente'),
            'CUSTOMER_EMAIL' => e($conversation->customer?->email ?? ''),
            'CONVERSATION_ID' => $conversation->id,
            'TICKET_NUMBER' => (string) $conversation->id,
            'SUBJECT' => e($conversation->subject ?: ('Consulta #'.$conversation->id)),
            'AGENT_NAME' => e($agent?->fullName() ?: ($agent?->email ?? '')),
            'INBOX_NAME' => $conversation->inbox?->name ?? '',
            'COMPANY_NAME' => config('app.name'),
        ];

        $langId = $this->resolveMailerLangIdForCustomer($conversation->customer);
        $htmlBody = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);
        $plainBody = strip_tags(html_entity_decode(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)));
        $plainBody = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $plainBody)));

        $subject = MailerTemplateRendererService::replaceVariables($template->translate($langId)?->subject ?? $template->subject ?? '', $variables);

        return response()->json([
            'subject' => $subject,
            'body' => $plainBody,
            'html_body' => $htmlBody,
        ]);
    }

    /**
     * Send a real email to the customer and persist it as a ConversationItem.
     */
    public function sendEmail(SendEmailFromConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();

        $conversation->loadMissing(['customer', 'inbox']);
        $customer = $conversation->customer;

        if (! filled($customer?->email)) {
            return response()->json([
                'success' => false,
                'message' => 'El contacto no tiene dirección de email.',
            ], 422);
        }

        $cc = $validated['cc'] ?? [];
        $bcc = $validated['bcc'] ?? [];

        // Si se eligió una plantilla del módulo Mailer, renderizar como HTML.
        if (! empty($validated['template_id'])) {
            $template = MailerTemplate::module('helpdesk')->enabled()
                ->whereNotIn('key', self::INTERNAL_ONLY_TEMPLATE_KEYS)
                ->with(['translations', 'layout'])
                ->find((int) $validated['template_id']);

            if ($template) {
                $agent = auth()->user();
                $variables = [
                    'CUSTOMER_NAME' => e($customer->name ?? 'Cliente'),
                    'CUSTOMER_EMAIL' => e($customer->email ?? ''),
                    'CONVERSATION_ID' => $conversation->id,
                    'TICKET_NUMBER' => (string) $conversation->id,
                    'SUBJECT' => e($conversation->subject ?: ('Consulta #'.$conversation->id)),
                    'AGENT_NAME' => e($agent?->fullName() ?: ($agent?->email ?? '')),
                    'INBOX_NAME' => $conversation->inbox?->name ?? '',
                    'COMPANY_NAME' => config('app.name'),
                ];
                $bodyHtml = MailerTemplateRendererService::renderEmailTemplate(
                    $template,
                    $variables,
                    $this->resolveMailerLangIdForCustomer($customer)
                );
                $bodyPlain = strip_tags(html_entity_decode(preg_replace('/<br\s*\/?>/i', "\n", $bodyHtml)));
                $bodyPlain = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $bodyPlain)));
            } else {
                $bodyHtml = nl2br(e($validated['body']));
                $bodyPlain = $validated['body'];
            }
        } else {
            $bodyHtml = nl2br(e($validated['body']));
            $bodyPlain = $validated['body'];
        }

        $externalId = (string) Str::uuid();

        $item = DB::transaction(function () use ($conversation, $validated, $cc, $bcc, $bodyHtml, $bodyPlain, $externalId): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'email_sent',
                'body' => $bodyPlain,
                'html_body' => $bodyHtml,
                'is_internal' => false,
                'external_id' => $externalId,
                'metadata' => [
                    'subject' => $validated['subject'],
                    'cc' => $cc,
                    'bcc' => $bcc,
                    'template_key' => $validated['template_id'] ?? null,
                ],
            ]);
        });

        Mail::to($customer->email)
            ->cc($cc)
            ->bcc($bcc)
            ->queue(new CustomerOutboundEmail(
                conversation: $conversation,
                emailSubject: $validated['subject'],
                emailBodyHtml: $bodyHtml,
                emailBodyPlain: $bodyPlain,
                ccEmails: $cc,
                bccEmails: $bcc,
                externalId: $externalId,
            ));

        return response()->json([
            'success' => true,
            'message' => 'Email enviado correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    public function emailLogIndex(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing('customer');
        $customerEmail = $conversation->customer?->email ?? '';

        // Build uid→EmailLog map for delivery status enrichment (optional module)
        $logMap = [];
        if (class_exists(EmailLog::class)) {
            EmailLog::query()
                ->select(EmailLog::LIST_COLUMNS)
                ->forEntity(Conversation::class, $conversation->id)
                ->get()
                ->each(function ($log) use (&$logMap) {
                    $logMap[$log->external_id ?? $log->uid] = $log;
                });
        }

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'email_sent')
            ->orderByDesc('created_at')
            ->get();

        $mapped = $items->map(function (ConversationItem $item) use ($customerEmail, $logMap) {
            $meta = $item->metadata ?? [];
            $log = $logMap[$item->external_id ?? ''] ?? null;

            $status = $log ? ($log->status?->value ?? 'queued') : 'queued';
            $statusLabel = match ($status) {
                'sent' => 'Enviado',
                'failed' => 'Fallido',
                default => 'En cola',
            };

            $attachCount = is_array($item->attachment_urls) ? count($item->attachment_urls) : 0;

            $preview = trim(preg_replace('/\s+/', ' ', strip_tags($item->body ?? '')));
            if (mb_strlen($preview) > 90) {
                $preview = mb_substr($preview, 0, 87).'…';
            }

            return [
                'uid' => (string) $item->id,
                'subject' => $meta['subject'] ?? '',
                'to' => $customerEmail,
                'status' => $status,
                'status_label' => $statusLabel,
                'preview' => $preview,
                'attachments_count' => $attachCount,
                'created_at' => $item->created_at?->toIso8601String(),
                'date_human' => $item->created_at?->diffForHumans() ?? '',
            ];
        });

        return response()->json([
            'emails' => $mapped,
            'counts' => [
                'all' => $mapped->count(),
                'sent' => $mapped->filter(fn ($e) => $e['status'] === 'sent')->count(),
                'failed' => $mapped->filter(fn ($e) => $e['status'] === 'failed')->count(),
            ],
        ]);
    }

    public function emailLogShow(Conversation $conversation, string $emailLog): JsonResponse
    {
        $this->authorize('view', $conversation);

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'email_sent')
            ->where('id', (int) $emailLog)
            ->with(['user:id,firstname,lastname,email'])
            ->firstOrFail();

        $meta = $item->metadata ?? [];
        $customerEmail = $conversation->customer?->email ?? '';

        // Enrich with delivery status from EmailLog if available
        $log = null;
        if (class_exists(EmailLog::class) && $item->external_id) {
            $log = EmailLog::query()
                ->forEntity(Conversation::class, $conversation->id)
                ->where('external_id', $item->external_id)
                ->first();
        }

        $status = $log ? ($log->status?->value ?? 'queued') : 'queued';
        $statusLabel = match ($status) {
            'sent' => 'Enviado',
            'failed' => 'Fallido',
            default => 'En cola',
        };

        $sentAt = $log?->sent_at ?? $item->created_at;

        // Resolver nombre de plantilla
        $templateName = null;
        $templateKey = $meta['template_key'] ?? null;
        if ($templateKey && class_exists(MailerTemplate::class)) {
            $template = MailerTemplate::query()->find((int) $templateKey);
            $templateName = $template?->name;
        }

        // Enviado por: usuario autenticado o sistema
        $sentBy = $item->user
            ? $item->user->fullName() ?: $item->user->email
            : 'Sistema (automático)';

        // Tipo / categoría del email (si la plantilla la trae)
        $typeLabel = $meta['email_type_label'] ?? ($meta['email_type'] ?? null);

        // Documento relacionado (si fue agregado al metadata por automation)
        $relatedOrderId = $meta['related_order_id'] ?? null;
        $relatedCustomerName = $meta['related_customer_name'] ?? null;
        $relatedDocStatus = $meta['related_document_status'] ?? null;
        $relatedDocStatusCode = $meta['related_document_status_code'] ?? null;

        return response()->json([
            'uid' => (string) $item->id,
            'id_label' => '#EM-'.str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
            'subject' => $meta['subject'] ?? '',
            'to' => $customerEmail,
            'cc' => $meta['cc'] ?? [],
            'status' => $status,
            'status_label' => $statusLabel,
            'body_html' => $item->html_body ?: ($item->body ?? ''),
            'body_text' => trim(strip_tags($item->body ?? '')),
            'attachments' => $item->attachment_urls ?? [],
            'sent_at' => $sentAt?->toIso8601String(),
            'sent_at_human' => $sentAt?->diffForHumans(),
            'sent_at_formatted' => $sentAt?->format('d/m/Y H:i:s'),
            'created_at' => $item->created_at?->toIso8601String(),
            'error_message' => $log?->error_message,
            'template_name' => $templateName,
            'sent_by' => $sentBy,
            'type_label' => $typeLabel,
            'related_order_id' => $relatedOrderId,
            'related_customer_name' => $relatedCustomerName,
            'related_document_status' => $relatedDocStatus,
            'related_document_status_code' => $relatedDocStatusCode,
        ]);
    }

    /**
     * Resuelve el lang_id de Mailer (langs.id) a partir del idioma guardado
     * en el contacto (helpdesk_customers.language, ej. "es"/"en"/"pt"). Si el
     * idioma del cliente no tiene fila en `langs` (iso_code), devuelve null y
     * MailerTemplateRendererService/translate() caen solos al idioma global
     * por defecto — nunca rompe el envío, solo deja de traducir.
     */
    private function resolveMailerLangIdForCustomer(?Customer $customer): ?int
    {
        $locale = $customer?->language;

        if (blank($locale)) {
            return null;
        }

        return MailerLang::query()->iso($locale)->value('id');
    }
}
