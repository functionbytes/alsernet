<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Registers Mailer email templates for order lifecycle notifications.
 *
 * Templates:
 *  - ecommerce_order_created      → Orden recibida (confirmación al cliente)
 *  - ecommerce_order_shipped      → Orden enviada (con datos de seguimiento)
 *  - ecommerce_order_completed    → Orden completada
 *  - ecommerce_order_cancelled    → Orden cancelada
 *  - ecommerce_payment_confirmed  → Pago confirmado
 *  - ecommerce_review_request     → Solicitud de reseña post-compra
 */
class EcommerceOrderEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        foreach ($this->getTemplates() as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => 'ecommerce',
                    'is_enabled' => true,
                    'is_protected' => true,
                ]
            );

            if (! $mailerTemplate->uid) {
                $mailerTemplate->update(['uid' => (string) Str::uuid()]);
            }

            foreach ($langs as $lang) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $lang->id],
                    [
                        'subject' => $template['subject'],
                        'content' => $template['content'],
                    ]
                );
            }

            $this->command->info("Template '{$template['key']}' (ID: {$mailerTemplate->id}) — {$langs->count()} idioma(s)");
        }

        if ($langs->isEmpty()) {
            $this->command->warn('No languages configured — templates created without translations. Re-run after adding languages.');
        }

        $this->command->info('Ecommerce order email templates seeded ('.count($this->getTemplates()).' templates)');
    }

    /** @return array<int, array{key: string, name: string, description: string, subject: string, content: string}> */
    private function getTemplates(): array
    {
        return [
            [
                'key' => 'ecommerce_order_created',
                'name' => 'Orden recibida',
                'description' => 'Email enviado al cliente cuando su orden ha sido recibida y está siendo procesada.',
                'subject' => '¡Hemos recibido tu orden #{ORDER_CODE}!',
                'content' => $this->orderCreatedTemplate(),
            ],
            [
                'key' => 'ecommerce_order_shipped',
                'name' => 'Orden enviada',
                'description' => 'Email enviado al cliente cuando su orden ha sido despachada. Incluye información de seguimiento si está disponible.',
                'subject' => 'Tu orden #{ORDER_CODE} ha sido enviada',
                'content' => $this->orderShippedTemplate(),
            ],
            [
                'key' => 'ecommerce_order_completed',
                'name' => 'Orden completada',
                'description' => 'Email enviado al cliente cuando su orden ha sido marcada como completada.',
                'subject' => 'Tu orden #{ORDER_CODE} ha sido completada',
                'content' => $this->orderCompletedTemplate(),
            ],
            [
                'key' => 'ecommerce_order_cancelled',
                'name' => 'Orden cancelada',
                'description' => 'Email enviado al cliente cuando su orden ha sido cancelada.',
                'subject' => 'Tu orden #{ORDER_CODE} ha sido cancelada',
                'content' => $this->orderCancelledTemplate(),
            ],
            [
                'key' => 'ecommerce_payment_confirmed',
                'name' => 'Pago confirmado',
                'description' => 'Email enviado al cliente cuando su pago ha sido confirmado.',
                'subject' => 'Pago confirmado para tu orden #{ORDER_CODE}',
                'content' => $this->paymentConfirmedTemplate(),
            ],
            [
                'key' => 'ecommerce_review_request',
                'name' => 'Solicitud de reseña',
                'description' => 'Email enviado al cliente después de completar su orden, solicitando que deje una reseña de los productos.',
                'subject' => '¿Cómo fue tu experiencia con tu orden #{ORDER_CODE}?',
                'content' => $this->reviewRequestTemplate(),
            ],
        ];
    }

    private function orderCreatedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #90bb13; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">¡Orden recibida!</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Hemos recibido tu orden y está siendo procesada. Te notificaremos cuando sea enviada.</p>

        <div style="background: #f0f5e3; border-left: 4px solid #90bb13; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 18px; font-weight: bold;">Orden: #{ORDER_CODE}</p>
        </div>

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalle de tu orden</h3>

        {ORDER_ITEMS_HTML}

        <table style="width:100%;margin-top:12px;">
            <tr><td style="padding:4px 12px;text-align:right;color:#777;">Total</td><td style="padding:4px 12px;text-align:right;font-weight:bold;font-size:16px;">{ORDER_TOTAL}</td></tr>
        </table>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Gracias por tu compra,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }

    private function orderShippedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #2196F3; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">¡Tu pedido fue despachado!</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Tenemos buenas noticias: tu orden <strong>#{ORDER_CODE}</strong> ha sido enviada y está en camino.</p>

        {TRACKING_BLOCK}

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Productos enviados</h3>

        {ORDER_ITEMS_HTML}

        <table style="width:100%;margin-top:12px;">
            <tr><td style="padding:4px 12px;text-align:right;color:#777;">Total</td><td style="padding:4px 12px;text-align:right;font-weight:bold;font-size:16px;">{ORDER_TOTAL}</td></tr>
        </table>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Gracias por tu compra,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }

    private function orderCompletedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #13C672; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">¡Orden completada!</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Tu orden <strong>#{ORDER_CODE}</strong> ha sido marcada como completada. Esperamos que estés satisfecho/a con tu compra.</p>

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalle de tu orden</h3>

        {ORDER_ITEMS_HTML}

        <table style="width:100%;margin-top:12px;">
            <tr><td style="padding:4px 12px;text-align:right;color:#777;">Total pagado</td><td style="padding:4px 12px;text-align:right;font-weight:bold;font-size:16px;">{ORDER_TOTAL}</td></tr>
        </table>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Gracias por elegirnos,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }

    private function orderCancelledTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #FA896B; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">Orden cancelada</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Tu orden <strong>#{ORDER_CODE}</strong> ha sido cancelada.</p>

        <div id="cancel-reason-block" style="background:#fff3f0;border-left:4px solid #FA896B;padding:12px 16px;margin:16px 0;border-radius:4px;">
            <p style="margin:0;font-size:14px;color:#555;"><strong>Motivo:</strong> {CANCEL_REASON}</p>
        </div>

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalle de la orden cancelada</h3>

        {ORDER_ITEMS_HTML}

        <table style="width:100%;margin-top:12px;">
            <tr><td style="padding:4px 12px;text-align:right;color:#777;">Total</td><td style="padding:4px 12px;text-align:right;font-weight:bold;font-size:16px;">{ORDER_TOTAL}</td></tr>
        </table>

        <p style="margin-top: 16px; font-size: 14px; color: #555;">Si tienes alguna pregunta, no dudes en contactarnos.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Atentamente,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }

    private function paymentConfirmedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #90bb13; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">¡Pago confirmado!</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Tu pago para la orden <strong>#{ORDER_CODE}</strong> ha sido confirmado exitosamente. Procederemos a preparar tu pedido.</p>

        <div style="background: #d4edda; border-left: 4px solid #13C672; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #155724;">Total pagado: {ORDER_TOTAL}</p>
        </div>

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalle de tu orden</h3>

        {ORDER_ITEMS_HTML}

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Gracias por tu compra,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }

    private function reviewRequestTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #FEC90F; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #333; margin: 0; font-size: 22px;">¿Cómo fue tu experiencia?</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Tu orden <strong>#{ORDER_CODE}</strong> fue completada. Nos encantaría saber tu opinión sobre los productos que recibiste.</p>
        <p>Tu reseña ayuda a otros compradores a tomar mejores decisiones.</p>

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Productos de tu orden</h3>

        {ORDER_ITEMS_HTML}

        <p style="font-size: 14px; color: #555; margin-top: 16px;">¡Gracias por confiar en nosotros!</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Con cariño,<br><strong>{COMPANY_NAME}</strong></p>
        <p style="font-size: 11px; color: #bbb; margin-top: 12px;">Este es un correo automático. Por favor no respondas directamente.</p>
    </div>
</div>
HTML;
    }
}
