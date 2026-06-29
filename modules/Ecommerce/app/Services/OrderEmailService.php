<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Mail\OrderMail;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Shipment;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

class OrderEmailService
{
    public static function sendOrderCreated(Order $order): bool
    {
        return self::send($order, 'ecommerce_order_created');
    }

    public static function sendOrderShipped(Order $order, Shipment $shipment): bool
    {
        $trackingHtml = '';
        if ($shipment->tracking_id) {
            $trackingHtml = sprintf(
                '<div style="background:#e8f4fd;border-left:4px solid #2196F3;padding:12px 16px;margin:16px 0;border-radius:4px;">'
                .'<p style="margin:0 0 4px 0;font-weight:bold;">Número de seguimiento: %s</p>'
                .(($shipment->shipping_company_name) ? '<p style="margin:0;font-size:14px;color:#555;">Transportadora: %s</p>' : '')
                .'</div>',
                htmlspecialchars($shipment->tracking_id),
                htmlspecialchars($shipment->shipping_company_name ?? '')
            );
        }

        return self::send($order, 'ecommerce_order_shipped', [
            'TRACKING_ID' => $shipment->tracking_id ?? '',
            'TRACKING_LINK' => $shipment->tracking_link ?? '',
            'SHIPPING_COMPANY' => $shipment->shipping_company_name ?? '',
            'TRACKING_BLOCK' => $trackingHtml,
        ]);
    }

    public static function sendOrderCompleted(Order $order): bool
    {
        return self::send($order, 'ecommerce_order_completed');
    }

    public static function sendOrderCancelled(Order $order): bool
    {
        return self::send($order, 'ecommerce_order_cancelled', [
            'CANCEL_REASON' => $order->admin_note ?? '',
        ]);
    }

    public static function sendPaymentConfirmed(Order $order): bool
    {
        return self::send($order, 'ecommerce_payment_confirmed');
    }

    public static function sendReviewRequest(Order $order): bool
    {
        return self::send($order, 'ecommerce_review_request');
    }

    /**
     * Core send method: resolves template, builds variables, renders, queues.
     *
     * @param  array<string, string>  $extraVars
     */
    private static function send(Order $order, string $templateKey, array $extraVars = []): bool
    {
        try {
            $recipient = $order->customer?->email;
            if (! $recipient) {
                return false;
            }

            $template = self::resolveTemplate($templateKey);
            if (! $template) {
                Log::warning('No Mailer template found for order email', [
                    'template_key' => $templateKey,
                    'order_id' => $order->id,
                ]);

                return false;
            }

            $langId = Locale::resolveLegacyLangId();
            $translation = $template->translate($langId);

            if (! $translation?->subject) {
                Log::warning('No translation for order email template', [
                    'template_key' => $templateKey,
                    'lang_id' => $langId,
                ]);

                return false;
            }

            $variables = array_merge(self::buildOrderVariables($order), $extraVars);
            $subject = MailerTemplateRendererService::replaceVariables($translation->subject, $variables);
            $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            Mail::to($recipient)->queue(new OrderMail($order, $subject, $content));

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending order email', [
                'template_key' => $templateKey,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<string, string> */
    private static function buildOrderVariables(Order $order): array
    {
        $order->loadMissing(['customer', 'items', 'shippingAddress']);

        $customerName = $order->customer?->name ?? 'Cliente';
        $firstName = explode(' ', $customerName)[0];

        return [
            'CUSTOMER_NAME' => $customerName,
            'CUSTOMER_FIRSTNAME' => $firstName,
            'CUSTOMER_EMAIL' => $order->customer?->email ?? '',
            'ORDER_CODE' => $order->code ?? '',
            'ORDER_STATUS' => $order->status?->label() ?? '',
            'ORDER_SUBTOTAL' => '$'.number_format((float) $order->sub_total, 2),
            'ORDER_SHIPPING' => '$'.number_format((float) $order->shipping_amount, 2),
            'ORDER_TAX' => '$'.number_format((float) $order->tax_amount, 2),
            'ORDER_TOTAL' => '$'.number_format((float) $order->total, 2),
            'ORDER_ITEMS_HTML' => self::buildOrderItemsHtml($order),
            'SITE_NAME' => config('app.name'),
            'COMPANY_NAME' => config('app.name'),
        ];
    }

    private static function buildOrderItemsHtml(Order $order): string
    {
        $rows = $order->items->map(function (OrderItem $item): string {
            return sprintf(
                '<tr>'
                .'<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">%s</td>'
                .'<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;">%d</td>'
                .'<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">$%s</td>'
                .'</tr>',
                htmlspecialchars($item->product_name),
                $item->qty,
                number_format((float) $item->total, 2)
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

    private static function resolveTemplate(string $key): ?MailerTemplate
    {
        return MailerTemplate::where('key', $key)
            ->where('is_enabled', true)
            ->first();
    }
}
