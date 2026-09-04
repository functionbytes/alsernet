<?php

namespace Modules\Forms\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\AlsernetForm;
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

    /**
     * Topes de los adjuntos que llegan en base64 dentro del payload. Los mismos
     * que aplica alsernetforms al recogerlos, repetidos aquí porque un receptor
     * no puede fiarse de que el emisor haya validado nada.
     */
    private const MAX_ATTACHMENT_SIZE = 5242880;

    private const MAX_ATTACHMENTS = 10;

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
        // Etiqueta legible por campo (ej. 'firstname' => 'Nombre'), tal cual
        // el <label> real del .tpl del lado PrestaShop -- ver
        // AlsernetFormFieldLabels. Ausente en payloads antiguos (antes de
        // este campo) o de formularios sin mapeo todavía: buildDescription()
        // cae al humanize genérico para esos.
        $fieldLabels = (array) $request->input('field_labels', []);

        try {
            $ticket = $this->createTicketFromSubmission(
                $formKey,
                $categorySlug,
                $data,
                $fieldLabels,
                (array) $request->input('attachments', [])
            );
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

    /**
     * @param  array<int, array<string, mixed>>  $attachments  Ficheros en base64 (ver FormAction::encodeAttachments).
     */
    private function createTicketFromSubmission(string $formKey, string $categorySlug, array $data, array $fieldLabels = [], array $attachments = []): Ticket
    {
        // AlsernetForm (tabla helpdesk_forms, gestionable desde el panel de tickets) es
        // la fuente de verdad de qué categoría corresponde a cada form_key --
        // reemplaza el mapeo hardcodeado que antes vivía en PHP. El 'category'
        // que manda el payload es solo un cross-check informativo: si diverge
        // del category_id real del AlsernetForm, se loguea un warning pero no bloquea
        // (AlsernetForm es quien manda; alsernetforms puede desincronizarse temporalmente
        // si alguien reconfigura el formulario aquí sin tocar el otro lado).
        $form = AlsernetForm::where('form_key', $formKey)
            ->where('active', true)
            ->with('category')
            ->first();

        if (! $form) {
            throw new RuntimeException("Unknown or inactive form_key: {$formKey}");
        }

        $category = $form->category;

        if (! $category || ! $category->active) {
            throw new RuntimeException("AlsernetForm '{$formKey}' has no active ticket category configured");
        }

        if ($categorySlug !== '' && $categorySlug !== $category->slug) {
            Log::warning('Forms: category slug mismatch between payload and AlsernetForm config', [
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

        $ticket = $this->ticketService->createTicket([
            'subject' => $category->name,
            'description' => $this->buildDescription($data, $fieldLabels),
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'source' => 'formulario',
            // '_field_labels' es una clave reservada, no un campo del
            // formulario: managers/tickets/show.blade.php la usa para
            // traducir el display de las demás y la excluye de la lista
            // al iterar. Ver AlsernetFormFieldLabels (lado PrestaShop).
            'custom_fields' => array_merge($data, ['form_key' => $formKey, '_field_labels' => $fieldLabels]),
        ]);

        $this->attachSubmissionFiles($ticket, $attachments, $formKey);

        return $ticket;
    }

    /**
     * Guarda como adjuntos del ticket los ficheros que el cliente subió en el
     * formulario (CV en 'Trabaja con nosotros', fotos en 'Segunda mano'...).
     *
     * Hasta ahora se perdían: alsernetforms solo se los pasaba al correo de
     * negocio, y todos los formularios entregan por este endpoint.
     *
     * Un fallo aquí NO tumba la petición: el ticket ya está creado y devolver
     * un 500 haría que el reintento del outbox abriera un ticket duplicado. Se
     * registra y se sigue; la descripción del ticket ya nombra los ficheros.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function attachSubmissionFiles(Ticket $ticket, array $attachments, string $formKey): void
    {
        if ($attachments === []) {
            return;
        }

        $allowedMimes = (array) config('helpdesk.attachments.allowed_mime_types', []);
        $files = [];
        $temporales = [];

        try {
            foreach (array_slice($attachments, 0, self::MAX_ATTACHMENTS) as $attachment) {
                $encoded = (string) ($attachment['content_b64'] ?? '');
                $name = trim((string) ($attachment['name'] ?? ''));

                if ($encoded === '' || $name === '') {
                    continue;
                }

                $content = base64_decode($encoded, true);

                if ($content === false || $content === '' || strlen($content) > self::MAX_ATTACHMENT_SIZE) {
                    Log::warning('Forms: adjunto descartado por tamaño o base64 inválido', [
                        'form_key' => $formKey,
                        'ticket' => $ticket->ticket_number,
                        'name' => $name,
                    ]);

                    continue;
                }

                $tmp = tempnam(sys_get_temp_dir(), 'forms_att_');
                file_put_contents($tmp, $content);
                $temporales[] = $tmp;

                // El tipo real del contenido, no el que venga declarado.
                $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);

                if ($allowedMimes !== [] && ! in_array($mime, $allowedMimes, true)) {
                    Log::warning('Forms: adjunto descartado por tipo no permitido', [
                        'form_key' => $formKey,
                        'ticket' => $ticket->ticket_number,
                        'name' => $name,
                        'mime' => $mime,
                    ]);

                    continue;
                }

                // $test = true: el fichero no viene de un upload de PHP, así que
                // la comprobación is_uploaded_file() rechazaría el temporal.
                $files[] = new UploadedFile($tmp, basename($name), $mime, null, true);
            }

            if ($files !== []) {
                $this->ticketService->storeAttachments($files, $ticket->id, ['message' => '']);
            }
        } catch (Throwable $e) {
            Log::error('Forms: no se pudieron guardar los adjuntos del formulario', [
                'form_key' => $formKey,
                'ticket' => $ticket->ticket_number,
                'error' => $e->getMessage(),
            ]);
        } finally {
            foreach ($temporales as $tmp) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Descripción legible en texto plano a partir del payload crudo del
     * formulario -- los campos individuales quedan también en custom_fields
     * (tipados por categoría si el módulo Alvarez llega a declarar
     * TicketCategoryField para alguna, ver plan de Fase 3).
     *
     * @param  array<string, string>  $fieldLabels  Etiqueta real del <label> del
     *                                              .tpl por campo (ver
     *                                              AlsernetFormFieldLabels, lado
     *                                              PrestaShop); un campo ausente
     *                                              aquí cae al humanize genérico.
     */
    private function buildDescription(array $data, array $fieldLabels = []): string
    {
        $lines = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
            $lines[] = $label.': '.$value;
        }

        return $lines !== [] ? implode("\n", $lines) : 'Sin detalle adicional.';
    }
}
