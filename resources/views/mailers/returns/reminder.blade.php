@extends('emails.layouts.return')

@section('title', 'Recordatorio de Devolución')
@section('header', 'Recordatorio: Su devolución está pendiente')

@section('content')
<p>Estimado/a {{ $return->customer_name }},</p>

<p>Le escribimos para recordarle que tiene una devolución pendiente de completar.</p>

<div class="info-box">
    <h3>Estado actual de su devolución:</h3>
    <table class="details-table">
        <tr>
            <th>Número de devolución:</th>
            <td><strong>{{ $return->number }}</strong></td>
        </tr>
        <tr>
            <th>Estado:</th>
            <td>
                @if($return->status === 'pending')
                <span class="status-badge status-pending">PENDIENTE DE REVISIÓN</span>
                @elseif($return->status === 'approved')
                <span class="status-badge status-approved">APROBADA - ESPERANDO ENVÍO</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Días transcurridos:</th>
            <td>{{ $days_pending ?? $return->created_at->diffInDays(now()) }} días</td>
        </tr>
    </table>
</div>

@if($return->status === 'approved')
<h3>⏰ Acción requerida:</h3>
<p>Su devolución ha sido <strong>aprobada</strong> pero aún no hemos recibido el paquete. Recuerde que debe enviar el producto dentro de los próximos <strong>{{ 14 - $days_pending }} días</strong> para que su devolución sea válida.</p>

<div style="text-align: center;">
    <a href="{{ $return_url }}" class="button">Descargar etiqueta de envío</a>
</div>
@else
<p>Estamos revisando su solicitud y le notificaremos tan pronto como tengamos una actualización.</p>

<div style="text-align: center;">
    <a href="{{ $return_url }}" class="button">Ver estado de la devolución</a>
</div>
@endif

<p><strong>Importante:</strong> Si no completa el proceso de devolución en los próximos días, su solicitud podría ser cancelada automáticamente.</p>

<p>Si ya ha enviado el producto, por favor ignore este mensaje. Si tiene alguna pregunta, no dude en contactarnos.</p>
@endsection
