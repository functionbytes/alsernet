<?php

namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Template\Models\Shortcode;

class ShortcodeSeeder extends Seeder
{
    public function run(): void
    {
        $shortcodes = [
            [
                'key' => 'raw-html',
                'name' => 'HTML personalizado',
                'description' => 'Insertar HTML libre',
                'icon' => 'fas fa-code',
                'config_fields' => [
                    ['id' => 'bc_html', 'label' => 'Contenido HTML', 'type' => 'textarea', 'placeholder' => '<div>Tu HTML aquí</div>', 'rows' => 6],
                ],
                'shortcode_template' => '{bc_html}',
                'render_template' => null,
                'sort_order' => 0,
            ],
            [
                'key' => 'alert',
                'name' => 'Alerta / Aviso',
                'description' => 'Caja de alerta con mensaje',
                'icon' => 'fas fa-exclamation-triangle',
                'config_fields' => [
                    ['id' => 'bc_type', 'label' => 'Tipo', 'type' => 'select', 'options' => ['info' => 'Información', 'success' => 'Éxito', 'warning' => 'Advertencia', 'danger' => 'Peligro']],
                    ['id' => 'bc_message', 'label' => 'Mensaje', 'type' => 'text', 'placeholder' => 'Texto del aviso'],
                ],
                'shortcode_template' => '[alert bc_type="{bc_type}" bc_message="{bc_message}"][/alert]',
                'render_template' => '<div class="alert alert-{bc_type}" role="alert">{bc_message}</div>',
                'sort_order' => 1,
            ],
            [
                'key' => 'columns',
                'name' => 'Columnas',
                'description' => 'Dividir en columnas Bootstrap',
                'icon' => 'fas fa-columns',
                'config_fields' => [
                    ['id' => 'bc_cols', 'label' => 'Número de columnas', 'type' => 'select', 'options' => ['2' => '2 columnas', '3' => '3 columnas', '4' => '4 columnas']],
                ],
                'shortcode_template' => '[columns bc_cols="{bc_cols}"][/columns]',
                'render_template' => '<div class="row row-cols-1 row-cols-md-{bc_cols} g-3">{content}</div>',
                'sort_order' => 2,
            ],
            [
                'key' => 'button',
                'name' => 'Botón',
                'description' => 'Botón con enlace y estilo',
                'icon' => 'fas fa-hand-pointer',
                'config_fields' => [
                    ['id' => 'bc_text', 'label' => 'Texto del botón', 'type' => 'text', 'placeholder' => 'Haz clic aquí'],
                    ['id' => 'bc_url', 'label' => 'Enlace (URL)', 'type' => 'url', 'placeholder' => 'https://'],
                    ['id' => 'bc_style', 'label' => 'Estilo', 'type' => 'select', 'options' => ['primary' => 'Primary', 'secondary' => 'Secondary', 'success' => 'Success', 'danger' => 'Danger']],
                ],
                'shortcode_template' => '[button bc_url="{bc_url}" bc_style="{bc_style}"]{bc_text}[/button]',
                'render_template' => '<a href="{bc_url}" class="btn btn-{bc_style}">{content}</a>',
                'sort_order' => 3,
            ],
            [
                'key' => 'image-gallery',
                'name' => 'Galería de imágenes',
                'description' => 'Grilla de imágenes',
                'icon' => 'fas fa-images',
                'config_fields' => [
                    ['id' => 'bc_cols', 'label' => 'Columnas', 'type' => 'select', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
                ],
                'shortcode_template' => '[image-gallery bc_cols="{bc_cols}"][/image-gallery]',
                'render_template' => '<div class="row row-cols-1 row-cols-md-{bc_cols} g-3">{content}</div>',
                'sort_order' => 4,
            ],
            [
                'key' => 'video',
                'name' => 'Video',
                'description' => 'Insertar video (YouTube, Vimeo)',
                'icon' => 'fas fa-video',
                'config_fields' => [
                    ['id' => 'bc_video', 'label' => 'URL del video (YouTube/Vimeo)', 'type' => 'url', 'placeholder' => 'https://www.youtube.com/watch?v=...'],
                ],
                'shortcode_template' => '[video bc_video="{bc_video}"][/video]',
                'render_template' => '<div class="ratio ratio-16x9"><iframe src="{bc_video}" frameborder="0" allowfullscreen></iframe></div>',
                'sort_order' => 5,
            ],
            [
                'key' => 'contact-form',
                'name' => 'Formulario de contacto',
                'description' => 'Formulario con campos básicos',
                'icon' => 'fas fa-envelope',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título del formulario', 'type' => 'text', 'placeholder' => 'Contáctanos'],
                    ['id' => 'bc_email', 'label' => 'Correo destino', 'type' => 'text', 'placeholder' => 'correo@ejemplo.com'],
                ],
                'shortcode_template' => '[contact-form bc_title="{bc_title}" bc_email="{bc_email}"][/contact-form]',
                'render_template' => '<div class="contact-form-wrapper my-4"><h3 class="mb-3">{bc_title}</h3><form class="contact-shortcode-form" data-email="{bc_email}"><div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control" required placeholder="Tu nombre"></div><div class="mb-3"><label class="form-label">Correo electrónico</label><input type="email" name="email" class="form-control" required placeholder="tu@email.com"></div><div class="mb-3"><label class="form-label">Mensaje</label><textarea name="message" class="form-control" rows="4" required></textarea></div><button type="submit" class="btn btn-primary">Enviar mensaje</button></form></div>',
                'sort_order' => 6,
            ],
            [
                'key' => 'faq',
                'name' => 'Preguntas frecuentes',
                'description' => 'Lista de preguntas y respuestas',
                'icon' => 'fas fa-question-circle',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título de la sección', 'type' => 'text', 'placeholder' => 'Preguntas frecuentes'],
                ],
                'shortcode_template' => '[faq bc_title="{bc_title}"][faq-item question="Pregunta 1" answer="Respuesta 1"][/faq]',
                'render_template' => '<div class="accordion" id="faq-block"><h4 class="mb-3">{bc_title}</h4>{content}</div>',
                'sort_order' => 7,
            ],
            [
                'key' => 'testimonials',
                'name' => 'Testimonios',
                'description' => 'Carrusel de testimonios',
                'icon' => 'fas fa-quote-left',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título', 'type' => 'text', 'placeholder' => 'Lo que dicen nuestros clientes'],
                ],
                'shortcode_template' => '[testimonials bc_title="{bc_title}"][/testimonials]',
                'render_template' => '<div class="testimonials-section"><h2 class="text-center mb-4">{bc_title}</h2><div class="row">{content}</div></div>',
                'sort_order' => 8,
            ],
            [
                'key' => 'cta',
                'name' => 'Llamada a la acción',
                'description' => 'Bloque CTA con título y botón',
                'icon' => 'fas fa-bullhorn',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título', 'type' => 'text', 'placeholder' => '¿Listo para comenzar?'],
                    ['id' => 'bc_btn', 'label' => 'Texto del botón', 'type' => 'text', 'placeholder' => 'Contáctanos'],
                    ['id' => 'bc_url', 'label' => 'URL del botón', 'type' => 'url', 'placeholder' => 'https://'],
                ],
                'shortcode_template' => '[cta bc_title="{bc_title}" bc_btn="{bc_btn}" bc_url="{bc_url}"][/cta]',
                'render_template' => '<div class="cta-section py-5 text-center bg-light"><h2>{bc_title}</h2><a href="{bc_url}" class="btn btn-primary btn-lg mt-3">{bc_btn}</a></div>',
                'sort_order' => 9,
            ],
            [
                'key' => 'map',
                'name' => 'Mapa',
                'description' => 'Mapa embebido de Google Maps',
                'icon' => 'fas fa-map-marker-alt',
                'config_fields' => [
                    ['id' => 'bc_address', 'label' => 'Dirección o URL de Google Maps embed', 'type' => 'text', 'placeholder' => 'Calle 123, Ciudad'],
                ],
                'shortcode_template' => '[map bc_address="{bc_address}"][/map]',
                'render_template' => '<div class="ratio ratio-16x9"><iframe src="https://maps.google.com/maps?q={bc_address}&output=embed" frameborder="0" allowfullscreen></iframe></div>',
                'sort_order' => 10,
            ],
            [
                'key' => 'spacer',
                'name' => 'Espaciado',
                'description' => 'Espacio en blanco configurable',
                'icon' => 'fas fa-arrows-alt-v',
                'config_fields' => [
                    ['id' => 'bc_height', 'label' => 'Altura (px)', 'type' => 'number', 'placeholder' => '40'],
                ],
                'shortcode_template' => '[spacer bc_height="{bc_height}"]',
                'render_template' => '<div style="height:{bc_height}px;display:block;"></div>',
                'sort_order' => 11,
            ],
            // ── PHP Handler shortcodes ──────────────────────────────────────────
            [
                'key' => 'column',
                'name' => 'Columna',
                'description' => 'Columna individual dentro de un [columns]',
                'icon' => 'fas fa-bars',
                'config_fields' => [
                    ['id' => 'bc_class', 'label' => 'Clases CSS', 'type' => 'text', 'placeholder' => 'col-md-6'],
                ],
                'shortcode_template' => '[column bc_class="{bc_class}"]Contenido[/column]',
                'render_template' => '<div class="col {bc_class}">{content}</div>',
                'sort_order' => 14,
            ],
            [
                'key' => 'youtube',
                'name' => 'YouTube',
                'description' => 'Incrusta un video de YouTube con aspecto 16:9',
                'icon' => 'fas fa-youtube',
                'config_fields' => [
                    ['id' => 'bc_id', 'label' => 'ID del video', 'type' => 'text', 'placeholder' => 'dQw4w9WgXcQ'],
                    ['id' => 'bc_title', 'label' => 'Título', 'type' => 'text', 'placeholder' => 'Video de YouTube'],
                ],
                'shortcode_template' => '[youtube bc_id="{bc_id}" bc_title="{bc_title}" /]',
                'render_template' => '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/{bc_id}" title="{bc_title}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
                'sort_order' => 15,
            ],
            [
                'key' => 'image',
                'name' => 'Imagen',
                'description' => 'Inserta una imagen del gestor de medios',
                'icon' => 'fas fa-image',
                'config_fields' => [
                    ['id' => 'bc_id', 'label' => 'ID de media', 'type' => 'text', 'placeholder' => '123'],
                    ['id' => 'bc_size', 'label' => 'Tamaño', 'type' => 'select', 'options' => ['thumbnail' => 'Miniatura', 'medium' => 'Mediano', 'large' => 'Grande']],
                    ['id' => 'bc_alt', 'label' => 'Texto alternativo', 'type' => 'text', 'placeholder' => 'Descripción de la imagen'],
                ],
                'shortcode_template' => '[image bc_id="{bc_id}" bc_size="{bc_size}" bc_alt="{bc_alt}" /]',
                'render_template' => '<img src="" class="img-fluid" alt="{bc_alt}" data-id="{bc_id}" data-size="{bc_size}">',
                'sort_order' => 16,
            ],
            [
                'key' => 'icon',
                'name' => 'Icono',
                'description' => 'Inserta un icono Bootstrap Icons',
                'icon' => 'fas fa-icons',
                'config_fields' => [
                    ['id' => 'bc_name', 'label' => 'Nombre del icono', 'type' => 'text', 'placeholder' => 'star'],
                    ['id' => 'bc_size', 'label' => 'Tamaño (px)', 'type' => 'text', 'placeholder' => '24'],
                    ['id' => 'bc_color', 'label' => 'Color Bootstrap', 'type' => 'text', 'placeholder' => 'primary'],
                ],
                'shortcode_template' => '[icon bc_name="{bc_name}" bc_size="{bc_size}" bc_color="{bc_color}" /]',
                'render_template' => '<i class="bi bi-{bc_name} text-{bc_color}" style="font-size:{bc_size}px;"></i>',
                'sort_order' => 17,
            ],
            [
                'key' => 'badge',
                'name' => 'Badge',
                'description' => 'Etiqueta con color Bootstrap',
                'icon' => 'fas fa-tag',
                'config_fields' => [
                    ['id' => 'bc_text', 'label' => 'Texto', 'type' => 'text', 'placeholder' => 'Nuevo'],
                    ['id' => 'bc_type', 'label' => 'Color', 'type' => 'select', 'options' => ['primary' => 'Primary', 'success' => 'Success', 'danger' => 'Danger', 'warning' => 'Warning', 'info' => 'Info']],
                ],
                'shortcode_template' => '[badge bc_type="{bc_type}"]{bc_text}[/badge]',
                'render_template' => '<span class="badge bg-{bc_type}">{content}</span>',
                'sort_order' => 18,
            ],
            [
                'key' => 'card',
                'name' => 'Card',
                'description' => 'Tarjeta Bootstrap con cabecera opcional',
                'icon' => 'fas fa-id-card',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título', 'type' => 'text', 'placeholder' => 'Mi tarjeta'],
                    ['id' => 'bc_class', 'label' => 'Clases CSS', 'type' => 'text', 'placeholder' => 'mb-3'],
                ],
                'shortcode_template' => '[card bc_title="{bc_title}" bc_class="{bc_class}"]Contenido[/card]',
                'render_template' => '<div class="card {bc_class}"><div class="card-header"><h5 class="card-title mb-0">{bc_title}</h5></div><div class="card-body">{content}</div></div>',
                'sort_order' => 19,
            ],
            [
                'key' => 'accordion',
                'name' => 'Acordeón',
                'description' => 'Contenedor de acordeón Bootstrap',
                'icon' => 'fas fa-layer-group',
                'config_fields' => [
                    ['id' => 'bc_id', 'label' => 'ID del acordeón', 'type' => 'text', 'placeholder' => 'faq-1'],
                ],
                'shortcode_template' => '[accordion bc_id="{bc_id}"][/accordion]',
                'render_template' => '<div class="accordion" id="{bc_id}">{content}</div>',
                'sort_order' => 20,
            ],
            [
                'key' => 'accordion-item',
                'name' => 'Item de acordeón',
                'description' => 'Elemento individual dentro de un [accordion]',
                'icon' => 'fas fa-list',
                'config_fields' => [
                    ['id' => 'bc_title', 'label' => 'Título del item', 'type' => 'text', 'placeholder' => 'Pregunta'],
                    ['id' => 'bc_parent', 'label' => 'ID del acordeón padre', 'type' => 'text', 'placeholder' => 'faq-1'],
                    ['id' => 'bc_id', 'label' => 'ID del item', 'type' => 'text', 'placeholder' => 'item-1'],
                ],
                'shortcode_template' => '[accordion-item bc_title="{bc_title}" bc_parent="{bc_parent}" bc_id="{bc_id}"]Contenido[/accordion-item]',
                'render_template' => '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{bc_id}" aria-expanded="false">{bc_title}</button></h2><div id="{bc_id}" class="accordion-collapse collapse" data-bs-parent="#{bc_parent}"><div class="accordion-body">{content}</div></div></div>',
                'sort_order' => 21,
            ],
            [
                'key' => 'quote',
                'name' => 'Cita',
                'description' => 'Blockquote con autor y fuente opcionales',
                'icon' => 'fas fa-quote-left',
                'config_fields' => [
                    ['id' => 'bc_text', 'label' => 'Texto de la cita', 'type' => 'textarea', 'placeholder' => 'Escribe la cita aquí...'],
                    ['id' => 'bc_author', 'label' => 'Autor', 'type' => 'text', 'placeholder' => 'Nombre del autor'],
                    ['id' => 'bc_cite', 'label' => 'Fuente', 'type' => 'text', 'placeholder' => 'Título de la obra'],
                ],
                'shortcode_template' => '[quote bc_author="{bc_author}" bc_cite="{bc_cite}"]{bc_text}[/quote]',
                'render_template' => '<blockquote class="blockquote"><p class="mb-0">{content}</p><footer class="blockquote-footer">{bc_author} <cite title="{bc_cite}">{bc_cite}</cite></footer></blockquote>',
                'sort_order' => 22,
            ],
            [
                'key' => 'our-offices',
                'name' => 'Nuestras oficinas',
                'description' => 'Listado de sedes y oficinas del sitio',
                'icon' => 'fas fa-building',
                'config_fields' => [],
                'shortcode_template' => '[our-offices][/our-offices]',
                'render_template' => null,
                'sort_order' => 23,
            ],
            [
                'key' => 'site-features',
                'name' => 'Características del sitio',
                'description' => 'Bloque de características principales',
                'icon' => 'fas fa-star',
                'config_fields' => [],
                'shortcode_template' => '[site-features][/site-features]',
                'render_template' => null,
                'sort_order' => 24,
            ],
            [
                'key' => 'reviews',
                'name' => 'Reseñas',
                'description' => 'Bloque de reseñas y valoraciones',
                'icon' => 'fas fa-star-half-alt',
                'config_fields' => [],
                'shortcode_template' => '[reviews][/reviews]',
                'render_template' => null,
                'sort_order' => 25,
            ],
            [
                'key' => 'gallery',
                'name' => 'Galería',
                'description' => 'Galería de imágenes del tema',
                'icon' => 'fas fa-images',
                'config_fields' => [],
                'shortcode_template' => '[gallery][/gallery]',
                'render_template' => null,
                'sort_order' => 26,
            ],
            [
                'key' => 'form',
                'name' => 'Formulario',
                'description' => 'Inserta un formulario del módulo Forms',
                'icon' => 'fas fa-wpforms',
                'config_fields' => [
                    ['id' => 'bc_id', 'label' => 'ID del formulario', 'type' => 'text', 'placeholder' => '1'],
                    ['id' => 'bc_display', 'label' => 'Visualización', 'type' => 'select', 'options' => ['inline' => 'En línea', 'modal' => 'Modal']],
                ],
                'shortcode_template' => '[form id="{bc_id}" display="{bc_display}"][/form]',
                'render_template' => null,
                'sort_order' => 27,
            ],
        ];

        foreach ($shortcodes as $data) {
            Shortcode::updateOrCreate(['key' => $data['key']], $data);
        }
    }
}
