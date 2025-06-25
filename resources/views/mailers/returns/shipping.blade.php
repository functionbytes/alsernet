@extends('emails.layouts.return')

@section('title', 'Recordatorio: Envíe su producto')

@section('content')
    <p>Estimado/a {{ $return->customer_name }},</p>

    <p>Le recordamos que su devolución <strong>#{{ $return->number }}</strong> fue <strong>aprobada</strong> hace {{ $days_pending }} días, pero aún no hemos recibido el paquete.</p>

    <div class="alert alert-warning" style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>⏰ Acción requerida:</strong> Debe enviar el producto dentro de los próximos <strong>{{ $days_until_expiration }} días</strong> para que su devolución sea válida.
    </div>

    <h3>Pasos a seguir:</h3>
    <ol>
        <li><strong>Empaque el producto</strong> de forma segura</li>
        <li><strong>Imprima la etiqueta</strong> adjunta a este email</li>
        <li><strong>Pegue la etiqueta</strong> en el paquete</li>
        <li><strong>Entregue el paquete</strong> en cualquier oficina del transportista</li>
    </ol>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $action_url }}" class="button" style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            📦 Ver Instrucciones Completas
        </a>
    </div>

    <div class="info-box" style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0;">
        <p><strong>💡 Consejo:</strong> Guarde el resguardo de envío como comprobante.</p>
    </div>

    <p>Si ya ha enviado el paquete, por favor ignore este mensaje. El estado se actualizará automáticamente cuando recibamos el tracking del transportista.</p>

    <p>Si necesita más tiempo o tiene algún problema, póngase en contacto con nosotros respondiendo a este email.</p>
@endsection
