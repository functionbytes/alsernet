@extends('emails.layouts.return')

@section('title', 'Devolución No Aprobada')
@section('header', 'Información sobre su solicitud de devolución')

@section('content')
    <p>Estimado/a {{ $return->customer_name }},</p>

    <p>Lamentamos informarle que tras revisar su solicitud, no hemos podido aprobar su devolución.</p>

    <div class="info-box">
        <h3>Detalles:</h3>
        <table class="details-table">
            <tr>
                <th>Número de devolución:</th>
                <td><strong>{{ $return->number }}</strong></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><span class="status-badge status-rejected">NO APROBADA</span></td>
            </tr>
            @if($return->rejection_reason)
                <tr>
                    <th>Motivo:</th>
                    <td>{{ $return->rejection_reason }}</td>
                </tr>
            @endif
        </table>
    </div>

    <h3>¿Qué puede hacer?</h3>
    <p>Si cree que ha habido un error o desea más información sobre esta decisión, puede:</p>
    <ul>
        <li>Contactar con nuestro servicio de atención al cliente</li>
        <li>Proporcionar información adicional sobre su caso</li>
        <li>Solicitar una revisión de su caso</li>
    </ul>

    <div style="text-align: center;">
        <a href="{{ route('contact') }}" class="button">Contactar soporte</a>
    </div>

    <p>Estamos aquí para ayudarle y encontrar la mejor solución posible.</p>
@endsection
