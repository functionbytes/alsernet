<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\LegalPage;

class EcommerceLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title' => 'Términos y condiciones',
                'meta_title' => 'Términos y condiciones de uso',
                'meta_description' => 'Lee nuestros términos y condiciones de uso de la tienda en línea.',
                'content' => $this->termsContent(),
            ],
            [
                'slug' => 'privacy',
                'title' => 'Política de privacidad',
                'meta_title' => 'Política de privacidad y tratamiento de datos',
                'meta_description' => 'Conoce cómo recopilamos, usamos y protegemos tus datos personales.',
                'content' => $this->privacyContent(),
            ],
            [
                'slug' => 'contact',
                'title' => 'Contacto',
                'meta_title' => 'Contáctanos',
                'meta_description' => 'Información de contacto y atención al cliente.',
                'content' => $this->contactContent(),
            ],
            [
                'slug' => 'shipping-policy',
                'title' => 'Política de envíos',
                'meta_title' => 'Política de envíos y entregas',
                'meta_description' => 'Conoce nuestros tiempos, costos y zonas de envío.',
                'content' => $this->shippingContent(),
            ],
            [
                'slug' => 'returns-policy',
                'title' => 'Política de devoluciones',
                'meta_title' => 'Política de devoluciones y reembolsos',
                'meta_description' => 'Cómo solicitar una devolución y procesar reembolsos.',
                'content' => $this->returnsContent(),
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::query()->updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, ['is_published' => true])
            );
        }
    }

    private function termsContent(): string
    {
        return <<<'HTML'
<h2>1. Aceptación de los términos</h2>
<p>Al acceder y utilizar este sitio web, aceptas estar sujeto a los siguientes términos y condiciones de uso. Si no estás de acuerdo con alguna parte de estos términos, no debes utilizar nuestro servicio.</p>

<h2>2. Productos y precios</h2>
<p>Todos los productos están sujetos a disponibilidad. Nos reservamos el derecho de modificar los precios sin previo aviso. Los precios incluyen impuestos cuando corresponda.</p>

<h2>3. Pedidos y pagos</h2>
<p>Al realizar un pedido aceptas pagar el precio total indicado, incluidos los gastos de envío e impuestos. Aceptamos los métodos de pago disponibles en el momento del checkout.</p>

<h2>4. Propiedad intelectual</h2>
<p>Todo el contenido de este sitio (textos, imágenes, logos) es propiedad de la tienda o de sus respectivos titulares y está protegido por leyes de propiedad intelectual.</p>

<h2>5. Limitación de responsabilidad</h2>
<p>No nos hacemos responsables de daños indirectos derivados del uso de productos. La responsabilidad máxima se limita al valor del producto adquirido.</p>

<h2>6. Modificaciones</h2>
<p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Las modificaciones serán efectivas desde su publicación en el sitio.</p>

<h2>7. Ley aplicable</h2>
<p>Estos términos se rigen por la legislación vigente en el país de operación de la tienda.</p>
HTML;
    }

    private function privacyContent(): string
    {
        return <<<'HTML'
<h2>1. Información que recopilamos</h2>
<p>Recopilamos información que nos proporcionas directamente al registrarte, realizar una compra o contactarnos: nombre, email, teléfono, dirección de envío y datos de pago.</p>

<h2>2. Uso de la información</h2>
<p>Utilizamos tus datos para procesar pedidos, gestionar tu cuenta, enviarte comunicaciones relacionadas con tus compras y, si lo autorizas, enviarte ofertas y novedades.</p>

<h2>3. Cookies</h2>
<p>Usamos cookies propias y de terceros para mejorar tu experiencia, recordar tus preferencias y analizar el tráfico del sitio. Puedes gestionar las cookies desde la configuración de tu navegador.</p>

<h2>4. Compartir información</h2>
<p>No vendemos tus datos personales. Compartimos información con terceros solo cuando es necesario: pasarelas de pago, servicios de envío, o por requerimiento legal.</p>

<h2>5. Seguridad</h2>
<p>Implementamos medidas técnicas y organizativas para proteger tus datos. Sin embargo, ningún sistema es 100% seguro y no podemos garantizar seguridad absoluta.</p>

<h2>6. Tus derechos</h2>
<p>Tienes derecho a acceder, rectificar, eliminar y oponerte al tratamiento de tus datos personales. Para ejercer estos derechos, contáctanos.</p>

<h2>7. Retención</h2>
<p>Conservamos tus datos durante el tiempo necesario para cumplir con las finalidades para las que fueron recopilados y para cumplir obligaciones legales.</p>
HTML;
    }

    private function contactContent(): string
    {
        return <<<'HTML'
<h2>Atención al cliente</h2>
<p>Estamos disponibles para resolver tus dudas y atender tus solicitudes:</p>

<ul>
    <li><strong>Email:</strong> contacto@ejemplo.com</li>
    <li><strong>Teléfono:</strong> +57 300 000 0000</li>
    <li><strong>Horario:</strong> Lunes a Viernes de 9:00 a 18:00</li>
</ul>

<h2>Dirección</h2>
<p>Calle Principal 123<br>Ciudad, País<br>Código postal 11001</p>

<h2>Redes sociales</h2>
<p>Síguenos en nuestras redes sociales para mantenerte al día con novedades y ofertas exclusivas.</p>
HTML;
    }

    private function shippingContent(): string
    {
        return <<<'HTML'
<h2>Tiempos de entrega</h2>
<p>Procesamos los pedidos en un plazo de 1 a 2 días hábiles. Una vez despachado, los tiempos de entrega son:</p>

<ul>
    <li><strong>Capital y área metropolitana:</strong> 2 a 3 días hábiles</li>
    <li><strong>Ciudades principales:</strong> 3 a 5 días hábiles</li>
    <li><strong>Resto del país:</strong> 5 a 8 días hábiles</li>
</ul>

<h2>Costos de envío</h2>
<p>El costo se calcula automáticamente al finalizar la compra según el destino, peso y dimensiones del paquete.</p>

<h2>Seguimiento</h2>
<p>Una vez despachado tu pedido, recibirás un correo con el código de seguimiento para rastrearlo en línea.</p>

<h2>Zonas no cubiertas</h2>
<p>En caso de no poder entregar en tu zona, te contactaremos para coordinar una alternativa o procesar el reembolso.</p>
HTML;
    }

    private function returnsContent(): string
    {
        return <<<'HTML'
<h2>Plazo de devolución</h2>
<p>Tienes hasta 30 días desde la recepción del producto para solicitar una devolución, siempre que el producto esté en su estado original sin uso ni daños.</p>

<h2>Cómo solicitar una devolución</h2>
<ol>
    <li>Ingresa a tu cuenta y abre el detalle de la orden</li>
    <li>Solicita la devolución indicando el motivo</li>
    <li>Recibirás instrucciones por correo electrónico</li>
    <li>Empaca el producto en su empaque original</li>
    <li>Despacha o coordina la recolección según se te indique</li>
</ol>

<h2>Productos no retornables</h2>
<p>Por motivos de higiene o seguridad, no se aceptan devoluciones de productos perecederos, ropa interior usada, productos personalizados o de uso íntimo.</p>

<h2>Reembolsos</h2>
<p>Una vez recibido y verificado el producto devuelto, procesamos el reembolso por el mismo medio de pago en un plazo de 5 a 10 días hábiles.</p>

<h2>Productos con defectos</h2>
<p>Si tu producto llega defectuoso o dañado, contáctanos dentro de las 48 horas siguientes a la recepción y coordinaremos el reemplazo o reembolso sin costo adicional.</p>
HTML;
    }
}
