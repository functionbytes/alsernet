<?php

namespace Modules\Questions\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Correos de las consultas de producto, en el catálogo del módulo Mailer para
 * que el texto sea editable desde el admin sin tocar código.
 *
 * Son tres, y cubren la promesa que hace el formulario de la ficha ("te
 * responderemos por correo"), que hasta ahora no cumplía nadie: acuse al
 * cliente, aviso al equipo y, cuando se publica, la respuesta al cliente.
 *
 * Usan QuestionsMailerLayoutSeeder::ALIAS (correr ese seeder antes) — el
 * contenido de cada plantilla es solo el fragmento interior, en español y con
 * la paleta de marca (verdes y grises, nunca rojo).
 */
class QuestionsEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command?->warn('Sin idiomas disponibles: no se siembran las plantillas de consultas.');

            return;
        }

        $layoutId = MailerLayout::where('alias', QuestionsMailerLayoutSeeder::ALIAS)->value('id');

        foreach ($this->templates() as $tpl) {
            $template = MailerTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $tpl['name'],
                    'description' => $tpl['description'],
                    'module' => 'questions',
                    'layout_id' => $layoutId,
                    'is_enabled' => true,
                    'is_protected' => true,
                    'variables' => $tpl['variables'],
                ]
            );

            foreach ($langs as $lang) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $template->id, 'lang_id' => $lang->id],
                    ['subject' => $tpl['subject'], 'content' => $tpl['content']]
                );
            }

            $this->command?->info("Plantilla: {$template->name} (ID: {$template->id})");
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'questions.received',
                'name' => 'Consulta recibida (cliente)',
                'description' => 'Acuse de recibo al cliente justo después de enviar una consulta desde la ficha del producto.',
                'subject' => 'Hemos recibido tu consulta sobre {PRODUCT_NAME}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'PRODUCT_NAME', 'required' => true, 'description' => 'Producto sobre el que pregunta'],
                    ['name' => 'QUESTION', 'required' => true, 'description' => 'Texto de la consulta'],
                    ['name' => 'SUBMITTED_AT', 'required' => false, 'description' => 'Fecha y hora de envío'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa (pie del correo)'],
                ],
                'content' => $this->headerCard('Hemos recibido tu consulta').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, gracias por escribirnos. Un compañero revisará tu consulta sobre <strong>{PRODUCT_NAME}</strong> y te responderá a este mismo correo.</p>
    <div style="background: #f6f7f1; border-left: 3px solid #90bb13; padding: 12px 16px; margin: 0 0 16px; border-radius: 0 6px 6px 0; color: #555;">
        {QUESTION}
    </div>
    <p style="color: #666; font-size: 13px; margin: 0;">Recibida el {SUBMITTED_AT}. Si la respuesta puede servirle a otros clientes, la publicaremos también en la ficha del producto.</p>
</div>
HTML,
            ],
            [
                'key' => 'questions.answered',
                'name' => 'Respuesta a la consulta (cliente)',
                'description' => 'Se envía al cliente cuando su consulta se responde y se publica en la ficha del producto.',
                'subject' => 'Tu consulta sobre {PRODUCT_NAME}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'PRODUCT_NAME', 'required' => true, 'description' => 'Producto sobre el que preguntó'],
                    ['name' => 'PRODUCT_URL', 'required' => false, 'description' => 'Enlace a la ficha del producto'],
                    ['name' => 'QUESTION', 'required' => true, 'description' => 'Consulta original'],
                    ['name' => 'ANSWER', 'required' => true, 'description' => 'Respuesta publicada'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa (pie del correo)'],
                ],
                'content' => $this->headerCard('Ya tienes respuesta').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, esto es lo que nos preguntaste sobre <strong>{PRODUCT_NAME}</strong>:</p>
    <div style="background: #f7f7f7; border-left: 3px solid #cccccc; padding: 12px 16px; margin: 0 0 16px; border-radius: 0 6px 6px 0; color: #666; font-size: 14px;">
        {QUESTION}
    </div>
    <p style="margin: 0 0 8px; font-weight: bold; color: #4f6b0a;">Nuestra respuesta</p>
    <div style="background: #f6f7f1; border-left: 3px solid #90bb13; padding: 12px 16px; margin: 0 0 20px; border-radius: 0 6px 6px 0;">
        {ANSWER}
    </div>
    <p style="margin: 0 0 16px;"><a href="{PRODUCT_URL}" style="display: inline-block; background: #90bb13; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 6px; font-weight: bold;">Ver el producto</a></p>
    <p style="color: #666; font-size: 13px; margin: 0;">Si te queda alguna duda, responde a este correo y seguimos.</p>
</div>
HTML,
            ],
            [
                'key' => 'questions.new_for_team',
                'name' => 'Consulta nueva (equipo)',
                'description' => 'Aviso interno al buzón del equipo cuando entra una consulta pendiente de responder.',
                'subject' => 'Consulta nueva sobre {PRODUCT_NAME}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'CUSTOMER_EMAIL', 'required' => false, 'description' => 'Correo del cliente'],
                    ['name' => 'PRODUCT_NAME', 'required' => true, 'description' => 'Producto sobre el que pregunta'],
                    ['name' => 'PRODUCT_REFERENCE', 'required' => false, 'description' => 'Referencia del producto'],
                    ['name' => 'QUESTION', 'required' => true, 'description' => 'Texto de la consulta'],
                    ['name' => 'PANEL_URL', 'required' => false, 'description' => 'Enlace a la consulta en el panel'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa (pie del correo)'],
                ],
                'content' => $this->headerCard('Consulta pendiente de responder').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Producto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{PRODUCT_NAME} <span style="color: #999;">{PRODUCT_REFERENCE}</span></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0;">{CUSTOMER_NAME} &lt;{CUSTOMER_EMAIL}&gt;</td>
        </tr>
    </table>
    <div style="background: #f6f7f1; border-left: 3px solid #90bb13; padding: 12px 16px; margin: 0 0 20px; border-radius: 0 6px 6px 0;">
        {QUESTION}
    </div>
    <p style="margin: 0;"><a href="{PANEL_URL}" style="display: inline-block; background: #90bb13; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 6px; font-weight: bold;">Responder en el panel</a></p>
</div>
HTML,
            ],
        ];
    }

    private function headerCard(string $title): string
    {
        return <<<HTML
<div style="background: #90bb13; color: #ffffff; padding: 20px 24px;">
    <h2 style="margin: 0; font-size: 18px;">{$title}</h2>
</div>
HTML;
    }
}
