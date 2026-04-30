@component('ecommerce::emails.layouts.base', ['slot' => null])
<h2 style="margin:0 0 12px;color:#1a2030;">Hola {{ $customer->name }},</h2>
<p>Te extrañamos. Hace tiempo que no te vemos por la tienda.</p>

<div style="background:#fffbeb;border-left:4px solid #ffc107;padding:16px;margin:20px 0;border-radius:4px;">
    <p style="margin:0;font-size:18px;"><strong>15% OFF</strong> para tu próxima compra</p>
    <p style="margin:8px 0 0;">Usa el código: <strong style="background:#fff;padding:4px 12px;border-radius:4px;letter-spacing:2px;">VUELTA15</strong></p>
    <p style="margin:8px 0 0;font-size:13px;color:#666;">Válido los próximos 7 días.</p>
</div>

<p style="text-align:center;margin-top:24px;">
    <a href="{{ url('/tienda') }}" style="background:#b10100;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">Volver a la tienda</a>
</p>
@endcomponent
