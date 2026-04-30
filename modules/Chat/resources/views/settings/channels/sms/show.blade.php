@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">{{ $sms->inbox->name ?? 'Canal SMS' }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('settings.chat.channels.sms.edit', $sms) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('settings.chat.channels.sms.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <!-- Información del canal -->
        <div class="card mb-4">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Información del canal</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-bold" width="200">Nombre del inbox:</td>
                            <td>{{ $sms->inbox->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Número de teléfono:</td>
                            <td><code>{{ $sms->formatted_phone_number }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Proveedor:</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $sms->provider_display_name }}</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Estado:</td>
                            <td>
                                @if($sms->active)
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        @if($sms->application_id)
                        <tr>
                            <td class="fw-bold">Application ID:</td>
                            <td><code>{{ $sms->application_id }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fw-bold">Creado:</td>
                            <td>{{ $sms->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configuración webhook -->
        <div class="card mb-4">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Configuración webhook</h6>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Configura esta URL en tu panel de control de {{ $sms->provider_display_name }} para recibir mensajes:
                </p>

                <div class="input-group">
                    <input type="text" class="form-control" id="webhookUrl" value="{{ $webhookUrl }}" readonly>
                    <button class="btn btn-secondary copy-webhook-btn" type="button"
                            data-copy="{{ $webhookUrl }}">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i>
                    Esta URL debe configurarse como webhook para mensajes entrantes en tu proveedor de SMS.
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="card">
            <div class="card-header bg-info-subtle">
                <h6 class="mb-0">Estadísticas</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total conversaciones:</span>
                    <strong>{{ $sms->inbox->conversations()->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Conversaciones abiertas:</span>
                    <strong>{{ $sms->inbox->conversations()->where('status', 'open')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total mensajes:</span>
                    <strong>{{ $sms->inbox->messages()->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.copy-webhook-btn').on('click', function() {
        var text = $(this).data('copy');
        var $button = $(this);

        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            var originalHtml = $button.html();
            $button.html('<i class="fas fa-check"></i> ¡Copiado!');
            $button.removeClass('btn-outline-secondary').addClass('btn-success');

            setTimeout(function() {
                $button.html(originalHtml);
                $button.removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);
        } catch (err) {
            alert('Error al copiar. Por favor, copia manualmente.');
        }

        $temp.remove();
    });
});
</script>
@endpush
