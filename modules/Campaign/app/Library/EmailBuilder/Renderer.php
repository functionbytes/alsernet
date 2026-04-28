<?php

namespace Modules\Campaign\Library\EmailBuilder;

/**
 * Email Builder — Renderer.
 *
 * Compila un árbol JSON de bloques a HTML email-safe (table-based para
 * compatibilidad con Outlook). Cada bloque es una unidad self-contained
 * con sus settings (colores, padding, alignment, etc.) y contenido.
 *
 * Estructura del JSON:
 *
 *   [
 *     {"type": "header", "settings": {...}, "content": {...}},
 *     {"type": "hero",   "settings": {...}, "content": {...}},
 *     ...
 *   ]
 *
 * Settings globales se pasan aparte (background, content_width, font_family).
 */
class Renderer
{
    /**
     * Settings globales por defecto. Pueden sobreescribirse por el usuario
     * en el panel "ajustes globales" del builder.
     */
    public const DEFAULT_GLOBALS = [
        'content_width' => '600px',
        'background_color' => '#f4f4f7',
        'content_background_color' => '#ffffff',
        'font_family' => "'Helvetica Neue', Helvetica, Arial, sans-serif",
        'text_color' => '#333333',
        'link_color' => '#0d6efd',
    ];

    public function __construct(
        protected array $blocks = [],
        protected array $globals = [],
    ) {
        $this->globals = array_merge(self::DEFAULT_GLOBALS, $this->globals);
    }

    /** Renderiza el árbol completo a un documento HTML5 email-safe. */
    public function render(): string
    {
        $body = '';
        foreach ($this->blocks as $block) {
            $body .= $this->renderBlock($block);
        }

        return $this->wrapDocument($body);
    }

    /** Render incremental de un solo bloque (para preview en panel settings). */
    public function renderBlock(array $block): string
    {
        $type = $block['type'] ?? null;
        $settings = $block['settings'] ?? [];
        $content = $block['content'] ?? [];

        return match ($type) {
            'header' => Blocks::header($settings, $content, $this->globals),
            'hero' => Blocks::hero($settings, $content, $this->globals),
            'text' => Blocks::text($settings, $content, $this->globals),
            'image' => Blocks::image($settings, $content, $this->globals),
            'button' => Blocks::button($settings, $content, $this->globals),
            'columns' => Blocks::columns($settings, $content, $this->globals),
            'spacer' => Blocks::spacer($settings, $content, $this->globals),
            'divider' => Blocks::divider($settings, $content, $this->globals),
            'social' => Blocks::social($settings, $content, $this->globals),
            'footer' => Blocks::footer($settings, $content, $this->globals),
            'video' => Blocks::video($settings, $content, $this->globals),
            'quote' => Blocks::quote($settings, $content, $this->globals),
            'list' => Blocks::list($settings, $content, $this->globals),
            'html' => Blocks::html($settings, $content, $this->globals),
            default => '<!-- bloque desconocido: '.htmlspecialchars((string) $type).' -->',
        };
    }

    protected function wrapDocument(string $body): string
    {
        $g = $this->globals;
        $width = htmlspecialchars($g['content_width']);
        $bg = htmlspecialchars($g['background_color']);
        $contentBg = htmlspecialchars($g['content_background_color']);
        $font = htmlspecialchars($g['font_family']);
        $color = htmlspecialchars($g['text_color']);
        $linkColor = htmlspecialchars($g['link_color']);

        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Email</title>
<style type="text/css">
  body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: {$bg}; }
  table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
  img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; max-width: 100%; }
  body, p, a, span, div { font-family: {$font}; color: {$color}; }
  a { color: {$linkColor}; text-decoration: underline; }
  @media only screen and (max-width: 600px) {
    .email-container { width: 100% !important; max-width: 100% !important; }
    .stack { display: block !important; width: 100% !important; max-width: 100% !important; }
    .px-mobile { padding-left: 16px !important; padding-right: 16px !important; }
    .h1-mobile { font-size: 24px !important; line-height: 30px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:{$bg};">
  <center style="width:100%;background-color:{$bg};">
    <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" width="{$width}" class="email-container" style="margin:0 auto;max-width:{$width};background-color:{$contentBg};">
      <tr><td>
{$body}
      </td></tr>
    </table>
  </center>
</body>
</html>
HTML;
    }
}
