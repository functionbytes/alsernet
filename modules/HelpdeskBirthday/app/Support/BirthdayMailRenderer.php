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
            // Todo lo del bono sale del destinatario cuando lo tiene: Gestión
            // emite uno por cliente, con su propio importe y su validez. Lo de
            // la campaña queda como respaldo para las promociones que reparten
            // un único código a todo el mundo.
            'COUPON_CODE' => self::couponCode($campaign, $recipient),
            'COUPON_VERIFICATION_CODE' => (string) $recipient->coupon_verification_code,
            'COUPON_VALID_FROM' => self::date($recipient->coupon_valid_from ?? $campaign->coupon_valid_from),
            'COUPON_VALID_TO' => self::date($recipient->coupon_valid_to ?? $campaign->coupon_valid_to),
            'COUPON_AMOUNT' => self::money($recipient->coupon_amount ?? $campaign->coupon_amount),
            'COUPON_MIN_PURCHASE' => self::money($recipient->coupon_min_purchase ?? $campaign->coupon_min_purchase),
            'SHOP_URL' => self::shopUrl(),
            'UNSUBSCRIBE_URL' => self::unsubscribeUrl($recipient),
        ];
    }

    /**
     * El código tal como lo usa el cliente: "{idbono}-{codigo_verificacion}",
     * igual que en la tienda. Guardamos las dos partes por separado porque
     * consultar o consumir el bono en Gestión las necesita sueltas, pero al
     * cliente hay que darle el código entero — solo con el id no puede canjear
     * nada.
     */
    private static function couponCode(BirthdayCampaign $campaign, BirthdayRecipient $recipient): string
    {
        $code = trim((string) $recipient->coupon_code);

        if ($code === '') {
            return (string) $campaign->coupon_code;
        }

        $verification = trim((string) $recipient->coupon_verification_code);

        return $verification !== '' ? $code.'-'.$verification : $code;
    }

    private static function date(mixed $value): string
    {
        return $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : '';
    }

    private static function money(mixed $value): string
    {
        return $value !== null && $value !== '' ? number_format((float) $value, 2, ',', '.') : '';
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
