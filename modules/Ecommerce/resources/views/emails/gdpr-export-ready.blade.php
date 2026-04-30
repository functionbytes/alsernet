@component('ecommerce::emails.layouts.base', ['slot' => null])
<h2 style="margin:0 0 12px;color:#1a2030;">Hola {{ $customer->name }},</h2>
<p>Tu exportación de datos personales (GDPR) está lista para descargar.</p>

<p style="text-align:center;margin:24px 0;">
    <a href="{{ $downloadUrl }}" style="background:#b10100;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;font-size:16px;">
        Descargar mis datos
    </a>
</p>

<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px 16px;border-radius:4px;font-size:13px;">
    <p style="margin:0;"><strong>El enlace expira en 24 horas.</strong> Por seguridad, descarga el archivo lo antes posible.</p>
</div>

<p style="margin-top:20px;font-size:13px;color:#666;">
    El archivo ZIP contiene: tu perfil, historial de órdenes, direcciones, lista de deseos y reseñas escritas.
</p>

<p style="margin-top:12px;font-size:13px;color:#666;">
    Si <strong>no solicitaste</strong> esta exportación, ignora este correo o contáctanos.
</p>
@endcomponent
