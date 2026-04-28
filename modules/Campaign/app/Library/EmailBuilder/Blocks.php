<?php

namespace Modules\Campaign\Library\EmailBuilder;

/**
 * Definiciones de los 10 tipos de bloque que el builder soporta.
 *
 * Cada método genera HTML email-safe (table-based) para Outlook + clients
 * modernos. Los settings.* son opcionales; cada bloque tiene defaults razonables.
 *
 * Schema de un bloque:
 *   [
 *     'type' => 'header'|'hero'|'text'|'image'|'button'|'columns'|'spacer'|'divider'|'social'|'footer',
 *     'settings' => [
 *       'background_color' => '#fff',
 *       'padding' => '20px 30px',
 *       'align' => 'left|center|right',
 *       ...                                  // específicos por bloque
 *     ],
 *     'content' => [...]                     // texto, imagen, botón, etc.
 *   ]
 */
class Blocks
{
    /** Definiciones de bloques disponibles (paleta del builder). */
    public static function palette(): array
    {
        return [
            'header' => ['label' => 'Encabezado', 'icon' => 'fa-heading'],
            'hero' => ['label' => 'Hero', 'icon' => 'fa-image'],
            'text' => ['label' => 'Texto', 'icon' => 'fa-paragraph'],
            'image' => ['label' => 'Imagen', 'icon' => 'fa-picture-o'],
            'video' => ['label' => 'Video', 'icon' => 'fa-play-circle'],
            'button' => ['label' => 'Botón', 'icon' => 'fa-square'],
            'columns' => ['label' => 'Columnas', 'icon' => 'fa-columns'],
            'list' => ['label' => 'Lista', 'icon' => 'fa-list'],
            'quote' => ['label' => 'Cita', 'icon' => 'fa-quote-right'],
            'spacer' => ['label' => 'Espaciador', 'icon' => 'fa-arrows-v'],
            'divider' => ['label' => 'Separador', 'icon' => 'fa-minus'],
            'social' => ['label' => 'Redes sociales', 'icon' => 'fa-share-alt'],
            'html' => ['label' => 'HTML custom', 'icon' => 'fa-code'],
            'footer' => ['label' => 'Pie', 'icon' => 'fa-window-minimize'],
        ];
    }

    /** Devuelve un bloque vacío del tipo dado, listo para insertar. */
    public static function blank(string $type): array
    {
        return match ($type) {
            'header' => ['type' => 'header', 'settings' => ['background_color' => '#0d6efd', 'text_color' => '#ffffff', 'padding' => '24px 30px', 'align' => 'center', 'font_size' => '28px'], 'content' => ['title' => 'Tu marca', 'subtitle' => '']],
            'hero' => ['type' => 'hero', 'settings' => ['padding' => '40px 30px', 'align' => 'center', 'background_color' => '#ffffff'], 'content' => ['image_url' => '', 'image_alt' => 'Hero', 'title' => 'Título principal', 'subtitle' => 'Subtítulo opcional', 'button_text' => 'Ir a la acción', 'button_url' => 'https://example.com']],
            'text' => ['type' => 'text', 'settings' => ['padding' => '20px 30px', 'align' => 'left', 'font_size' => '16px', 'line_height' => '1.6'], 'content' => ['html' => '<p>Escribe tu contenido aquí. Puedes usar <strong>negrita</strong>, <em>cursiva</em> y <a href="#">enlaces</a>. Inserta variables como <code>{{FIRST_NAME}}</code>.</p>']],
            'image' => ['type' => 'image', 'settings' => ['padding' => '20px 30px', 'align' => 'center'], 'content' => ['url' => 'https://via.placeholder.com/540x300', 'alt' => 'Imagen', 'link' => '']],
            'button' => ['type' => 'button', 'settings' => ['padding' => '20px 30px', 'align' => 'center', 'background_color' => '#0d6efd', 'text_color' => '#ffffff', 'border_radius' => '6px', 'font_size' => '16px'], 'content' => ['text' => 'Haz click aquí', 'url' => 'https://example.com']],
            'columns' => ['type' => 'columns', 'settings' => ['padding' => '20px 30px', 'gap' => '20px'], 'content' => ['columns' => [['html' => '<p>Columna 1</p>'], ['html' => '<p>Columna 2</p>']]]],
            'spacer' => ['type' => 'spacer', 'settings' => ['height' => '32px'], 'content' => []],
            'divider' => ['type' => 'divider', 'settings' => ['padding' => '20px 30px', 'color' => '#e0e0e0', 'thickness' => '1px'], 'content' => []],
            'video' => ['type' => 'video', 'settings' => ['padding' => '20px 30px', 'align' => 'center'], 'content' => ['thumbnail_url' => 'https://via.placeholder.com/540x300?text=▶+Video', 'video_url' => 'https://youtube.com/watch?v=...', 'play_overlay' => true]],
            'quote' => ['type' => 'quote', 'settings' => ['padding' => '24px 30px', 'background_color' => '#f9fafb', 'border_color' => '#0d6efd', 'font_size' => '18px'], 'content' => ['text' => 'Una cita inspiradora va aquí.', 'author' => 'Autor']],
            'list' => ['type' => 'list', 'settings' => ['padding' => '20px 30px', 'list_type' => 'ul', 'font_size' => '16px'], 'content' => ['items' => ['Primer ítem', 'Segundo ítem', 'Tercer ítem']]],
            'html' => ['type' => 'html', 'settings' => ['padding' => '0'], 'content' => ['html' => '<!-- HTML personalizado · usa con cuidado -->']],
            'social' => ['type' => 'social', 'settings' => ['padding' => '20px 30px', 'align' => 'center', 'icon_size' => '32px'], 'content' => ['networks' => [['name' => 'Twitter', 'url' => 'https://twitter.com/'], ['name' => 'Facebook', 'url' => 'https://facebook.com/'], ['name' => 'Instagram', 'url' => 'https://instagram.com/']]]],
            'footer' => ['type' => 'footer', 'settings' => ['padding' => '24px 30px', 'background_color' => '#f4f4f7', 'text_color' => '#888888', 'font_size' => '12px', 'align' => 'center'], 'content' => ['text' => '© '.date('Y').' Tu Marca · {{COMPANY_ADDRESS}}<br><a href="{{UNSUBSCRIBE_URL}}">Darme de baja</a> · <a href="{{MANAGE_URL}}">Gestionar preferencias</a>']],
            default => ['type' => $type, 'settings' => [], 'content' => []],
        };
    }

    // ────────────────────────────────────────────────────────────────────
    // Renderers — uno por tipo de bloque. Todos devuelven HTML email-safe.
    // ────────────────────────────────────────────────────────────────────

    public static function header(array $s, array $c, array $g): string
    {
        $bg = self::esc($s['background_color'] ?? '#0d6efd');
        $color = self::esc($s['text_color'] ?? '#ffffff');
        $pad = self::esc($s['padding'] ?? '24px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $fontSize = self::esc($s['font_size'] ?? '28px');

        $title = self::esc($c['title'] ?? '');
        $subtitle = self::esc($c['subtitle'] ?? '');

        $subtitleHtml = $subtitle
            ? "<div style=\"font-size:14px;opacity:0.85;margin-top:8px;color:{$color};\">{$subtitle}</div>"
            : '';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="background-color:{$bg};padding:{$pad};color:{$color};" class="px-mobile">
    <div class="h1-mobile" style="font-size:{$fontSize};font-weight:700;line-height:1.2;color:{$color};">{$title}</div>
    {$subtitleHtml}
  </td></tr>
</table>
HTML;
    }

    public static function hero(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '40px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $bg = self::esc($s['background_color'] ?? '#ffffff');

        $imageHtml = '';
        if (! empty($c['image_url'])) {
            $url = self::esc($c['image_url']);
            $alt = self::esc($c['image_alt'] ?? '');
            $imageHtml = "<img src=\"{$url}\" alt=\"{$alt}\" style=\"max-width:100%;height:auto;display:block;margin:0 auto 24px;\" />";
        }

        $title = self::esc($c['title'] ?? '');
        $subtitle = self::esc($c['subtitle'] ?? '');
        $buttonHtml = '';
        if (! empty($c['button_text']) && ! empty($c['button_url'])) {
            $buttonHtml = self::button(
                ['padding' => '20px 0 0', 'align' => $align, 'background_color' => '#0d6efd', 'text_color' => '#ffffff', 'border_radius' => '6px'],
                ['text' => $c['button_text'], 'url' => $c['button_url']],
                $g,
            );
        }

        $subtitleHtml = $subtitle
            ? "<div style=\"font-size:16px;line-height:1.6;color:#666;margin-top:12px;\">{$subtitle}</div>"
            : '';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="background-color:{$bg};padding:{$pad};" class="px-mobile">
    {$imageHtml}
    <div class="h1-mobile" style="font-size:32px;font-weight:700;line-height:1.2;">{$title}</div>
    {$subtitleHtml}
  </td></tr>
</table>
{$buttonHtml}
HTML;
    }

    public static function text(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $align = self::esc($s['align'] ?? 'left');
        $fontSize = self::esc($s['font_size'] ?? '16px');
        $lineHeight = self::esc($s['line_height'] ?? '1.6');
        $color = self::esc($s['text_color'] ?? $g['text_color']);

        // El HTML del usuario se respeta tal cual (puede contener strong/em/a).
        // Los HtmlHandler del pipeline harán inline css y limpieza al enviar.
        $html = $c['html'] ?? '';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="padding:{$pad};font-size:{$fontSize};line-height:{$lineHeight};color:{$color};" class="px-mobile">
    {$html}
  </td></tr>
</table>
HTML;
    }

    public static function image(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $url = self::esc($c['url'] ?? '');
        $alt = self::esc($c['alt'] ?? '');
        $link = $c['link'] ?? '';

        $img = "<img src=\"{$url}\" alt=\"{$alt}\" style=\"max-width:100%;height:auto;display:block;margin:0 auto;\" />";
        if (! empty($link)) {
            $linkEsc = self::esc($link);
            $img = "<a href=\"{$linkEsc}\" target=\"_blank\">{$img}</a>";
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="padding:{$pad};" class="px-mobile">{$img}</td></tr>
</table>
HTML;
    }

    public static function button(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $bg = self::esc($s['background_color'] ?? '#0d6efd');
        $color = self::esc($s['text_color'] ?? '#ffffff');
        $radius = self::esc($s['border_radius'] ?? '6px');
        $fontSize = self::esc($s['font_size'] ?? '16px');
        $text = self::esc($c['text'] ?? 'Botón');
        $url = self::esc($c['url'] ?? '#');

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="padding:{$pad};" class="px-mobile">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
      <tr><td align="center" bgcolor="{$bg}" style="border-radius:{$radius};">
        <a href="{$url}" target="_blank" style="display:inline-block;padding:14px 32px;font-size:{$fontSize};font-weight:600;color:{$color};background-color:{$bg};border-radius:{$radius};text-decoration:none;">{$text}</a>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    public static function columns(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $columns = $c['columns'] ?? [];
        $count = max(1, count($columns));
        $widthPercent = (int) (100 / $count);

        $cells = '';
        foreach ($columns as $col) {
            $html = $col['html'] ?? '';
            $cells .= "<td valign=\"top\" class=\"stack\" width=\"{$widthPercent}%\" style=\"padding:0 10px;font-size:14px;line-height:1.5;\">{$html}</td>";
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td style="padding:{$pad};" class="px-mobile">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
      <tr>{$cells}</tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    public static function spacer(array $s, array $c, array $g): string
    {
        $h = self::esc($s['height'] ?? '32px');

        return "<table role=\"presentation\" width=\"100%\"><tr><td style=\"height:{$h};line-height:{$h};font-size:1px;\">&nbsp;</td></tr></table>";
    }

    public static function divider(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $color = self::esc($s['color'] ?? '#e0e0e0');
        $thickness = self::esc($s['thickness'] ?? '1px');

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td style="padding:{$pad};" class="px-mobile">
    <table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="border-top:{$thickness} solid {$color};font-size:0;line-height:0;">&nbsp;</td></tr></table>
  </td></tr>
</table>
HTML;
    }

    public static function social(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $size = self::esc($s['icon_size'] ?? '32px');
        $networks = $c['networks'] ?? [];

        $iconBaseUrl = 'https://cdn.simpleicons.org';

        $cells = '';
        foreach ($networks as $n) {
            $name = strtolower($n['name'] ?? '');
            $url = self::esc($n['url'] ?? '#');
            $iconName = match ($name) {
                'twitter', 'x' => 'x',
                'facebook' => 'facebook',
                'instagram' => 'instagram',
                'linkedin' => 'linkedin',
                'youtube' => 'youtube',
                'tiktok' => 'tiktok',
                default => $name,
            };
            $cells .= "<td style=\"padding:0 8px;\"><a href=\"{$url}\" target=\"_blank\"><img src=\"{$iconBaseUrl}/{$iconName}\" alt=\"".self::esc($n['name'] ?? '')."\" width=\"{$size}\" height=\"{$size}\" style=\"width:{$size};height:{$size};display:block;\" /></a></td>";
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="padding:{$pad};" class="px-mobile">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr>{$cells}</tr></table>
  </td></tr>
</table>
HTML;
    }

    public static function footer(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '24px 30px');
        $bg = self::esc($s['background_color'] ?? '#f4f4f7');
        $color = self::esc($s['text_color'] ?? '#888888');
        $fontSize = self::esc($s['font_size'] ?? '12px');
        $align = self::esc($s['align'] ?? 'center');
        $text = $c['text'] ?? '';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="background-color:{$bg};color:{$color};padding:{$pad};font-size:{$fontSize};line-height:1.5;" class="px-mobile">
    {$text}
  </td></tr>
</table>
HTML;
    }

    public static function video(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $align = self::esc($s['align'] ?? 'center');
        $thumb = self::esc($c['thumbnail_url'] ?? '');
        $videoUrl = self::esc($c['video_url'] ?? '#');
        $playOverlay = ! empty($c['play_overlay']);

        // No se pueden embeber <video> en email — usamos thumbnail con play
        // overlay que linkea a YouTube/Vimeo/etc.
        $img = "<img src=\"{$thumb}\" alt=\"Video\" style=\"max-width:100%;height:auto;display:block;margin:0 auto;\" />";

        if ($playOverlay) {
            $img = '<div style="position:relative;display:inline-block;">'
                .$img
                .'<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,.7);color:white;border-radius:50%;width:64px;height:64px;line-height:64px;font-size:24px;text-align:center;">▶</div>'
                .'</div>';
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td align="{$align}" style="padding:{$pad};" class="px-mobile">
    <a href="{$videoUrl}" target="_blank" style="text-decoration:none;">{$img}</a>
  </td></tr>
</table>
HTML;
    }

    public static function quote(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '24px 30px');
        $bg = self::esc($s['background_color'] ?? '#f9fafb');
        $border = self::esc($s['border_color'] ?? '#0d6efd');
        $fontSize = self::esc($s['font_size'] ?? '18px');
        $text = self::esc($c['text'] ?? '');
        $author = self::esc($c['author'] ?? '');

        $authorHtml = $author
            ? "<div style=\"margin-top:12px;font-size:14px;color:#666;font-style:normal;\">— {$author}</div>"
            : '';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td style="padding:{$pad};" class="px-mobile">
    <blockquote style="margin:0;padding:16px 24px;background:{$bg};border-left:4px solid {$border};font-size:{$fontSize};line-height:1.6;font-style:italic;color:#333;">
      "{$text}"
      {$authorHtml}
    </blockquote>
  </td></tr>
</table>
HTML;
    }

    public static function list(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '20px 30px');
        $tag = ($s['list_type'] ?? 'ul') === 'ol' ? 'ol' : 'ul';
        $fontSize = self::esc($s['font_size'] ?? '16px');
        $items = $c['items'] ?? [];

        $li = '';
        foreach ($items as $item) {
            $li .= '<li style="margin-bottom:8px;">'.self::esc((string) $item).'</li>';
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr><td style="padding:{$pad};font-size:{$fontSize};line-height:1.6;" class="px-mobile">
    <{$tag} style="margin:0;padding-left:24px;">{$li}</{$tag}>
  </td></tr>
</table>
HTML;
    }

    /**
     * HTML custom. NO sanitizamos — el usuario es responsable. Útil para
     * embeber código de tracking de terceros, snippets de affiliates, etc.
     */
    public static function html(array $s, array $c, array $g): string
    {
        $pad = self::esc($s['padding'] ?? '0');
        $html = $c['html'] ?? '';

        return "<table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td style=\"padding:{$pad};\" class=\"px-mobile\">{$html}</td></tr></table>";
    }

    /** Escape básico HTML (los textos del usuario van por aquí). */
    protected static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
