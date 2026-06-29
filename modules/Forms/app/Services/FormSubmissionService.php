<?php

namespace Modules\Forms\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Events\FormSubmitted;
use Modules\Forms\Events\NewFormSubmission;
use Modules\Forms\Jobs\SendConditionalEmailsJob;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionValue;
use Modules\Forms\Notifications\NewFormSubmissionNotification;
use Modules\Notification\Services\NotificationService;
use Modules\Page\Models\Page;

class FormSubmissionService
{
    public function __construct(
        protected FormEmailService $emailService,
        protected FormWebhookService $webhookService,
    ) {}

    /**
     * Procesa un envío de formulario completo.
     * Crea submission + values + GeoIP + dispara emails + webhook.
     */
    public function process(Form $form, Request $request): FormSubmission
    {
        $submission = DB::transaction(function () use ($form, $request) {
            $submission = $this->createSubmission($form, $request);
            $this->saveValues($submission, $form, $request);
            $this->dispatchNotifications($form, $submission);

            return $submission;
        });

        try {
            event(new NewFormSubmission($form, $submission));
        } catch (\Throwable $e) {
            Log::error('Forms: broadcast failed', ['error' => $e->getMessage()]);
        }

        try {
            event(new FormSubmitted($form, $submission));
        } catch (\Throwable $e) {
            Log::error('Forms: FormSubmitted listener failed', ['error' => $e->getMessage()]);
        }

        return $submission;
    }

    protected function createSubmission(Form $form, Request $request): FormSubmission
    {
        $geoData = $this->resolveGeoData($request->ip());
        $utmData = $this->extractUtmData($request);

        return FormSubmission::create([
            'form_id' => $form->id,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer_url' => $request->headers->get('referer'),
            'source_page_id' => $this->resolveSourcePageId($request->headers->get('referer')),
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'utm_source' => $utmData['utm_source'],
            'utm_medium' => $utmData['utm_medium'],
            'utm_campaign' => $utmData['utm_campaign'],
            'utm_term' => $utmData['utm_term'],
            'utm_content' => $utmData['utm_content'],
            'locale' => app()->getLocale(),
            'time_to_complete' => $request->input('_time_to_complete') !== null
                ? (int) $request->input('_time_to_complete')
                : null,
            'status' => 'new',
            'is_read' => false,
            'is_spam' => false,
        ]);
    }

    protected function saveValues(FormSubmission $submission, Form $form, Request $request): void
    {
        $fields = $form->fields()->visible()->ordered()->get();
        $rows = [];
        $now = now();

        foreach ($fields as $field) {
            if ($field->isLayoutField() && ! in_array($field->type, ['consent', 'newsletter_consent'], true)) {
                continue;
            }

            $rows[] = [
                'submission_id' => $submission->id,
                'form_field_id' => $field->id,
                'field_key' => $field->key,
                'field_label' => $field->label,
                'field_type' => $field->type,
                'value' => $this->resolveFieldValue($field, $request),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            FormSubmissionValue::query()->insert($rows);
        }
    }

    protected function resolveFieldValue(FormField $field, Request $request): ?string
    {
        if ($field->type === 'file' && $request->hasFile($field->key)) {
            return $this->handleFileUpload($field, $request);
        }

        $value = $request->input($field->key);

        if ($field->type === 'checkbox' && is_array($value)) {
            return json_encode($value);
        }

        if ($field->type === 'newsletter_consent' && $value) {
            $this->handleNewsletterConsent($field, $request);
        }

        return $value !== null ? (string) $value : null;
    }

    protected function handleFileUpload(FormField $field, Request $request): ?string
    {
        try {
            $file = $request->file($field->key);

            if (! $file || ! $file->isValid()) {
                return null;
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: '');
            $mime = strtolower($file->getMimeType() ?: '');

            $allowedExtensions = array_map('strtolower', (array) config('forms.allowed_file_extensions', []));
            $allowedMimes = (array) config('forms.allowed_mime_types', [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'text/plain', 'text/csv',
            ]);

            if ($allowedExtensions && ! in_array($extension, $allowedExtensions, true)) {
                Log::warning('Forms: upload rechazado por extensión', [
                    'form_id' => $field->form_id,
                    'field_key' => $field->key,
                    'extension' => $extension,
                ]);

                return null;
            }

            if (! in_array($mime, $allowedMimes, true)) {
                Log::warning('Forms: upload rechazado por mime-type', [
                    'form_id' => $field->form_id,
                    'field_key' => $field->key,
                    'mime' => $mime,
                ]);

                return null;
            }

            $maxBytes = (int) (config('forms.max_file_size_mb', 10) * 1024 * 1024);
            if ($file->getSize() > $maxBytes) {
                return null;
            }

            return $file->store("forms/{$field->form_id}", 'public');
        } catch (\Throwable $e) {
            Log::warning('Forms: error subiendo archivo', [
                'form_id' => $field->form_id,
                'field_key' => $field->key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function handleNewsletterConsent(FormField $field, Request $request): void
    {
        if (! $field->mailrelay_group_id || ! class_exists('\Modules\Mailrelay\Services\MailrelayService')) {
            return;
        }

        try {
            $email = $request->input('email') ?? $request->input('correo');

            if ($email) {
                Log::info("Forms: Newsletter consent - email: {$email}, group: {$field->mailrelay_group_id}");
            }
        } catch (\Throwable $e) {
            Log::warning('Forms: newsletter consent error', [
                'form_id' => $field->form_id,
                'field_key' => $field->key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function dispatchNotifications(Form $form, FormSubmission $submission): void
    {
        $ctx = ['form_id' => $form->id, 'submission_id' => $submission->id];

        if ($form->admin_notification_email || $form->admin_template_id) {
            try {
                $this->emailService->notifyAdmin($form, $submission);
            } catch (\Throwable $e) {
                Log::error('Forms: admin email failed', $ctx + ['error' => $e->getMessage()]);
            }
        }

        if ($form->send_confirmation) {
            $clientEmail = $submission->getEmailValue();
            if ($clientEmail) {
                try {
                    $this->emailService->sendConfirmation($form, $submission, $clientEmail);
                } catch (\Throwable $e) {
                    Log::error('Forms: client confirmation email failed', $ctx + ['error' => $e->getMessage()]);
                }
            }
        }

        // Emails condicionales a queue para no bloquear el submit
        SendConditionalEmailsJob::dispatch($form, $submission);

        if ($form->webhook_url) {
            try {
                $this->webhookService->dispatch($form, $submission);
            } catch (\Throwable $e) {
                Log::error('Forms: webhook dispatch failed', $ctx + ['error' => $e->getMessage()]);
            }
        }

        $this->dispatchInAppNotification($form, $submission);
        $this->logActivity($form, $submission);
    }

    protected function dispatchInAppNotification(Form $form, FormSubmission $submission): void
    {
        try {
            if (! class_exists(NotificationService::class)) {
                Log::info('Forms: nueva submission (sin NotificationService)', [
                    'form_id' => $form->id,
                    'submission_id' => $submission->id,
                ]);

                return;
            }

            $admins = User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            $service = app(NotificationService::class);
            $notification = new NewFormSubmissionNotification($form, $submission);

            foreach ($admins as $admin) {
                $service->sendToUser($admin, $notification, ['database']);
            }
        } catch (\Throwable $e) {
            Log::error('Forms: in-app notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function logActivity(Form $form, FormSubmission $submission): void
    {
        try {
            if (function_exists('activity')) {
                activity('forms')
                    ->performedOn($submission)
                    ->withProperties(['form_id' => $form->id, 'form_name' => $form->name])
                    ->log('Nueva submission recibida');
            }
        } catch (\Throwable) {
            // silencioso
        }
    }

    /**
     * @return array{country: string|null, city: string|null}
     */
    /**
     * Dado el referrer HTTP, intenta resolver qué Page del módulo Page
     * originó el submit (lookup por slug en la URL).
     */
    protected function resolveSourcePageId(?string $referrer): ?int
    {
        if (! $referrer || ! class_exists(Page::class)) {
            return null;
        }

        try {
            $path = parse_url($referrer, PHP_URL_PATH) ?? '';
            $slug = trim(basename($path), '/ ');

            if ($slug === '') {
                return null;
            }

            return \Cache::remember('forms:source_page:'.md5($slug), 600, function () use ($slug) {
                return Page::query()
                    ->where('slug', $slug)
                    ->value('id');
            });
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveGeoData(string $ip): array
    {
        if (! function_exists('geoip')) {
            return ['country' => null, 'city' => null];
        }

        $cacheKey = 'forms:geoip:'.md5($ip);

        try {
            return \Cache::remember($cacheKey, 3600, function () use ($ip) {
                // Uso deadline via pcntl_alarm no es portable. Confiamos en el
                // timeout del driver GeoIP y capturamos cualquier error.
                $geo = @geoip($ip);

                return [
                    'country' => $geo->country ?? null,
                    'city' => $geo->city ?? null,
                ];
            });
        } catch (\Throwable $e) {
            Log::warning('Forms: GeoIP resolve failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return ['country' => null, 'city' => null];
        }
    }

    /**
     * @return array<string, string|null>
     */
    protected function extractUtmData(Request $request): array
    {
        $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $result = [];

        foreach ($utmKeys as $key) {
            $result[$key] = $request->input($key) ?? session("_utm.{$key}");
        }

        return $result;
    }
}
