<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * Siembra una TicketCategory por cada formulario "reporte" de
 * FormCategoryRegistry (módulo alsernetforms, PrestaShop). El `slug` de
 * cada fila DEBE coincidir exactamente con `category_slug` del lado
 * PrestaShop -- es la clave que usa FormSubmissionReceiverController para
 * resolver a qué categoría pertenece cada envío entrante.
 *
 * Idempotente vía 'slug' (columna única), igual que HelpdeskTicketCategorySeeder.
 */
class FormsTicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Contacto general',
                'slug' => 'contacto-general',
                'description' => 'Formulario de contacto general del sitio',
                'color' => '#6c757d',
                'icon' => 'fa-duotone fa-envelope',
                'active' => true,
                'order' => 9,
            ],
            [
                'name' => 'Compromiso de mejor precio',
                'slug' => 'compromiso-mejor-precio',
                'description' => 'Solicitud de igualación de precio',
                'color' => '#198754',
                'icon' => 'fa-duotone fa-tags',
                'active' => true,
                'order' => 10,
            ],
            [
                'name' => 'Devoluciones y cambios',
                'slug' => 'devoluciones-cambios',
                'description' => 'Solicitud de cambio o devolución de producto',
                'color' => '#dc3545',
                'icon' => 'fa-duotone fa-rotate-left',
                'active' => true,
                'order' => 11,
            ],
            [
                'name' => 'Que te llamemos',
                'slug' => 'que-te-llamemos',
                'description' => 'Solicitud de llamada para consultar sobre un producto',
                'color' => '#0dcaf0',
                'icon' => 'fa-duotone fa-phone',
                'active' => true,
                'order' => 12,
            ],
            [
                'name' => 'Canal interno de información',
                'slug' => 'canal-interno-informacion',
                'description' => 'Canal interno de información / whistleblowing',
                'color' => '#212529',
                'icon' => 'fa-duotone fa-shield-halved',
                'active' => true,
                'order' => 13,
            ],
            [
                'name' => 'Seguros de caza',
                'slug' => 'seguros-caza',
                'description' => 'Solicitud/contratación de seguro de caza',
                'color' => '#6f4e37',
                'icon' => 'fa-duotone fa-paw',
                'active' => true,
                'order' => 14,
            ],
            [
                'name' => 'Cita / Fitting',
                'slug' => 'cita-fitting',
                'description' => 'Solicitud de cita de fitting',
                'color' => '#0d6efd',
                'icon' => 'fa-duotone fa-calendar-check',
                'active' => true,
                'order' => 15,
            ],
            [
                'name' => 'Diagnóstico de golf',
                'slug' => 'diagnostico-golf',
                'description' => 'Solicitud de diagnóstico de material de golf',
                'color' => '#198754',
                'icon' => 'fa-duotone fa-golf-club',
                'active' => true,
                'order' => 16,
            ],
            [
                'name' => 'Taller armero',
                'slug' => 'taller-armero',
                'description' => 'Consulta sobre el taller armero',
                'color' => '#6c757d',
                'icon' => 'fa-duotone fa-screwdriver-wrench',
                'active' => true,
                'order' => 17,
            ],
            [
                'name' => 'Paquetes de buceo',
                'slug' => 'paquetes-buceo',
                'description' => 'Consulta sobre paquetes de buceo',
                'color' => '#0dcaf0',
                'icon' => 'fa-duotone fa-water',
                'active' => true,
                'order' => 18,
            ],
            [
                'name' => 'Dudas de envío',
                'slug' => 'dudas-envio',
                'description' => 'Consulta sobre el envío de un pedido',
                'color' => '#ffc107',
                'icon' => 'fa-duotone fa-truck',
                'active' => true,
                'order' => 19,
            ],
            [
                'name' => 'Métodos de pago y financiación',
                'slug' => 'metodos-pago-financiacion',
                'description' => 'Consulta sobre métodos de pago o financiación',
                'color' => '#198754',
                'icon' => 'fa-duotone fa-credit-card',
                'active' => true,
                'order' => 20,
            ],
            [
                'name' => 'Trabaja con nosotros',
                'slug' => 'trabaja-con-nosotros',
                'description' => 'Candidaturas espontáneas (formulario pendiente de recrear en PrestaShop)',
                'color' => '#6c757d',
                'icon' => 'fa-duotone fa-briefcase',
                'active' => true,
                'order' => 21,
            ],
            // Categoría C del plan de migración ets_contactform7 -> alsernetforms
            // (formularios sin equivalente previo, ver secondhand.tpl et al.).
            [
                'name' => 'Segunda mano',
                'slug' => 'segunda-mano',
                'description' => 'Venta de armas usadas de segunda mano',
                'color' => '#8b5a2b',
                'icon' => 'fa-duotone fa-gun',
                'active' => true,
                'order' => 22,
            ],
            [
                'name' => 'Defensor del cliente',
                'slug' => 'defensor-del-cliente',
                'description' => 'Reclamaciones e incidencias escaladas al defensor del cliente',
                'color' => '#dc3545',
                'icon' => 'fa-duotone fa-scale-balanced',
                'active' => true,
                'order' => 23,
            ],
            [
                'name' => 'Condiciones Sillas Prestige',
                'slug' => 'condiciones-sillas-prestige',
                'description' => 'Solicitud de medición/reserva de sillas de montar Prestige a medida',
                'color' => '#6f4e37',
                'icon' => 'fa-duotone fa-horse',
                'active' => true,
                'order' => 24,
            ],
            [
                'name' => 'Consulta de precio',
                'slug' => 'consulta-precio-producto',
                'description' => 'Consulta de precio realizada desde la ficha de producto',
                'color' => '#0d6efd',
                'icon' => 'fa-duotone fa-tag',
                'active' => true,
                'order' => 25,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command?->info('✅ Forms ticket categories seeded successfully');
    }
}
