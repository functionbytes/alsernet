<?php

namespace Modules\Attention\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Mail\AttentionCustomMail;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionMail;
use Modules\Core\Models\Setting;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableValueService;

class AttentionEmailTemplateService
{
    /**
     * Send confirmation email when attention is created
     */
    public static function sendConfirmation(Attention $attention, ?int $userId = null): bool
    {
        try {
            $template = self::resolveTemplate('attention.mail_template_confirmation_id', 'attention_confirmation');

            if (! $template) {
                Log::warning('No confirmation template configured for Attention', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            $recipient = $attention->customer_email;
            if (! $recipient) {
                Log::warning('No recipient email for Attention confirmation', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            // Get lang_id (default to 1 for Spanish)
            $langId = 1;

            $variables = self::prepareAttentionVariables($attention);

            // Get translation for the template
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                Log::error('Template has no translation', [
                    'template_id' => $template->id,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new AttentionCustomMail($attention, $subject, $content));

            // Log the email
            self::logEmail($attention, 'confirmation', $subject, $content, $template, [], true, null, $userId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending attention confirmation email', [
                'attention_uid' => $attention->uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send notification when attention is assigned to a user
     */
    public static function sendAssignedNotification(Attention $attention, ?int $userId = null): bool
    {
        try {
            $template = self::resolveTemplate('attention.mail_template_assigned_id', 'attention_assigned');

            if (! $template) {
                Log::warning('No assigned template configured for Attention', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            $recipient = $attention->customer_email;
            if (! $recipient) {
                Log::warning('No recipient email for Attention assigned notification', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            // Get lang_id (default to 1 for Spanish)
            $langId = 1;

            $variables = self::prepareAttentionVariables($attention);

            // Add assigned user info
            if ($attention->assignedUser) {
                $variables['ASSIGNED_USER_NAME'] = $attention->assignedUser->name;
                $variables['ASSIGNED_USER_EMAIL'] = $attention->assignedUser->email;
            }

            // Get translation for the template
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                Log::error('Template has no translation', [
                    'template_id' => $template->id,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new AttentionCustomMail($attention, $subject, $content));

            // Log the email
            self::logEmail($attention, 'assigned', $subject, $content, $template, [], true, null, $userId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending attention assigned email', [
                'attention_uid' => $attention->uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send notification when attention enters "in process" status
     */
    public static function sendInProcessNotification(Attention $attention, ?int $userId = null): bool
    {
        try {
            $template = self::resolveTemplate('attention.mail_template_in_process_id', 'attention_in_process');

            if (! $template) {
                Log::warning('No in-process template configured for Attention', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            $recipient = $attention->customer_email;
            if (! $recipient) {
                Log::warning('No recipient email for Attention in-process notification', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            $langId = 1;

            $variables = self::prepareAttentionVariables($attention);
            $variables['TRACKING_URL'] = url("/peticiones/tracking/{$attention->radicado}");

            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                Log::error('Template has no translation', [
                    'template_id' => $template->id,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new AttentionCustomMail($attention, $subject, $content));

            self::logEmail($attention, 'in_process', $subject, $content, $template, [], true, null, $userId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending attention in-process email', [
                'attention_uid' => $attention->uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send notification when attention is resolved
     */
    public static function sendResolutionNotification(Attention $attention, ?int $userId = null): bool
    {
        try {
            $template = self::resolveTemplate('attention.mail_template_resolution_id', 'attention_resolution');

            if (! $template) {
                Log::warning('No resolution template configured for Attention', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            $recipient = $attention->customer_email;
            if (! $recipient) {
                Log::warning('No recipient email for Attention resolution', [
                    'attention_uid' => $attention->uid,
                ]);

                return false;
            }

            // Get lang_id (default to 1 for Spanish)
            $langId = 1;

            $variables = self::prepareAttentionVariables($attention);

            // Add resolution details
            $variables['RESOLUTION'] = $attention->resolution ?? '';
            $variables['RESOLUTION_DATE'] = $attention->resolved_at ? $attention->resolved_at->format('d/m/Y H:i') : '';
            $variables['RESPONSE_TYPE'] = $attention->response_type?->label() ?? '';

            // Get translation for the template
            $translation = $template->translate($langId);
            if (! $translation || ! $translation->subject) {
                Log::error('Template has no translation', [
                    'template_id' => $template->id,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)
                ->queue(new AttentionCustomMail($attention, $subject, $content));

            // Log the email
            self::logEmail($attention, 'resolution', $subject, $content, $template, [
                'resolution' => $attention->resolution,
                'resolved_at' => $attention->resolved_at,
            ], true, null, $userId);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending attention resolution email', [
                'attention_uid' => $attention->uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Prepare variables for attention emails
     */
    public static function prepareAttentionVariables(Attention $attention): array
    {
        // Get system variables
        $langId = 1; // Default to Spanish
        $locale = 'es';
        $variables = self::getSystemVariables($locale, $langId);

        // Customer information
        $customerName = $attention->is_anonymous ? 'ANÓNIMO' : $attention->full_name;

        // Attention specific variables
        $variables = array_merge($variables, [
            // Customer info
            'CUSTOMER_NAME' => $customerName,
            'CUSTOMER_FIRSTNAME' => $attention->customer_firstname ?? '',
            'CUSTOMER_LASTNAME' => $attention->customer_lastname ?? '',
            'CUSTOMER_EMAIL' => $attention->customer_email ?? '',
            'CUSTOMER_PHONE' => $attention->customer_cellphone ?? '',
            'CUSTOMER_DNI' => $attention->customer_dni ?? '',
            'CUSTOMER_ADDRESS' => $attention->customer_address ?? '',

            // Attention info
            'ATTENTION_UID' => $attention->uid ?? '',
            'ATTENTION_RADICADO' => $attention->radicado ?? '',
            'ATTENTION_SUBJECT' => $attention->subject ?? '',
            'ATTENTION_DESCRIPTION' => $attention->description ?? '',
            'ATTENTION_STATUS' => $attention->status?->label() ?? '',
            'ATTENTION_TYPE' => $attention->type?->name ?? '',
            'ATTENTION_CATEGORY' => $attention->category?->name ?? '',

            // Department and assignment
            'DEPARTMENT_NAME' => $attention->department?->name ?? '',
            'ASSIGNED_USER_NAME' => $attention->assignedUser?->name ?? '',
            'ASSIGNED_USER_EMAIL' => $attention->assignedUser?->email ?? '',

            // Dates
            'CREATED_DATE' => $attention->created_at ? $attention->created_at->format('d/m/Y') : '',
            'CREATED_DATETIME' => $attention->created_at ? $attention->created_at->format('d/m/Y H:i') : '',
            'RESOLVED_DATE' => $attention->resolved_at ? $attention->resolved_at->format('d/m/Y') : '',
            'CLOSED_DATE' => $attention->closed_at ? $attention->closed_at->format('d/m/Y') : '',

            // URLs
            'ATTENTION_URL' => url("/attentions/show/{$attention->uid}"),
            'TRACKING_URL' => url("/peticiones/tracking/{$attention->radicado}"),
            'PORTAL_URL' => config('app.url'),
        ]);

        return $variables;
    }

    /**
     * Get system variables (always available)
     */
    private static function getSystemVariables(string $locale = 'es', int $langId = 1): array
    {
        // Get all translated variables from database
        $realValues = MailerVariableValueService::getTranslatedValues($langId);

        // Dynamic variables calculated at runtime
        $dynamicVariables = [
            // System dates
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_DATE' => date('d/m/Y'),
            'CURRENT_DATETIME' => date('d/m/Y H:i'),

            // Language
            'LANG_CODE' => $locale,
            'LANGUAGE' => $locale,

            // Email subject (will be filled from template)
            'EMAIL_SUBJECT' => '',
        ];

        // Merge: DB values first, then dynamic (dynamic takes priority)
        return array_merge($realValues, $dynamicVariables);
    }

    /**
     * Resolve template from configuration or use fallback by key
     */
    private static function resolveTemplate(string $settingKey, string $fallbackKey, array $alternativeKeys = []): ?MailerTemplate
    {
        // Try to get configured template ID
        $configuredTemplateId = Setting::get($settingKey);

        if ($configuredTemplateId) {
            // Search by configured ID
            $template = MailerTemplate::find($configuredTemplateId);
            if ($template && $template->is_enabled) {
                return $template;
            }
        }

        // Fallback: search by primary key
        $template = MailerTemplate::where('key', $fallbackKey)
            ->where('is_enabled', true)
            ->first();

        if ($template) {
            return $template;
        }

        // Try alternative keys
        foreach ($alternativeKeys as $key) {
            $template = MailerTemplate::where('key', $key)
                ->where('is_enabled', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Log email to attention_mails table
     */
    private static function logEmail(
        Attention $attention,
        string $emailType,
        string $subject,
        string $content,
        ?MailerTemplate $template = null,
        array $metadata = [],
        bool $success = true,
        ?string $errorMessage = null,
        ?int $adminId = null
    ): ?AttentionMail {
        try {
            $mail = AttentionMail::logEmail(
                $attention,
                $emailType,
                $subject,
                $content,
                null,
                $template?->id,
                $adminId,
                $metadata
            );

            if ($success) {
                $mail->markAsSent();
            } else {
                $mail->markAsFailed($errorMessage ?? 'Unknown error');
            }

            return $mail;
        } catch (\Exception $e) {
            Log::error('Failed to log attention email', [
                'attention_uid' => $attention->uid,
                'email_type' => $emailType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
