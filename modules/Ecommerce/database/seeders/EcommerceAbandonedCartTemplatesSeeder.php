<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Registers Mailer email templates for abandoned cart recovery.
 *
 * Templates:
 *  - abandoned_cart  → Modern template (default)
 *  - order_recover   → Classic template
 *
 * Variables available: {CUSTOMER_NAME}, {CUSTOMER_FIRSTNAME}, {CART_RECOVER_URL},
 *   {CART_ITEMS_HTML}, {CART_TOTAL}, {FREE_SHIPPING_BADGE}, {COMPANY_NAME}, {SITE_NAME}
 */
class EcommerceAbandonedCartTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        foreach ($this->getTemplates() as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => 'ecommerce',
                    'is_enabled' => true,
                    'is_protected' => true,
                ]
            );

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
            $this->command->warn('No languages configured — templates created without translations. Run this seeder again after adding languages.');
        }

        $this->command->info('Ecommerce abandoned cart templates seeded ('.count($this->getTemplates()).' templates)');
    }

    /** @return array<int, array{key: string, name: string, description: string, subject: string, content: string}> */
    private function getTemplates(): array
    {
        return [
            [
                'key' => 'abandoned_cart',
                'name' => 'Carrito abandonado (moderna)',
                'description' => 'Plantilla moderna de recuperación de carrito abandonado. Incluye tabla de productos, total, botón CTA y opcionalmente una oferta de envío gratis.',
                'subject' => 'Completa tu compra - ¡Tu carrito te está esperando!',
                'content' => $this->modernTemplate(),
            ],
            [
                'key' => 'order_recover',
                'name' => 'Carrito abandonado (clásica)',
                'description' => 'Plantilla clásica de recuperación de carrito abandonado con diseño limpio y tabla de productos.',
                'subject' => 'Tu carrito te está esperando, {CUSTOMER_FIRSTNAME}',
                'content' => $this->classicTemplate(),
            ],
        ];
    }

    private function modernTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background: #90bb13; padding: 24px; text-align: center; border-radius: 6px 6px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 22px;">¡Tu carrito te está esperando!</h1>
    </div>

    <div style="background: #fff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px;">

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
        <p>Notamos que dejaste algunos artículos en tu carrito. ¡No te preocupes, los guardamos para ti!</p>

        {FREE_SHIPPING_BADGE}

        <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Artículos en tu carrito</h3>

        {CART_ITEMS_HTML}

        <p style="text-align: right; font-weight: bold; font-size: 16px; margin-top: 8px;">
            Total: {CART_TOTAL}
        </p>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{CART_RECOVER_URL}"
               style="display: inline-block; background: #90bb13; color: #fff; text-decoration: none;
                      padding: 14px 36px; border-radius: 6px; font-size: 16px; font-weight: bold;">
                Completar mi compra
            </a>
        </div>

        <p style="font-size: 12px; color: #999; text-align: center;">
            Este enlace lleva directamente a tu carrito. Si ya completaste tu compra, puedes ignorar este mensaje.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0;">Gracias,<br><strong>{COMPANY_NAME}</strong></p>

        <p style="font-size: 11px; color: #bbb; margin-top: 16px;">
            Este es un correo automático. Por favor no respondas directamente.
        </p>
    </div>
</div>
HTML;
    }

    private function classicTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <h1 style="color: #90bb13; font-size: 22px; margin-bottom: 8px;">Tu carrito te está esperando</h1>

    <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>
    <p>Notamos que dejaste artículos en tu carrito. ¡Puedes completar tu compra en cualquier momento!</p>

    {FREE_SHIPPING_BADGE}

    <h3 style="color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Resumen de tu carrito</h3>

    {CART_ITEMS_HTML}

    <p style="text-align: right; font-weight: bold; font-size: 15px; margin-top: 8px; border-top: 2px solid #e0e0e0; padding-top: 8px;">
        Total: {CART_TOTAL}
    </p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{CART_RECOVER_URL}"
           style="display: inline-block; background: #90bb13; color: #fff; text-decoration: none;
                  padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: bold;">
            Ir a mi carrito
        </a>
    </div>

    <p style="font-size: 12px; color: #999; text-align: center;">
        Si ya completaste tu compra, puedes ignorar este mensaje.
    </p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    <p style="margin: 0;">{COMPANY_NAME}</p>
    <p style="font-size: 11px; color: #bbb; margin-top: 8px;">Este es un correo automático, por favor no respondas directamente.</p>
</div>
HTML;
    }
}
