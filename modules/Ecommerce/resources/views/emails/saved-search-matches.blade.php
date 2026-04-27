@component('ecommerce::emails.layouts.base', ['slot' => null])
<h2 style="margin:0 0 12px;color:#1a2030;">Hola {{ $customer->name }},</h2>
<p>Hay <strong>{{ $matches->count() }}</strong> producto(s) nuevo(s) que coinciden con tu búsqueda guardada <strong>"{{ $searchName }}"</strong>:</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;">
    @foreach($matches as $product)
    <tr>
        <td style="padding:12px;border-bottom:1px solid #eee;">
            <h4 style="margin:0 0 4px;color:#1a2030;">{{ $product->name }}</h4>
            <p style="margin:0;color:#90bb13;font-weight:700;">${{ number_format($product->price, 2) }}</p>
            <p style="margin:8px 0 0;">
                <a href="{{ route('shop.product', $product->slug) }}" style="color:#90bb13;font-weight:600;">Ver producto &rarr;</a>
            </p>
        </td>
    </tr>
    @endforeach
</table>

<p style="margin-top:24px;font-size:13px;color:#666;">
    Para gestionar o eliminar esta búsqueda guardada,
    <a href="{{ route('account.saved-searches.index') }}" style="color:#90bb13;">visita tu cuenta</a>.
</p>
@endcomponent
