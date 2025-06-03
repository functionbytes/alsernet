@extends('emails.layouts.return')

@section('title', 'Devolución Aprobada')
@section('header', '¡Su devolución ha sido aprobada!')

@section('content')
    <p>Estimado/a {{ $return->customer_name }},</p>

    <p>Nos complace informarle que su solicitud de devolución ha sido <strong>aprobada</strong>.</p>

    <div class="info-box">
        <h3>Información de la devolución:</h3>
        <table class="details-table">
            <tr>
                <th>Número de devolución:</th>
                <td><strong>{{ $return->number }}</strong></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><span class="status-badge status-approved">APROBADA</span></td>
            </tr>
            <tr>
                <th>Fecha de aprobación:</th>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <h3>Próximos pasos:</h3>
    <ol>
        <li><strong>Empaque el producto</strong> en su embalaje original (si es posible)</li>
        <li><strong>Imprima la etiqueta de devolución</strong> adjunta a este email</li>
        <li><strong>Envíe el paquete</strong> usando el transportista indicado</li>
        <li><strong>Guarde el comprobante</strong> de envío para su seguimiento</li>
    </ol>

    <div style="text-align: center;">
        <a href="{{ $return_url }}" class="button">Descargar etiqueta de envío</a>
    </div>

    @if(isset($return_label_path))
        <p><em>También hemos adjuntado la etiqueta de devolución a este email.</em></p>
    @endif

    <p><strong>Importante:</strong> Debe enviar el producto dentro de los próximos 14 días para que su devolución sea válida.</p>
@endsection
