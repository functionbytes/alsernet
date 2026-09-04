<?php

namespace Modules\HelpdeskBirthday\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayLanguageResolver;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

/**
 * Renderiza el correo de cumpleaños desde su plantilla del módulo Mailer, para
 * que el diseño se edite en el admin y no en un blade. Mismo patrón que
 * HelpdeskTickets\Support\TicketMailRenderer.
 */
class BirthdayMailRenderer
{
    /**
     * @return array{0: string, 1: string} [asunto, htmlContent]
     */
    public static function render(BirthdayCampaign $campaign, BirthdayRecipient $recipient): array
    {
        $key = $campaign->template_key ?: (string) config('helpdeskbirthday.template_key', 'birthday-coupon');
        $fallbackSubject = __('helpdeskbirthday::messages.mail_fallback_subject');

        $variables = self::variables($campaign, $recipient);

        $template = MailerTemplate::query()
            ->where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (! $template) {
            Log::warning('[HelpdeskBirthday] Plantilla no encontrada, se usa el fallback', ['key' => $key]);

            return [$fallbackSubject, '<p>'.e($fallbackSubject).'</p>'];
        }

        // El idioma del destinatario manda; sin traducción para ese idioma, el
        // propio renderer del módulo Mailer cae al idioma por defecto.
        $langId = app(BirthdayLanguageResolver::class)->langId($recipient->lang);
        $translation = $langId !== null ? $template->translate($langId) : null;

        $subject = MailerTemplateRendererService::replaceVariables(
            $translation?->subject ?: ($template->subject ?: $fallbackSubject),
            $variables
        );

        return [$subject, MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId)];
    }

    /**
     * Variables {TAG} disponibles en la plantilla.
     *
     * @return array<string, string>
     */
    public static function variables(BirthdayCampaign $campaign, BirthdayRecipient $recipient): array
    {
        return [
            'CUSTOMER_NAME' => $recipient->displayName(),
            'CUSTOMER_EMAIL' => (string) $recipient->email,
            'COUPON_CODE' => (string) $campaign->coupon_code,
            'COUPON_VALID_FROM' => $campaign->coupon_valid_from?->format('d/m/Y') ?? '',
            'COUPON_VALID_TO' => $campaign->coupon_valid_to?->format('d/m/Y') ?? '',
            'COUPON_AMOUNT' => $campaign->coupon_amount !== null ? number_format((float) $campaign->coupon_amount, 2, ',', '.') : '',
            'COUPON_MIN_PURCHASE' => $campaign->coupon_min_purchase !== null ? number_format((float) $campaign->coupon_min_purchase, 2, ',', '.') : '',
            'SHOP_URL' => self::shopUrl(),
            'UNSUBSCRIBE_URL' => self::unsubscribeUrl($recipient),
        ];
    }

    /**
     * Destino del botón principal. Si no hay tienda configurada cae a la app,
     * para no dejar un href vacío que en algunos clientes rompe el botón.
     */
    private static function shopUrl(): string
    {
        $url = trim((string) config('helpdeskbirthday.shop_url', ''));

        return $url !== '' ? $url : (string) config('app.url');
    }

    /**
     * Enlace de baja firmado: al abrirlo, la dirección entra en
     * email_suppressions y deja de recibir estos correos.
     */
    private static function unsubscribeUrl(BirthdayRecipient $recipient): string
    {
        return URL::signedRoute('helpdeskbirthday.unsubscribe', [
            'email' => $recipient->email,
        ]);
    }
}
