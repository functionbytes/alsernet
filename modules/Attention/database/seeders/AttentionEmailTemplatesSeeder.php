<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Seeds email templates for the Attention (peticiones) module.
 * Creates 5 templates: confirmation, assigned, in_process, resolution, custom.
 */
class AttentionEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('No languages found - skipping attention template translations');

            return;
        }

        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => 'attention',
                    'is_enabled' => true,
                    'is_protected' => $template['is_protected'] ?? false,
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

            $this->command->info("Template: {$template['name']} (ID: {$mailerTemplate->id}) - {$langs->count()} translation(s)");
        }

        $this->command->info('Attention email templates seeded ('.count($templates).' templates)');
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, subject: string, content: string, is_protected?: bool}>
     */
    private function getTemplates(): array
    {
        return [
            [
                'key' => 'attention_confirmation',
                'name' => 'Confirmacion de recepcion',
                'description' => 'Email enviado al ciudadano cuando se radica un nuevo peticiones. Confirma la recepcion y proporciona el numero de radicado para seguimiento.',
                'subject' => 'Confirmacion de radicado {ATTENTION_RADICADO}',
                'is_protected' => true,
                'content' => $this->confirmationTemplate(),
            ],
            [
                'key' => 'attention_assigned',
                'name' => 'Notificacion de asignacion',
                'description' => 'Email enviado al funcionario cuando se le asigna un nuevo peticiones para gestion.',
                'subject' => 'Nuevo peticiones asignado - Radicado {ATTENTION_RADICADO}',
                'is_protected' => true,
                'content' => $this->assignedTemplate(),
            ],
            [
                'key' => 'attention_in_process',
                'name' => 'Notificacion en tramite',
                'description' => 'Email enviado al ciudadano cuando su peticiones pasa a estado "En proceso". Informa que la solicitud esta siendo atendida.',
                'subject' => 'Su peticiones esta en proceso - Radicado {ATTENTION_RADICADO}',
                'is_protected' => true,
                'content' => $this->inProcessTemplate(),
            ],
            [
                'key' => 'attention_resolution',
                'name' => 'Notificacion de resolucion',
                'description' => 'Email enviado al ciudadano cuando su peticiones ha sido resuelto. Incluye la respuesta oficial y enlace a encuesta de satisfaccion.',
                'subject' => 'Respuesta a su solicitud {ATTENTION_RADICADO}',
                'is_protected' => true,
                'content' => $this->resolutionTemplate(),
            ],
            [
                'key' => 'attention_custom',
                'name' => 'Correo personalizado',
                'description' => 'Plantilla base para correos personalizados enviados manualmente por funcionarios a los ciudadanos.',
                'subject' => '{EMAIL_SUBJECT}',
                'content' => $this->customTemplate(),
            ],
        ];
    }

    private function confirmationTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #90bb13; font-size: 24px; margin-bottom: 10px;">peticiones recibido exitosamente</h1>

    <p>Estimado/a <strong>{CUSTOMER_NAME}</strong>,</p>

    <p>Hemos recibido su solicitud y se ha generado el siguiente numero de radicado para seguimiento:</p>

    <div style="background: #f0f5e3; border-left: 4px solid #90bb13; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0; font-size: 18px; font-weight: bold; color: #333;">Radicado: {ATTENTION_RADICADO}</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalles de su solicitud</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Tipo:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_TYPE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Asunto:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha de radicacion:</td>
            <td style="padding: 8px 0; font-weight: bold;">{CREATED_DATE}</td>
        </tr>
    </table>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Como hacer seguimiento</h2>

    <p>Puede consultar el estado de su peticiones en cualquier momento utilizando el numero de radicado:</p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{TRACKING_URL}" style="display: inline-block; background: #90bb13; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Consultar estado</a>
    </div>

    <p style="color: #777; font-size: 14px;">Tambien puede hacer seguimiento enviando el numero de radicado por WhatsApp o llamando a nuestras lineas de atencion.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <h3 style="font-size: 16px; color: #555;">Tiempos de respuesta</h3>

    <p style="font-size: 14px; color: #555;">Le informamos que su solicitud sera atendida en los siguientes tiempos maximos segun la normativa vigente:</p>

    <ul style="font-size: 14px; color: #555; padding-left: 20px;">
        <li><strong>Peticion:</strong> 15 dias habiles</li>
        <li><strong>Queja/Reclamo:</strong> 15 dias habiles</li>
        <li><strong>Sugerencia:</strong> 15 dias habiles</li>
        <li><strong>Felicitacion:</strong> Acuse de recibo inmediato</li>
    </ul>

    <p style="font-size: 14px; color: #555;">Recibira notificaciones por correo electronico sobre los cambios en el estado de su peticiones.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="font-size: 12px; color: #999;"><strong>Nota:</strong> Este es un correo automatico, por favor no responda a este mensaje. Si tiene alguna consulta, utilice el numero de radicado para hacer seguimiento a traves de nuestros canales oficiales.</p>

    <p style="margin-top: 24px;">Gracias por su comunicacion,<br><strong>{COMPANY_NAME}</strong></p>
</div>
HTML;
    }

    private function assignedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #90bb13; font-size: 24px; margin-bottom: 10px;">Nuevo peticiones asignado</h1>

    <p>Se le ha asignado un nuevo caso de atencion al ciudadano para su gestion.</p>

    <div style="background: #f0f5e3; border-left: 4px solid #90bb13; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0 0 4px 0; font-size: 18px; font-weight: bold; color: #333;">Radicado: {ATTENTION_RADICADO}</p>
        <p style="margin: 0; font-size: 14px; color: #555;">Tipo: {ATTENTION_TYPE}</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Informacion del caso</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Tipo:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_TYPE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Categoria:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_CATEGORY}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Ciudadano:</td>
            <td style="padding: 8px 0; font-weight: bold;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha de radicacion:</td>
            <td style="padding: 8px 0; font-weight: bold;">{CREATED_DATE}</td>
        </tr>
    </table>

    <h3 style="font-size: 16px; color: #555;">Asunto</h3>
    <p>{ATTENTION_SUBJECT}</p>

    <h3 style="font-size: 16px; color: #555;">Descripcion</h3>
    <p style="color: #555;">{ATTENTION_DESCRIPTION}</p>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Acciones requeridas</h2>

    <p>Por favor revise el caso y proceda con la gestion correspondiente. Es importante mantener actualizado el estado del peticiones para que el ciudadano pueda hacer seguimiento.</p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{ATTENTION_URL}" style="display: inline-block; background: #13C672; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Gestionar peticiones</a>
    </div>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <h3 style="font-size: 16px; color: #555;">Recordatorio de tiempos de respuesta</h3>
    <ul style="font-size: 14px; color: #555; padding-left: 20px;">
        <li><strong>Peticion:</strong> 15 dias habiles</li>
        <li><strong>Queja/Reclamo:</strong> 15 dias habiles</li>
        <li><strong>Sugerencia:</strong> 15 dias habiles</li>
    </ul>
    <p style="font-size: 14px; color: #555;">Asegurese de dar respuesta dentro de los terminos legales establecidos.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p>Saludos,<br><strong>Sistema de Gestion peticiones</strong><br>{COMPANY_NAME}</p>
</div>
HTML;
    }

    private function inProcessTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #90bb13; font-size: 24px; margin-bottom: 10px;">Su peticiones esta en proceso</h1>

    <p>Estimado/a <strong>{CUSTOMER_NAME}</strong>,</p>

    <p>Le informamos que su {ATTENTION_TYPE} con radicado <strong>{ATTENTION_RADICADO}</strong> ha sido asignado y se encuentra en proceso de atencion.</p>

    <div style="background: #fff3cd; border-left: 4px solid #FEC90F; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0 0 4px 0; font-size: 18px; font-weight: bold; color: #333;">Radicado: {ATTENTION_RADICADO}</p>
        <p style="margin: 0; font-size: 14px; color: #555;">Estado: En proceso</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalles de su solicitud</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Tipo:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_TYPE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Asunto:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha de radicacion:</td>
            <td style="padding: 8px 0; font-weight: bold;">{CREATED_DATE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Departamento encargado:</td>
            <td style="padding: 8px 0; font-weight: bold;">{DEPARTMENT_NAME}</td>
        </tr>
    </table>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Que significa esto</h2>

    <p>Su solicitud ha sido asignada a nuestro equipo de <strong>{DEPARTMENT_NAME}</strong> y esta siendo revisada. Pronto recibira una respuesta.</p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{TRACKING_URL}" style="display: inline-block; background: #90bb13; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Ver estado actual</a>
    </div>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <h3 style="font-size: 16px; color: #555;">Seguimiento</h3>
    <p style="font-size: 14px; color: #555;">Puede consultar el estado de su peticiones en cualquier momento usando el numero de radicado. Le notificaremos cuando haya novedades o cuando su caso sea resuelto.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="font-size: 12px; color: #999;"><strong>Nota:</strong> Este es un correo automatico. Por favor no responda a este mensaje.</p>

    <p style="margin-top: 24px;">Atentamente,<br><strong>{COMPANY_NAME}</strong></p>
</div>
HTML;
    }

    private function resolutionTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #13C672; font-size: 24px; margin-bottom: 10px;">Su peticiones ha sido resuelto</h1>

    <p>Estimado/a <strong>{CUSTOMER_NAME}</strong>,</p>

    <p>Nos complace informarle que su {ATTENTION_TYPE} con radicado <strong>{ATTENTION_RADICADO}</strong> ha sido resuelto.</p>

    <div style="background: #d4edda; border-left: 4px solid #13C672; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0 0 4px 0; font-size: 18px; font-weight: bold; color: #333;">Radicado: {ATTENTION_RADICADO}</p>
        <p style="margin: 0; font-size: 14px; color: #555;">Estado: Resuelto</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalles de su solicitud</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Tipo:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_TYPE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Asunto:</td>
            <td style="padding: 8px 0; font-weight: bold;">{ATTENTION_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha de resolucion:</td>
            <td style="padding: 8px 0; font-weight: bold;">{RESOLUTION_DATE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Medio de respuesta:</td>
            <td style="padding: 8px 0; font-weight: bold;">{RESPONSE_TYPE}</td>
        </tr>
    </table>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Respuesta oficial</h2>

    <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 16px 20px; margin: 16px 0; border-radius: 4px;">
        <p style="margin: 0; color: #333; line-height: 1.6;">{RESOLUTION}</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Su opinion es importante</h2>

    <p>Nos gustaria conocer su nivel de satisfaccion con la atencion recibida. Por favor, dedique un momento a calificar nuestro servicio:</p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{TRACKING_URL}" style="display: inline-block; background: #13C672; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Calificar servicio</a>
    </div>

    <p style="font-size: 14px; color: #555;">Su retroalimentacion nos ayuda a mejorar continuamente nuestros procesos de atencion al ciudadano.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <h3 style="font-size: 16px; color: #555;">Informacion adicional</h3>

    <p style="font-size: 14px; color: #555;">Si considera que la respuesta no resuelve completamente su solicitud, puede:</p>
    <ul style="font-size: 14px; color: #555; padding-left: 20px;">
        <li>Presentar un recurso de reposicion dentro de los 10 dias habiles siguientes</li>
        <li>Solicitar aclaraciones adicionales</li>
        <li>Radicar una nueva solicitud relacionada</li>
    </ul>

    <p style="font-size: 14px; color: #555;">Para cualquier gestion adicional, puede contactarnos a traves de nuestros canales oficiales, siempre mencionando su numero de radicado.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p>Agradecemos su comunicacion y esperamos haber atendido satisfactoriamente su solicitud.</p>

    <p style="margin-top: 24px;">Atentamente,<br><strong>{COMPANY_NAME}</strong></p>
</div>
HTML;
    }

    private function customTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <p>Estimado/a <strong>{CUSTOMER_NAME}</strong>,</p>

    <p>En relacion con su solicitud con radicado <strong>{ATTENTION_RADICADO}</strong>, le informamos:</p>

    <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0; color: #333; line-height: 1.6;">{EMAIL_BODY}</p>
    </div>

    <p style="font-size: 14px; color: #555;">Si tiene alguna pregunta, puede consultar el estado de su solicitud en cualquier momento:</p>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{TRACKING_URL}" style="display: inline-block; background: #90bb13; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Consultar estado</a>
    </div>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="margin-top: 24px;">Atentamente,<br><strong>{COMPANY_NAME}</strong></p>
</div>
HTML;
    }
}
