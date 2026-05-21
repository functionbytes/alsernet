<?php

namespace Modules\Remarketing\Support;

class StarterTemplates
{
    public static function all(): array
    {
        return [
            'welcome' => self::welcome(),
            'abandoned-cart' => self::abandonedCart(),
            'win-back' => self::winBack(),
            'promotional' => self::promotional(),
        ];
    }

    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    private static function welcome(): array
    {
        return [
            'name' => 'Bienvenida',
            'type' => 'welcome',
            'subject' => '¡Bienvenido/a, {{firstName}}!',
            'icon' => 'fas fa-hand-wave',
            'description' => 'Email de bienvenida para nuevos suscriptores',
            'html_content' => self::wrap('¡Bienvenido/a, <strong>{{firstName}}</strong>!',
                '<p>Nos alegra que estés aquí. Explora todo lo que tenemos para ti.</p>
                <p><a href="{{storeDomain}}" style="display:inline-block;background:#b10100;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:600;">Ver tienda</a></p>
                <p style="font-size:12px;color:#999;">¿No quieres recibir más emails? <a href="{{unsubscribeUrl}}" style="color:#999;">Cancelar suscripción</a></p>'),
        ];
    }

    private static function abandonedCart(): array
    {
        return [
            'name' => 'Carrito abandonado',
            'type' => 'abandoned_cart',
            'subject' => '{{firstName}}, tienes artículos esperándote',
            'icon' => 'fas fa-cart-shopping',
            'description' => 'Recupera clientes que dejaron artículos en el carrito',
            'html_content' => self::wrap('Tu carrito te echa de menos',
                '<p>Hola <strong>{{firstName}}</strong>, dejaste artículos en tu carrito y nos gustaría que los completaras.</p>
                <p><a href="{{cartUrl}}" style="display:inline-block;background:#b10100;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:600;">Completar compra</a></p>
                <p style="color:#666;font-size:13px;">El stock es limitado — no lo pierdas.</p>
                <p style="font-size:12px;color:#999;"><a href="{{unsubscribeUrl}}" style="color:#999;">Cancelar suscripción</a></p>'),
        ];
    }

    private static function winBack(): array
    {
        return [
            'name' => 'Reactivación',
            'type' => 'win_back',
            'subject' => 'Te echamos de menos, {{firstName}}',
            'icon' => 'fas fa-heart',
            'description' => 'Recupera clientes inactivos con una oferta especial',
            'html_content' => self::wrap('¡Cuánto tiempo, {{firstName}}!',
                '<p>Han pasado algunos meses desde tu última visita y queremos que sepas que te echamos de menos.</p>
                <p>Como gesto especial, aquí tienes un descuento exclusivo:</p>
                <p style="font-size:28px;font-weight:700;color:#b10100;text-align:center;letter-spacing:4px;">BIENVENIDO10</p>
                <p style="text-align:center;"><a href="{{storeDomain}}" style="display:inline-block;background:#b10100;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:600;">Volver a la tienda</a></p>
                <p style="font-size:12px;color:#999;"><a href="{{unsubscribeUrl}}" style="color:#999;">Cancelar suscripción</a></p>'),
        ];
    }

    private static function promotional(): array
    {
        return [
            'name' => 'Promoción / Oferta',
            'type' => 'promotional',
            'subject' => '🎉 Oferta especial solo para ti',
            'icon' => 'fas fa-tag',
            'description' => 'Plantilla genérica para anunciar ofertas y promociones',
            'html_content' => self::wrap('¡Oferta exclusiva!',
                '<p>Hola <strong>{{firstName}}</strong>,</p>
                <p>Tenemos una oferta especial que no querrás perderte:</p>
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:20px;text-align:center;margin:20px 0;">
                    <p style="font-size:22px;font-weight:700;margin:0;">[Descripción de la oferta]</p>
                    <p style="color:#666;margin:8px 0 0;">Válida hasta [fecha]</p>
                </div>
                <p style="text-align:center;"><a href="{{storeDomain}}" style="display:inline-block;background:#b10100;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:600;">Ver oferta</a></p>
                <p style="font-size:12px;color:#999;"><a href="{{unsubscribeUrl}}" style="color:#999;">Cancelar suscripción</a></p>'),
        ];
    }

    private static function wrap(string $headline, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Email</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
        <tr>
          <td style="background:#b10100;padding:24px 32px;">
            <p style="margin:0;color:#fff;font-size:18px;font-weight:700;">{{storeName}}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <h1 style="margin:0 0 16px;font-size:24px;color:#1a1a1a;">{$headline}</h1>
            {$body}
          </td>
        </tr>
        <tr>
          <td style="background:#f9f9f9;padding:16px 32px;border-top:1px solid #e5e5e5;">
            <p style="margin:0;font-size:11px;color:#aaa;text-align:center;">{{storeName}} · {{storeDomain}}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
}
