@component('ecommerce::emails.layouts.base', ['slot' => null])
<h2 style="margin:0 0 12px;color:#1a2030;">Hola {{ $customer->name }},</h2>
<p>Tu suscripción a <strong>{{ $product->name ?? 'producto' }}</strong> ha sido renovada exitosamente.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8f9fa;border-radius:6px;padding:16px;margin:20px 0;">
    <tr>
        <td>
            <p style="margin:0 0 8px;"><strong>Monto cobrado:</strong> ${{ number_format($subscription->price, 2) }}</p>
            <p style="margin:0 0 8px;"><strong>Frecuencia:</strong> {{ ucfirst($subscription->interval) }}</p>
            <p style="margin:0;"><strong>Próximo cobro:</strong> {{ $subscription->next_billing_at->format('d/m/Y') }}</p>
        </td>
    </tr>
</table>

<p style="text-align:center;margin-top:24px;">
    <a href="{{ route('account.subscriptions.index') }}" style="color:#b10100;font-weight:600;">Gestionar mis suscripciones &rarr;</a>
</p>

<p style="margin-top:20px;font-size:13px;color:#666;">
    Si quieres pausar o cancelar esta suscripción, puedes hacerlo desde tu cuenta en cualquier momento.
</p>
@endcomponent
