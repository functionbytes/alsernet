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
                'shortcode_template' => '[alert type="{bc_type}"]{bc_message}[/alert]',
                'render_template' => null,
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
                'shortcode_template' => '[columns cols="{bc_cols}"][/columns]',
                'render_template' => null,
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
                'shortcode_template' => '[button url="{bc_url}" style="{bc_style}"]{bc_text}[/button]',
                'render_template' => null,
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
                'shortcode_template' => '[image-gallery cols="{bc_cols}"][/image-gallery]',
                'render_template' => null,
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
                'shortcode_template' => '[video url="{bc_video}"][/video]',
                'render_template' => null,
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
                'shortcode_template' => '[contact-form title="{bc_title}" email="{bc_email}"][/contact-form]',
                'render_template' => null,
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
                'shortcode_template' => '[faq title="{bc_title}"][faq-item question="Pregunta 1" answer="Respuesta 1"][/faq]',
                'render_template' => null,
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
                'shortcode_template' => '[testimonials title="{bc_title}"][/testimonials]',
                'render_template' => null,
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
                'shortcode_template' => '[cta title="{bc_title}" btn_text="{bc_btn}" btn_url="{bc_url}"][/cta]',
                'render_template' => null,
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
                'shortcode_template' => '[map address="{bc_address}"][/map]',
                'render_template' => null,
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
                'shortcode_template' => '[spacer height="{bc_height}"]',
                'render_template' => '<div style="height:{bc_height}px;display:block;"></div>',
                'sort_order' => 11,
            ],
        ];

        foreach ($shortcodes as $shortcode) {
            Shortcode::firstOrCreate(['key' => $shortcode['key']], $shortcode);
        }
    }
}
