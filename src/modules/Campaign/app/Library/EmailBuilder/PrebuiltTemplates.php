<?php

namespace Modules\Campaign\Library\EmailBuilder;

/**
 * Plantillas pre-hechas como árboles de bloques. Pensadas para que el usuario
 * arranque desde un punto razonable en vez de un canvas vacío.
 *
 * Tipos:
 *   - newsletter        — boletín informativo, varios artículos.
 *   - promotional       — descuento / oferta puntual con CTA prominente.
 *   - welcome           — bienvenida tras suscripción.
 *   - transactional     — confirmación de acción (compra, signup).
 *   - announcement      — anuncio breve / cambio de producto.
 */
class PrebuiltTemplates
{
    /** Catálogo: clave → metadata. */
    public static function catalog(): array
    {
        return [
            'newsletter' => ['name' => 'Newsletter', 'description' => 'Boletín mensual con varios bloques de contenido', 'thumbnail' => '📰'],
            'promotional' => ['name' => 'Promocional', 'description' => 'Descuento u oferta con CTA destacado', 'thumbnail' => '🏷️'],
            'welcome' => ['name' => 'Bienvenida', 'description' => 'Email de bienvenida tras suscripción', 'thumbnail' => '👋'],
            'transactional' => ['name' => 'Confirmación', 'description' => 'Confirmación de acción: compra, alta, etc.', 'thumbnail' => '✅'],
            'announcement' => ['name' => 'Anuncio', 'description' => 'Anuncio breve de novedad o cambio', 'thumbnail' => '📣'],
        ];
    }

    /** Construye el árbol de bloques para una plantilla concreta. */
    public static function blocks(string $key): array
    {
        return match ($key) {
            'newsletter' => self::newsletter(),
            'promotional' => self::promotional(),
            'welcome' => self::welcome(),
            'transactional' => self::transactional(),
            'announcement' => self::announcement(),
            default => [],
        };
    }

    /** Settings globales por plantilla (overrides de defaults del Renderer). */
    public static function globals(string $key): array
    {
        return match ($key) {
            'promotional' => ['link_color' => '#e91e63'],
            'welcome' => ['link_color' => '#22c55e'],
            'transactional' => ['link_color' => '#0d6efd'],
            default => [],
        };
    }

    // ────────────────────────────────────────────────────────────────────
    // Plantillas — cada una devuelve un array de bloques.
    // ────────────────────────────────────────────────────────────────────

    protected static function newsletter(): array
    {
        return [
            ['type' => 'header', 'settings' => ['background_color' => '#0d6efd', 'text_color' => '#ffffff', 'padding' => '32px 30px', 'align' => 'left'], 'content' => ['title' => 'Tu Newsletter', 'subtitle' => 'Edición de '.date('F Y')]],
            ['type' => 'text', 'settings' => ['padding' => '32px 30px 16px'], 'content' => ['html' => '<h2 style="margin:0 0 16px;font-size:22px;">Hola {{FIRST_NAME}},</h2><p>Te traemos las últimas novedades del mes. Disfruta de la lectura.</p>']],
            ['type' => 'image', 'settings' => ['padding' => '0 30px 24px'], 'content' => ['url' => 'https://via.placeholder.com/540x280?text=Hero', 'alt' => 'Imagen destacada']],
            ['type' => 'text', 'settings' => ['padding' => '0 30px 24px'], 'content' => ['html' => '<h3 style="margin:0 0 12px;">Artículo principal</h3><p>Resumen del artículo destacado del mes. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>']],
            ['type' => 'button', 'settings' => ['padding' => '0 30px 24px', 'align' => 'left', 'background_color' => '#0d6efd'], 'content' => ['text' => 'Leer más →', 'url' => 'https://example.com/articulo']],
            ['type' => 'divider', 'settings' => ['padding' => '8px 30px'], 'content' => []],
            ['type' => 'columns', 'settings' => ['padding' => '24px 30px'], 'content' => ['columns' => [['html' => '<h4 style="margin:0 0 8px;">Tip rápido</h4><p style="margin:0;font-size:14px;">Un consejo breve para tus lectores.</p>'], ['html' => '<h4 style="margin:0 0 8px;">Recurso</h4><p style="margin:0;font-size:14px;">Recurso útil de la semana.</p>']]]],
            ['type' => 'social', 'settings' => ['padding' => '24px 30px'], 'content' => ['networks' => [['name' => 'Twitter', 'url' => 'https://twitter.com/'], ['name' => 'Instagram', 'url' => 'https://instagram.com/'], ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/']]]],
            ['type' => 'footer', 'settings' => [], 'content' => ['text' => '© '.date('Y').' Tu Marca · {{COMPANY_ADDRESS}}<br><a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a> · <a href="{{MANAGE_URL}}">Preferencias</a>']],
        ];
    }

    protected static function promotional(): array
    {
        return [
            ['type' => 'header', 'settings' => ['background_color' => '#e91e63', 'text_color' => '#ffffff', 'padding' => '32px 30px', 'align' => 'center', 'font_size' => '32px'], 'content' => ['title' => '¡Oferta exclusiva!', 'subtitle' => 'Solo por tiempo limitado']],
            ['type' => 'hero', 'settings' => ['padding' => '40px 30px', 'align' => 'center'], 'content' => ['image_url' => 'https://via.placeholder.com/540x300?text=Producto', 'image_alt' => 'Producto', 'title' => '40% de descuento', 'subtitle' => 'En toda la colección. Solo este fin de semana.', 'button_text' => 'Comprar ahora', 'button_url' => 'https://example.com/shop']],
            ['type' => 'text', 'settings' => ['padding' => '0 30px 32px', 'align' => 'center'], 'content' => ['html' => '<p style="font-size:14px;color:#999;">Usa el código <strong style="color:#e91e63;">SUMMER40</strong> al pagar.</p>']],
            ['type' => 'divider', 'settings' => [], 'content' => []],
            ['type' => 'footer', 'settings' => [], 'content' => ['text' => '© '.date('Y').' Tu Marca · {{COMPANY_ADDRESS}}<br><a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a>']],
        ];
    }

    protected static function welcome(): array
    {
        return [
            ['type' => 'header', 'settings' => ['background_color' => '#22c55e', 'text_color' => '#ffffff', 'padding' => '32px 30px', 'align' => 'center'], 'content' => ['title' => '¡Bienvenido!', 'subtitle' => '']],
            ['type' => 'text', 'settings' => ['padding' => '32px 30px 16px'], 'content' => ['html' => '<h2 style="margin:0 0 16px;">Hola {{FIRST_NAME}} 👋</h2><p>Gracias por suscribirte. Vamos a enviarte contenido útil cada semana — sin spam, prometido.</p><p>Mientras tanto, aquí tienes recursos para empezar:</p>']],
            ['type' => 'columns', 'settings' => ['padding' => '0 30px 24px'], 'content' => ['columns' => [['html' => '<h4>📚 Guías</h4><p>Tutoriales paso a paso.</p>'], ['html' => '<h4>💬 Comunidad</h4><p>Únete al chat.</p>'], ['html' => '<h4>🎁 Recursos</h4><p>Plantillas gratis.</p>']]]],
            ['type' => 'button', 'settings' => ['padding' => '0 30px 32px', 'align' => 'center', 'background_color' => '#22c55e'], 'content' => ['text' => 'Comenzar', 'url' => 'https://example.com/onboarding']],
            ['type' => 'footer', 'settings' => [], 'content' => ['text' => 'Gracias por unirte · <a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a>']],
        ];
    }

    protected static function transactional(): array
    {
        return [
            ['type' => 'header', 'settings' => ['background_color' => '#ffffff', 'text_color' => '#222', 'padding' => '24px 30px', 'align' => 'left', 'font_size' => '20px'], 'content' => ['title' => 'Tu Marca', 'subtitle' => '']],
            ['type' => 'divider', 'settings' => ['padding' => '0 30px'], 'content' => []],
            ['type' => 'text', 'settings' => ['padding' => '32px 30px 8px'], 'content' => ['html' => '<h2 style="margin:0 0 16px;">✅ Confirmación</h2><p>Hola {{FIRST_NAME}},</p><p>Hemos procesado tu solicitud correctamente. Aquí tienes los detalles:</p>']],
            ['type' => 'text', 'settings' => ['padding' => '0 30px 24px', 'background_color' => '#f9fafb'], 'content' => ['html' => '<p style="background:#f9fafb;padding:16px;border-radius:6px;border:1px solid #e5e7eb;"><strong>Referencia:</strong> #ABC123<br><strong>Fecha:</strong> '.now()->format('Y-m-d').'<br><strong>Estado:</strong> Confirmado</p>']],
            ['type' => 'button', 'settings' => ['padding' => '0 30px 32px', 'align' => 'left'], 'content' => ['text' => 'Ver detalles', 'url' => 'https://example.com/account']],
            ['type' => 'footer', 'settings' => [], 'content' => ['text' => 'Si no fuiste tú, contacta soporte.<br><a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a>']],
        ];
    }

    protected static function announcement(): array
    {
        return [
            ['type' => 'header', 'settings' => ['background_color' => '#1e293b', 'text_color' => '#ffffff', 'padding' => '40px 30px', 'align' => 'center', 'font_size' => '32px'], 'content' => ['title' => '📣 Anuncio importante', 'subtitle' => 'Algo nuevo en camino']],
            ['type' => 'text', 'settings' => ['padding' => '32px 30px'], 'content' => ['html' => '<h2 style="margin:0 0 16px;">Novedad: lanzamos X</h2><p>Estamos emocionados de anunciar que [...] Lorem ipsum dolor sit amet.</p><ul><li>Punto destacado 1</li><li>Punto destacado 2</li><li>Punto destacado 3</li></ul>']],
            ['type' => 'image', 'settings' => ['padding' => '0 30px 24px'], 'content' => ['url' => 'https://via.placeholder.com/540x280?text=Anuncio', 'alt' => 'Anuncio']],
            ['type' => 'button', 'settings' => ['padding' => '0 30px 40px', 'align' => 'center', 'background_color' => '#1e293b'], 'content' => ['text' => 'Ver más', 'url' => 'https://example.com/announcement']],
            ['type' => 'footer', 'settings' => [], 'content' => ['text' => '<a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a>']],
        ];
    }
}
