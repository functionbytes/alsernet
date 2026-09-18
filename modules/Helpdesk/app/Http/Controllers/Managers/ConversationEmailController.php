<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Helpdesk\Http\Requests\SendEmailFromConversationRequest;
use Modules\Helpdesk\Mail\CustomerOutboundEmail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
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
            ->with(['user:id,firstname,lastname,email'])
            ->orderByDesc('created_at')
            ->get();

        $mapped = $items->map(function (ConversationItem $item) use ($customerEmail, $logMap) {
            $meta = $item->metadata ?? [];
            $log = $logMap[$item->external_id ?? ''] ?? null;

            $status = $log ? ($log->status?->value ?? 'queued') : 'queued';
            // EmailStatus::label() ya trae las 6 etiquetas reales (incluye
            // bounced/complained/suppressed) — antes este match() solo
            // conocía sent/failed y todo lo demás (p.ej. un email bloqueado
            // por la lista de supresión) se mostraba como "En cola" para
            // siempre, aunque EmailLog ya tuviera el resultado final.
            $statusLabel = $log?->status?->label() ?? EmailStatus::Queued->label();

            $attachCount = is_array($item->attachment_urls) ? count($item->attachment_urls) : 0;

            // 150 en vez de 90: con la tarjeta "más información" (dos líneas
            // de vista previa) 90 caracteres dejaba media segunda línea vacía.
            $preview = trim(preg_replace('/\s+/', ' ', strip_tags($item->body ?? '')));
            if (mb_strlen($preview) > 150) {
                $preview = mb_substr($preview, 0, 147).'…';
            }

            $sentBy = $item->user
                ? ($item->user->fullName() ?: $item->user->email)
                : 'Sistema (automático)';

            return [
                'uid' => (string) $item->id,
                'subject' => $meta['subject'] ?? '',
                'to' => $customerEmail,
                'status' => $status,
                'status_label' => $statusLabel,
                'preview' => $preview,
                'attachments_count' => $attachCount,
                'sent_by' => $sentBy,
                'created_at' => $item->created_at?->toIso8601String(),
                'date_human' => $item->created_at?->diffForHumans() ?? '',
            ];
        });

        // Failed/bounced/complained/suppressed son estados terminales
        // negativos — la tarjeta ya los pinta a todos en rojo ("danger"),
        // pero el pill "Fallidos" seguía comparando contra el literal
        // 'failed', así que un email bloqueado por la lista de supresión (o
        // rebotado, o marcado como spam) no sumaba ni aparecía al filtrar.
        $dangerStatuses = ['failed', 'bounced', 'complained', 'suppressed'];

        return response()->json([
            'emails' => $mapped,
            'counts' => [
                'all' => $mapped->count(),
                'sent' => $mapped->filter(fn ($e) => $e['status'] === 'sent')->count(),
                'failed' => $mapped->filter(fn ($e) => in_array($e['status'], $dangerStatuses, true))->count(),
                'queued' => $mapped->filter(fn ($e) => $e['status'] === 'queued')->count(),
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
        $statusLabel = $log?->status?->label() ?? EmailStatus::Queued->label();

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

        // Enlace al inspector completo de HelpdeskEmailActivity (fuente/
        // cabeceras/link-check/bitácora, que este modal no replica). Solo si
        // hay un EmailLog correlacionado y el módulo satélite tiene sus
        // rutas cargadas — Route::has en vez de class_exists porque el
        // modelo puede existir sin que el módulo esté activo.
        $activityUrl = ($log && Route::has('helpdeskemailactivity.show'))
            ? route('helpdeskemailactivity.show', ['emailLog' => $log->uid])
            : null;

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
            'created_at_formatted' => $item->created_at?->format('d/m/Y H:i'),
            'error_message' => $log?->error_message,
            'template_name' => $templateName,
            'sent_by' => $sentBy,
            'type_label' => $typeLabel,
            'related_order_id' => $relatedOrderId,
            'related_customer_name' => $relatedCustomerName,
            'related_document_status' => $relatedDocStatus,
            'related_document_status_code' => $relatedDocStatusCode,
            'activity_url' => $activityUrl,
            // Traza/aperturas/clics — mismos datos que el inspector de
            // HelpdeskEmailActivity (Traza/Aperturas), leídos de EmailLog vía
            // el mismo cruce por external_id ya usado arriba para status/
            // error_message. Sin $log (módulo desactivado o correo previo a
            // esta funcionalidad) se devuelve null y el modal oculta esas
            // pestañas en vez de mostrarlas vacías.
            'trace' => $log ? $this->buildEmailTrace($item, $log) : null,
            'opens' => $this->buildEmailOpens($log),
            'clicks' => $this->buildEmailClicks($log),
        ]);
    }

    /**
     * Recorrido del envío (Encolado → Aceptado por SMTP/Fallido → Entrega),
     * misma lógica que emails/partials/detail-panel.blade.php:465-536 del
     * módulo HelpdeskEmailActivity, sin los detalles de transporte (host:
     * puerto SMTP) que ahí son para el admin y aquí son ruido para un agente
     * mirando el email de un cliente.
     *
     * @return array<int, array{key: string, title: string, meta: ?string, time: ?string, icon: string}>
     */
    private function buildEmailTrace(ConversationItem $item, EmailLog $log): array
    {
        $steps = [
            [
                'key' => 'queued',
                'title' => 'Encolado',
                'meta' => $item->created_at?->format('d/m/Y H:i:s'),
                'time' => $item->created_at?->format('H:i:s'),
                'icon' => 'ok',
            ],
        ];

        if ($log->sent_at) {
            $steps[] = [
                'key' => 'sent',
                'title' => 'Aceptado por el servidor de correo',
                'meta' => $log->sent_at->format('d/m/Y H:i:s'),
                'time' => $log->sent_at->format('H:i:s'),
                'icon' => 'ok',
            ];
        } elseif ($log->status === EmailStatus::Failed) {
            $steps[] = [
                'key' => 'failed',
                'title' => 'Aceptado por el servidor de correo',
                'meta' => ($log->failed_at ? $log->failed_at->format('d/m/Y H:i:s').' · ' : '').'Fallido',
                'time' => $log->failed_at?->format('H:i:s'),
                'icon' => 'err',
            ];
        } else {
            $steps[] = [
                'key' => 'sent',
                'title' => 'Aceptado por el servidor de correo',
                'meta' => 'Pendiente',
                'time' => null,
                'icon' => 'warn',
            ];
        }

        if ($log->bounced_at) {
            $steps[] = [
                'key' => 'bounced',
                'title' => 'Confirmación de entrega',
                'meta' => $log->bounced_at->format('d/m/Y H:i:s').' · '.EmailStatus::Bounced->label(),
                'time' => $log->bounced_at->format('H:i:s'),
                'icon' => 'err',
            ];
        } elseif ($log->complained_at) {
            $steps[] = [
                'key' => 'complained',
                'title' => 'Confirmación de entrega',
                'meta' => $log->complained_at->format('d/m/Y H:i:s').' · '.EmailStatus::Complained->label(),
                'time' => $log->complained_at->format('H:i:s'),
                'icon' => 'spam',
            ];
        } elseif ($log->status === EmailStatus::Suppressed) {
            $steps[] = [
                'key' => 'suppressed',
                'title' => 'Confirmación de entrega',
                'meta' => EmailStatus::Suppressed->label().($log->error_message ? ' · '.$log->error_message : ''),
                'time' => null,
                'icon' => 'err',
            ];
        } else {
            $steps[] = [
                'key' => 'delivery',
                'title' => 'Confirmación de entrega',
                'meta' => 'Sin confirmación explícita de rebote o queja — no implica que no llegara.',
                'time' => null,
                'icon' => 'unknown',
            ];
        }

        return $steps;
    }

    /**
     * @return ?array{tracked: bool, count: int, items: array<int, array{at: ?string, source: ?string, ip: ?string, user_agent: ?string}>}
     */
    private function buildEmailOpens(?EmailLog $log): ?array
    {
        if (! $log || ! $log->hasOpenTracking()) {
            return null;
        }

        $opens = $log->opens()->orderByDesc('opened_at')->get();

        return [
            'tracked' => true,
            'count' => $opens->count(),
            'items' => $opens->take(20)->map(fn ($open) => [
                'at' => $open->opened_at?->format('d/m/Y H:i'),
                'source' => $open->source?->label(),
                'ip' => $open->ip,
                'user_agent' => $open->user_agent ? Str::limit($open->user_agent, 60) : null,
            ])->values()->all(),
        ];
    }

    /**
     * @return ?array{tracked: bool, count: int, items: array<int, array{at: ?string, url: ?string, ip: ?string, user_agent: ?string}>}
     */
    private function buildEmailClicks(?EmailLog $log): ?array
    {
        if (! $log || ! $log->hasClickTracking()) {
            return null;
        }

        $clicks = $log->clicks()->with('link')->orderByDesc('clicked_at')->get();

        return [
            'tracked' => true,
            'count' => $clicks->count(),
            'items' => $clicks->take(20)->map(fn ($click) => [
                'at' => $click->clicked_at?->format('d/m/Y H:i'),
                'url' => $click->link?->url,
                'ip' => $click->ip,
                'user_agent' => $click->user_agent ? Str::limit($click->user_agent, 60) : null,
            ])->values()->all(),
        ];
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
