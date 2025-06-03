@extends('emails.layouts.return')

@section('title', 'Devolución Completada')
@section('header', 'Su devolución ha sido procesada')

@section('content')
    <p>Estimado/a {{ $return->customer_name }},</p>

    <p>Le informamos que hemos recibido y procesado su devolución satisfactoriamente.</p>

    <div class="info-box">
        <h3>Resumen de la devolución:</h3>
        <table class="details-table">
            <tr>
                <th>Número de devolución:</th>
                <td><strong>{{ $return->number }}</strong></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><span class="status-badge status-completed">COMPLETADA</span></td>
            </tr>
            <tr>
                <th>Fecha de finalización:</th>
                <td>{{ now()->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    @if(isset($costs_summary))
        <h3>Detalles del reembolso:</h3>
        <table class="details-table">
            <tr>
                <th>Importe original:</th>
                <td>{{ number_format($return->original_amount, 2) }} €</td>
            </tr>
            <tr>
                <th>Deducciones aplicadas:</th>
                <td>-{{ number_format($costs_summary['total_deductions'], 2) }} €</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <th><strong>Reembolso final:</strong></th>
                <td><strong>{{ number_format($costs_summary['final_refund'], 2) }} €</strong></td>
            </tr>
        </table>
    @endif

    <p>El reembolso se procesará en los próximos 5-7 días hábiles en el mismo método de pago utilizado en la compra original.</p>

    <div style="text-align: center;">
        <a href="{{ $return_url }}" class="button">Ver detalles completos</a>
    </div>

    <p>Gracias por su paciencia durante este proceso. Si tiene alguna pregunta sobre su reembolso, no dude en contactarnos.</p>
@endsection
