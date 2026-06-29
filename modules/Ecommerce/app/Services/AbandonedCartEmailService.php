<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Mail\AbandonedCartMail;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Customer;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class AbandonedCartEmailService
{
    /**
     * Send abandoned cart recovery email to a customer.
     *
     * @param  Collection<int, Cart>  $cartItems  Cart items for this customer (with product loaded)
     */
    public static function send(Customer $customer, Collection $cartItems): bool
    {
        try {
            if (! $customer->email) {
                return false;
            }

            $template = self::resolveTemplate();

            if (! $template) {
                Log::warning('No abandoned cart template found', ['customer_id' => $customer->id]);

                return false;
            }

            $langId = Locale::resolveLegacyLangId();
            $translation = $template->translate($langId);

            if (! $translation) {
                Log::warning('No translation for abandoned cart template', [
                    'template_key' => $template->key,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $variables = self::buildVariables($customer, $cartItems);

            // Subject: setting override → template subject → fallback
            $configuredSubject = Setting::get('ecommerce.abandoned_cart_email_subject');
            $rawSubject = $configuredSubject ?: ($translation->subject ?? 'Tu carrito te está esperando');
            $subject = MailerTemplateRendererService::replaceVariables($rawSubject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($customer->email)
                ->queue(new AbandonedCartMail($customer, $subject, $content));

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending abandoned cart email', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build template variables for the abandoned cart email.
     *
     * @param  Collection<int, Cart>  $cartItems
     * @return array<string, string>
     */
    private static function buildVariables(Customer $customer, Collection $cartItems): array
    {
        $offerFreeShipping = Setting::get('ecommerce.abandoned_cart_offer_free_shipping') === '1';

        $cartTotal = $cartItems->sum(fn (Cart $item) => ($item->product->getCurrentPrice() ?? 0) * $item->qty);
        $cartUrl = route('cart.index');

        $freeShippingBadge = $offerFreeShipping
            ? '<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;padding:10px 16px;margin:16px 0;text-align:center;font-weight:bold;color:#155724;">🚚 ¡Envío gratis en tu pedido!</div>'
            : '';

        return [
            'CUSTOMER_NAME' => $customer->name ?? 'Cliente',
            'CUSTOMER_FIRSTNAME' => explode(' ', $customer->name ?? 'Cliente')[0],
            'CUSTOMER_EMAIL' => $customer->email ?? '',
            'CART_RECOVER_URL' => $cartUrl,
            'CART_ITEMS_HTML' => self::buildCartItemsHtml($cartItems),
            'CART_TOTAL' => '$'.number_format($cartTotal, 2),
            'FREE_SHIPPING_BADGE' => $freeShippingBadge,
            'SITE_NAME' => config('app.name'),
            'COMPANY_NAME' => config('app.name'),
        ];
    }

    /**
     * Build an HTML table of cart items.
     *
     * @param  Collection<int, Cart>  $cartItems
     */
    private static function buildCartItemsHtml(Collection $cartItems): string
    {
        $rows = $cartItems->map(function (Cart $item): string {
            $name = $item->product->name ?? 'Producto';
            $price = $item->product->getCurrentPrice() ?? 0;
            $subtotal = $price * $item->qty;

            return sprintf(
                '<tr><td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">%s</td><td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;">%d</td><td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">$%s</td></tr>',
                htmlspecialchars($name),
                $item->qty,
                number_format($subtotal, 2)
            );
        })->implode('');

        return '<table style="width:100%;border-collapse:collapse;">'
            .'<thead><tr>'
            .'<th style="padding:8px 12px;text-align:left;background:#f8f8f8;border-bottom:2px solid #e0e0e0;font-size:12px;text-transform:uppercase;color:#666;">Producto</th>'
            .'<th style="padding:8px 12px;text-align:center;background:#f8f8f8;border-bottom:2px solid #e0e0e0;font-size:12px;text-transform:uppercase;color:#666;">Cant.</th>'
            .'<th style="padding:8px 12px;text-align:right;background:#f8f8f8;border-bottom:2px solid #e0e0e0;font-size:12px;text-transform:uppercase;color:#666;">Subtotal</th>'
            .'</tr></thead>'
            .'<tbody>'.$rows.'</tbody>'
            .'</table>';
    }

    /**
     * Resolve the MailerTemplate to use based on the configured template key.
     */
    private static function resolveTemplate(): ?MailerTemplate
    {
        $templateKey = Setting::get('ecommerce.abandoned_cart_email_template') ?: 'abandoned_cart';

        $template = MailerTemplate::where('key', $templateKey)
            ->where('is_enabled', true)
            ->first();

        if (! $template) {
            // Fallback to the other template if configured one is missing
            $fallback = $templateKey === 'abandoned_cart' ? 'order_recover' : 'abandoned_cart';
            $template = MailerTemplate::where('key', $fallback)
                ->where('is_enabled', true)
                ->first();
        }

        return $template;
    }
}
