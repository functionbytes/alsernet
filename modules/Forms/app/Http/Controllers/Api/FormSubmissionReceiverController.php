<?php

namespace Modules\Forms\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\Form;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketService;
use RuntimeException;
use Throwable;

/**
 * Recibe el POST firmado (VerifyAlsernetFormsHmac) que manda FormAction desde
 * el módulo alsernetforms de PrestaShop, y crea el ticket correspondiente.
 *
 * A partir de ahí, todo el pipeline de notificaciones ya existente de
 * HelpdeskTickets se dispara solo sobre el evento TicketCreated -- no hace
 * falta ningún Mailable nuevo aquí: SendCustomerConfirmation ya renderiza la
 * MailerTemplate 'helpdesk_tickets.ticket_created' y manda la confirmación al
 * cliente, NotifyAgentsOnNewTicket avisa a los agentes, RunAutomationsOnTicketCreated
 * aplica las reglas de negocio por categoría, etc. Ver HelpdeskTicketsEventServiceProvider.
 *
 * IMPORTANTE: BaseAction::execute() (lado alsernetforms) solo trata como
 * éxito un HTTP 200 exacto -- todas las respuestas de este controlador usan
 * 200, nunca 201, para no romper esa comprobación compartida con
 * DocumentAction/HuntingLicenseAction.
 */
class FormSubmissionReceiverController extends Controller
{
    private const DEDUP_TTL_SECONDS = 600;

    private const PROCESSING_TTL_SECONDS = 60;

    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! function_exists('helpdesk_forms_enabled') || ! helpdesk_forms_enabled()) {
            // Integración apagada: se responde con algo distinto de 200 a
            // propósito, para que BaseAction lo trate como 'failed' y la
            // petición quede en alsernet_requests reintentando con backoff
            // (igual que cualquier caída real), en vez de darse por buena.
            return response()->json(['success' => false, 'message' => 'integration disabled'], 503);
        }

        $idemKey = (string) $request->header('X-Alsernet-Idempotency-Key', '');

        if ($idemKey === '') {
            // alsernetforms siempre manda el id de alsernet_forms_submissions
            // como idempotency key; su ausencia indica una integración mal
            // configurada, no un caso normal a deduplicar por otra vía.
            return response()->json(['success' => false, 'message' => 'missing idempotency key'], 400);
        }

        $cacheKey = "forms:webhook:seen:{$idemKey}";
        $existing = Cache::get($cacheKey);

        if (is_array($existing) && ($existing['status'] ?? null) === 'done') {
            return response()->json([
                'success' => true,
                'deduplicated' => true,
                'data' => ['ticket_number' => $existing['ticket_number'] ?? null],
            ]);
        }

        // Lock de dos fases (mismo patrón que PsEventReceiverController):
        // si otro worker ya está procesando esta misma clave, deduplicar.
        if ($existing === null && ! Cache::add($cacheKey, ['status' => 'processing'], self::PROCESSING_TTL_SECONDS)) {
            return response()->json(['success' => true, 'deduplicated' => true]);
        }

        $formKey = (string) $request->input('type', '');
        $categorySlug = (string) $request->input('category', '');
        $data = (array) $request->input('data', []);

        try {
            $ticket = $this->createTicketFromSubmission($formKey, $categorySlug, $data);
        } catch (Throwable $e) {
            Log::error('Forms: error processing form submission', [
                'form_key' => $formKey,
                'category' => $categorySlug,
                'error' => $e->getMessage(),
            ]);

            // Liberar el lock para que el reintento del cron de alsernetforms
            // no se encuentre con un "processing" fantasma.
            Cache::forget($cacheKey);

            return response()->json(['success' => false, 'message' => 'processing error'], 500);
        }

        Cache::put($cacheKey, [
            'status' => 'done',
            'ticket_number' => $ticket->ticket_number,
        ], self::DEDUP_TTL_SECONDS);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ],
        ]);
    }

    private function createTicketFromSubmission(string $formKey, string $categorySlug, array $data): Ticket
    {
        // Form (tabla helpdesk_forms, gestionable desde panel/forms/manage) es
        // la fuente de verdad de qué categoría corresponde a cada form_key --
        // reemplaza el mapeo hardcodeado que antes vivía en PHP. El 'category'
        // que manda el payload es solo un cross-check informativo: si diverge
        // del category_id real del Form, se loguea un warning pero no bloquea
        // (Form es quien manda; alsernetforms puede desincronizarse temporalmente
        // si alguien reconfigura el formulario aquí sin tocar el otro lado).
        $form = Form::where('form_key', $formKey)
            ->where('active', true)
            ->with('category')
            ->first();

        if (! $form) {
            throw new RuntimeException("Unknown or inactive form_key: {$formKey}");
        }

        $category = $form->category;

        if (! $category || ! $category->active) {
            throw new RuntimeException("Form '{$formKey}' has no active ticket category configured");
        }

        if ($categorySlug !== '' && $categorySlug !== $category->slug) {
            Log::warning('Forms: category slug mismatch between payload and Form config', [
                'form_key' => $formKey,
                'payload_category' => $categorySlug,
                'configured_category' => $category->slug,
            ]);
        }

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Missing or invalid customer email in submission payload');
        }

        $name = trim(($data['firstname'] ?? '').' '.($data['lastname'] ?? ''));
        $customer = Customer::firstOrCreate(
            ['email' => $email],
            ['name' => $name !== '' ? $name : $email]
        );

        return $this->ticketService->createTicket([
            'subject' => $category->name,
            'description' => $this->buildDescription($data),
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'source' => 'formulario',
            'custom_fields' => array_merge($data, ['form_key' => $formKey]),
        ]);
    }

    /**
     * Descripción legible en texto plano a partir del payload crudo del
     * formulario -- los campos individuales quedan también en custom_fields
     * (tipados por categoría si el módulo Alvarez llega a declarar
     * TicketCategoryField para alguna, ver plan de Fase 3).
     */
    private function buildDescription(array $data): string
    {
        $lines = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $lines[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
        }

        return $lines !== [] ? implode("\n", $lines) : 'Sin detalle adicional.';
    }
}
