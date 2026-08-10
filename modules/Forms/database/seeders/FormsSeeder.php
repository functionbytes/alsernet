<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Forms\Models\Form;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * Siembra los 13 formularios iniciales de alsernetforms como filas Form,
 * vinculadas a la TicketCategory correspondiente (sembrada por
 * FormsTicketCategorySeeder). `form_key` DEBE coincidir exactamente con la
 * clave del array en FormCategoryRegistry del lado alsernetforms/PrestaShop.
 *
 * A partir de aquí, añadir/desactivar formularios ya no requiere tocar
 * código ni desplegar: se gestiona desde panel/forms/manage.
 */
class FormsSeeder extends Seeder
{
    public function run(): void
    {
        $forms = [
            ['form_key' => 'contact', 'name' => 'Contacto general', 'category_slug' => 'contacto-general'],
            ['form_key' => 'compromise', 'name' => 'Compromiso de mejor precio', 'category_slug' => 'compromiso-mejor-precio'],
            ['form_key' => 'exchangesandreturns', 'name' => 'Devoluciones y cambios', 'category_slug' => 'devoluciones-cambios'],
            ['form_key' => 'wecallyouus', 'name' => 'Que te llamemos', 'category_slug' => 'que-te-llamemos'],
            ['form_key' => 'internalinformationsystem', 'name' => 'Canal interno de información', 'category_slug' => 'canal-interno-informacion'],
            ['form_key' => 'huntinginsurance', 'name' => 'Seguros de caza', 'category_slug' => 'seguros-caza'],
            ['form_key' => 'fitting', 'name' => 'Cita / Fitting', 'category_slug' => 'cita-fitting'],
            ['form_key' => 'golfdiagnosis', 'name' => 'Diagnóstico de golf', 'category_slug' => 'diagnostico-golf'],
            ['form_key' => 'gunsmithworkshop', 'name' => 'Taller armero', 'category_slug' => 'taller-armero'],
            ['form_key' => 'divingpackages', 'name' => 'Paquetes de buceo', 'category_slug' => 'paquetes-buceo'],
            ['form_key' => 'shipping', 'name' => 'Dudas de envío', 'category_slug' => 'dudas-envio'],
            ['form_key' => 'paymentandfinancing', 'name' => 'Métodos de pago y financiación', 'category_slug' => 'metodos-pago-financiacion'],
            [
                'form_key' => 'workwithus', 'name' => 'Trabaja con nosotros', 'category_slug' => 'trabaja-con-nosotros',
                'active' => false,
                'description' => 'La plantilla workwithus.tpl no existe en disco del lado PrestaShop; el formulario no es alcanzable hasta recrearla.',
            ],
        ];

        foreach ($forms as $form) {
            $category = TicketCategory::where('slug', $form['category_slug'])->first();

            Form::firstOrCreate(
                ['form_key' => $form['form_key']],
                [
                    'name' => $form['name'],
                    'category_id' => $category?->id,
                    'description' => $form['description'] ?? null,
                    'active' => $form['active'] ?? true,
                ]
            );
        }

        $this->command?->info('✅ Forms seeded successfully');
    }
}
