<?php

namespace Modules\Page\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed CMS permissions first
        $this->call(CmsPermissionsSeeder::class);

        // Get first user or create one
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found in database. Skipping page seeding.');

            return;
        }

        // Sample pages
        $pages = [
            [
                'title' => 'Acerca de Nosotros',
                'slug' => 'acerca-de-nosotros',
                'content' => '<h1>Sobre Nuestra Empresa</h1><p>Somos una empresa dedicada a proporcionar soluciones innovadoras para nuestros clientes. Con años de experiencia en el mercado, nos hemos consolidado como líderes en nuestro sector.</p><h2>Nuestra Misión</h2><p>Brindar servicios de calidad que superen las expectativas de nuestros clientes.</p><h2>Nuestra Visión</h2><p>Ser reconocidos como la mejor opción en el mercado.</p>',
                'description' => 'Conoce más sobre nuestra empresa, misión y visión',
                'template' => 'default',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Acerca de Nosotros - Conoce nuestra historia',
                'seo_description' => 'Descubre quiénes somos, nuestra misión, visión y valores. Años de experiencia al servicio de nuestros clientes.',
                'seo_keywords' => 'empresa, acerca de, misión, visión, quiénes somos',
                'published_at' => now()->subDays(30),
            ],
            [
                'title' => 'Servicios',
                'slug' => 'servicios',
                'content' => '<h1>Nuestros Servicios</h1><p>Ofrecemos una amplia gama de servicios profesionales:</p><ul><li><strong>Consultoría Empresarial:</strong> Asesoramiento estratégico para el crecimiento de tu negocio.</li><li><strong>Desarrollo Web:</strong> Creamos sitios web modernos y funcionales.</li><li><strong>Marketing Digital:</strong> Estrategias efectivas para aumentar tu presencia online.</li><li><strong>Soporte Técnico:</strong> Asistencia profesional 24/7.</li></ul><p>Contáctanos para conocer más detalles sobre cada servicio.</p>',
                'description' => 'Explora nuestros servicios profesionales',
                'template' => 'full-width',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Servicios Profesionales - Soluciones para tu Negocio',
                'seo_description' => 'Consultoría, desarrollo web, marketing digital y más. Conoce todos nuestros servicios profesionales.',
                'seo_keywords' => 'servicios, consultoría, desarrollo web, marketing digital',
                'published_at' => now()->subDays(25),
            ],
            [
                'title' => 'Contacto',
                'slug' => 'contacto',
                'content' => '<h1>Contáctanos</h1><p>Estamos aquí para ayudarte. Comunícate con nosotros a través de los siguientes medios:</p><div class="contact-info"><p><strong>Dirección:</strong> Calle Principal #123, Ciudad</p><p><strong>Teléfono:</strong> +1 234 567 8900</p><p><strong>Email:</strong> info@ejemplo.com</p><p><strong>Horario:</strong> Lunes a Viernes, 9:00 AM - 6:00 PM</p></div><p>También puedes completar nuestro formulario de contacto y te responderemos a la brevedad.</p>',
                'description' => 'Ponte en contacto con nuestro equipo',
                'template' => 'contact',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Contacto - Comunícate con Nosotros',
                'seo_description' => 'Encuentra nuestros datos de contacto: teléfono, email, dirección y horarios de atención.',
                'seo_keywords' => 'contacto, teléfono, email, dirección, comunicación',
                'published_at' => now()->subDays(20),
            ],
            [
                'title' => 'Política de Privacidad',
                'slug' => 'politica-de-privacidad',
                'content' => '<h1>Política de Privacidad</h1><p>Última actualización: '.now()->format('d/m/Y').'</p><h2>Recopilación de Información</h2><p>Recopilamos información que usted nos proporciona directamente cuando utiliza nuestros servicios.</p><h2>Uso de la Información</h2><p>Utilizamos la información recopilada para proporcionar, mantener y mejorar nuestros servicios.</p><h2>Protección de Datos</h2><p>Implementamos medidas de seguridad para proteger su información personal.</p><h2>Cookies</h2><p>Utilizamos cookies para mejorar su experiencia en nuestro sitio web.</p>',
                'description' => 'Conoce cómo protegemos tu información personal',
                'template' => 'no-sidebar',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Política de Privacidad - Protección de Datos',
                'seo_description' => 'Lee nuestra política de privacidad y conoce cómo manejamos y protegemos tu información personal.',
                'seo_keywords' => 'privacidad, protección de datos, cookies, seguridad',
                'published_at' => now()->subDays(15),
            ],
            [
                'title' => 'Términos y Condiciones',
                'slug' => 'terminos-y-condiciones',
                'content' => '<h1>Términos y Condiciones</h1><p>Al acceder y usar este sitio web, aceptas estar sujeto a los siguientes términos y condiciones.</p><h2>Uso del Sitio</h2><p>Este sitio web es proporcionado para tu uso personal y no comercial.</p><h2>Propiedad Intelectual</h2><p>Todo el contenido de este sitio está protegido por derechos de autor.</p><h2>Limitación de Responsabilidad</h2><p>No nos hacemos responsables de daños directos, indirectos o consecuentes.</p><h2>Modificaciones</h2><p>Nos reservamos el derecho de modificar estos términos en cualquier momento.</p>',
                'description' => 'Lee nuestros términos y condiciones de uso',
                'template' => 'no-sidebar',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Términos y Condiciones de Uso',
                'seo_description' => 'Conoce los términos y condiciones que rigen el uso de nuestro sitio web y servicios.',
                'seo_keywords' => 'términos, condiciones, uso, legal, derechos',
                'published_at' => now()->subDays(10),
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create($pageData);
            $this->command->info("Created page: {$pageData['title']}");
        }

        $this->command->info('Page seeding completed successfully!');
    }
}
