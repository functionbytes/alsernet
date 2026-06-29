<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionEmail;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class FormEmailService
{
    /**
     * Envía email de notificación al administrador.
     */
    public function notifyAdmin(Form $form, FormSubmission $submission): bool
    {
        $recipients = $this->parseRecipients($form->admin_notification_email);

        if (empty($recipients) && ! $form->admin_template_id) {
            return false;
        }

        if (empty($recipients)) {
            $recipients = [config('mail.from.address')];
        }

        try {
            if ($form->admin_template_id && class_exists('\Modules\Mailer\Services\MailerTemplateRendererService')) {
                $result = $this->sendViaMailerTemplate($form->admin_template_id, $recipients, $form, $submission, $this->resolveDefaultLangId());
                if ($result) {
                    foreach ($recipients as $recipient) {
                        FormSubmissionEmail::log($submission, 'admin', $recipient, "Notificación vía plantilla #{$form->admin_template_id}")->markAsSent();
                    }
                }

                return $result;
            }

            $subject = "[{$form->name}] Nueva respuesta recibida - #{$submission->id}";

            Mail::send('forms::emails.notification', [
                'form' => $form,
                'submission' => $submission,
                'fieldsTable' => $this->buildFieldsTable($submission),
            ], fn ($message) => $message->to($recipients)->subject($subject));

            foreach ($recipients as $recipient) {
                FormSubmissionEmail::log($submission, 'admin', $recipient, $subject)->markAsSent();
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FormEmailService: admin notification error', [
                'form_id' => $form->id,
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
            foreach ($recipients as $recipient) {
                FormSubmissionEmail::log($submission, 'admin', $recipient, $subject ?? "Notificación admin #{$submission->id}")->markAsFailed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Envía email de confirmación al cliente.
     */
    public function sendConfirmation(Form $form, FormSubmission $submission, string $email): bool
    {
        try {
            if ($form->confirmation_template_id && class_exists('\Modules\Mailer\Services\MailerTemplateRendererService')) {
                $result = $this->sendViaMailerTemplate($form->confirmation_template_id, [$email], $form, $submission, $this->resolveSubmissionLangId($submission));
                if ($result) {
                    FormSubmissionEmail::log($submission, 'confirmation', $email, "Confirmación vía plantilla #{$form->confirmation_template_id}")->markAsSent();
                }

                return $result;
            }

            $subject = "Hemos recibido tu mensaje - {$form->name}";

            Mail::send('forms::emails.confirmation', [
                'form' => $form,
                'submission' => $submission,
                'successMessage' => $form->success_message ?? config('forms.default_success_message'),
            ], fn ($message) => $message->to($email)->subject($subject));

            FormSubmissionEmail::log($submission, 'confirmation', $email, $subject)->markAsSent();

            return true;
        } catch (\Throwable $e) {
            Log::error('FormEmailService: client confirmation error', [
                'form_id' => $form->id,
                'submission_id' => $submission->id,
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);
            FormSubmissionEmail::log($submission, 'confirmation', $email, $subject ?? "Confirmación #{$submission->id}")->markAsFailed($e->getMessage());

            return false;
        }
    }

    /**
     * Evalúa y envía emails condicionales basados en los valores del submission.
     */
    public function evaluateConditionalEmails(Form $form, FormSubmission $submission): void
    {
        $conditionals = $form->conditionalEmails()->active()->ordered()->get();

        foreach ($conditionals as $conditional) {
            $fieldValue = $submission->getValueFor($conditional->condition_field_key);

            if ($fieldValue === null || ! $conditional->matches($fieldValue)) {
                continue;
            }

            if ($conditional->admin_template_id) {
                $recipients = $this->parseRecipients($form->admin_notification_email);
                if (empty($recipients)) {
                    $recipients = [config('mail.from.address')];
                }
                $this->sendViaMailerTemplate($conditional->admin_template_id, $recipients, $form, $submission);
            }

            if ($conditional->client_template_id) {
                $clientEmail = $submission->getEmailValue();
                if ($clientEmail) {
                    $this->sendViaMailerTemplate($conditional->client_template_id, [$clientEmail], $form, $submission);
                }
            }
        }
    }

    /**
     * Construye tabla HTML con todos los valores del submission.
     */
    public function buildFieldsTable(FormSubmission $submission): string
    {
        $submission->loadMissing('values');

        $rows = '';
        foreach ($submission->values as $value) {
            if (in_array($value->field_type, ['hidden', 'signature'], true)) {
                continue;
            }

            $label = htmlspecialchars($value->field_label, ENT_QUOTES, 'UTF-8');
            $displayValue = htmlspecialchars($value->getDisplayValue(), ENT_QUOTES, 'UTF-8');

            $rows .= "<tr>
                <td style='padding:8px;border:1px solid #ddd;font-weight:bold;background:#f9f9f9;width:35%'>{$label}</td>
                <td style='padding:8px;border:1px solid #ddd'>{$displayValue}</td>
            </tr>";
        }

        return "<table style='width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px'><tbody>{$rows}</tbody></table>";
    }

    /**
     * Envía email a través del módulo Mailer con template.
     */
    protected function sendViaMailerTemplate(int $templateId, array $recipients, Form $form, FormSubmission $submission, ?int $langId = null): bool
    {
        try {
            $template = MailerTemplate::find($templateId);

            if (! $template) {
                return false;
            }

            $variables = $this->buildTemplateVariables($form, $submission);
            $body = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);
            $translation = $template->translate($langId);
            $rawSubject = ($translation?->subject) ?? $template->subject ?? "[{$form->name}] Nueva respuesta #{$submission->id}";
            $subject = MailerTemplateRendererService::replaceVariables($rawSubject, $variables);

            Mail::send([], [], function ($message) use ($recipients, $body, $subject) {
                $message->to($recipients)->subject($subject)->html($body);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('FormEmailService: Mailer template error', [
                'template_id' => $templateId,
                'recipients_count' => count($recipients),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Construye variables disponibles para plantillas de email.
     *
     * @return array<string, string>
     */
    protected function buildTemplateVariables(Form $form, FormSubmission $submission): array
    {
        $submission->loadMissing('values');

        $submitterName = $submission->values
            ->firstWhere(fn ($v) => in_array($v->field_key, ['nombre', 'name', 'full_name', 'nombre_completo']))
            ?->value ?? '';
        $nameParts = explode(' ', trim($submitterName), 2);

        $siteUrl = config('app.url');
        $companyName = setting('mail_from_name') ?: config('app.name');

        return [
            '{FORM_NAME}' => $form->name,
            '{FORM_ID}' => (string) $form->id,
            '{SUBMISSION_ID}' => "#{$submission->id}",
            '{SUBMISSION_DATE}' => $submission->created_at->format('d/m/Y H:i'),
            '{SUBMISSION_STATUS}' => ucfirst($submission->status),
            '{FIELDS_TABLE}' => $this->buildFieldsTable($submission),
            '{SUBMITTER_IP}' => $submission->ip_address ?? 'N/A',
            '{SUBMITTER_COUNTRY}' => $submission->country ?? 'N/A',
            '{SITE_NAME}' => $companyName,
            '{SITE_URL}' => $siteUrl,
            '{SITE_LOGO_URL}' => $siteUrl.'/media/0/fnL5XDsB9dREbPHjAk5PzhDQd4o8btomFgYosNbA.png',
            '{COMPANY_NAME}' => $companyName,
            '{COMPANY_PHONE}' => '+351 913 893 333',
            '{COMPANY_EMAIL}' => 'info@caixilhariablanco.pt',
            '{COMPANY_ADDRESS}' => 'Estrada Nacional 8 N12',
            '{COMPANY_CITY}' => 'Alcobaça',
            '{COMPANY_POSTAL_CODE}' => '2460-349',
            '{COMPANY_COUNTRY}' => 'Portugal',
            '{CURRENT_YEAR}' => date('Y'),
            '{CURRENT_DATE}' => date('d/m/Y'),
            '{USER_NAME}' => $submitterName,
            '{USER_FIRST_NAME}' => $nameParts[0] ?? $submitterName,
            '{USER_LAST_NAME}' => $nameParts[1] ?? '',
            '{ADMIN_URL}' => route('settings.forms.submissions.show', [
                'form' => $form->id,
                'submission' => $submission->id,
            ]),
        ];
    }

    /**
     * @return string[]
     */
    protected function parseRecipients(?string $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $emails))));
    }

    protected function resolveDefaultLangId(): int
    {
        return Locale::query()->where('is_default', true)->value('id')
            ?? Locale::query()->first()?->id
            ?? 1;
    }

    protected function resolveSubmissionLangId(FormSubmission $submission): int
    {
        if ($submission->locale) {
            $id = Locale::query()->where('code', $submission->locale)->value('id');
            if ($id) {
                return $id;
            }
        }

        return $this->resolveDefaultLangId();
    }
}
