@extends('layouts.theme')

@section('title', 'Notificaciones de analytics')

@section('page_header')
    @include('core::components.card', ['title' => 'Notificaciones de analytics'])
@endsection

@section('content')
    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: formulario --}}
        <div class="col-lg-8">
            <form action="{{ route('settings.analytics.notifications.update') }}" method="POST">
                @csrf

                <div class="card">

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Notificaciones de analytics</h5>
                        <small class="text-muted">Configura los destinatarios para cada tipo de evento de reportes programados</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Reporte enviado --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Reporte enviado</h6>
                            <p class="text-muted mb-3">
                                Destinatarios que recibirán el correo cuando un reporte programado se genere sin errores.
                                <span class="ms-1">
                                    @if(count($sentEmails) > 0)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-light text-black">Sin destinatarios</span>
                                    @endif
                                </span>
                            </p>

                            <div class="emails-container" id="sent-emails-container" data-name="sent_emails[]">
                                @forelse($sentEmails as $i => $email)
                                    <div class="mb-2 email-row">
                                        <div class="input-group">
                                            <input type="email"
                                                   class="form-control @error('sent_emails.'.$i) is-invalid @enderror"
                                                   name="sent_emails[]"
                                                   value="{{ $email }}" placeholder="correo@ejemplo.com">
                                            <button type="button" class="btn btn-info remove-email"><i class="fas fa-times"></i></button>
                                            @if($loop->last)
                                                <button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>
                                            @endif
                                        </div>
                                        @error('sent_emails.'.$i)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @empty
                                    <div class="mb-2 email-row">
                                        <div class="input-group">
                                            <input type="email" class="form-control" name="sent_emails[]" placeholder="correo@ejemplo.com">
                                            <button type="button" class="btn btn-info remove-email"><i class="fas fa-times"></i></button>
                                            <button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>

                            <div class="alert alert-info border-0 mb-0 py-2 small mt-3">
                                <i class="fas fa-circle-info me-1"></i>
                                Incluye el reporte adjunto en el formato configurado (PDF, Excel o CSV).
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Error en reporte --}}
                        <div>
                            <h6 class="fw-bold mb-1">Error en reporte</h6>
                            <p class="text-muted mb-3">
                                Destinatarios que recibirán el correo cuando un reporte falle. Se incluye el mensaje de error.
                                <span class="ms-1">
                                    @if(count($failedEmails) > 0)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-light text-black">Sin destinatarios</span>
                                    @endif
                                </span>
                            </p>

                            <div class="emails-container" id="failed-emails-container" data-name="failed_emails[]">
                                @forelse($failedEmails as $i => $email)
                                    <div class="mb-2 email-row">
                                        <div class="input-group">
                                            <input type="email"
                                                   class="form-control @error('failed_emails.'.$i) is-invalid @enderror"
                                                   name="failed_emails[]"
                                                   value="{{ $email }}" placeholder="correo@ejemplo.com">
                                            <button type="button" class="btn btn-info remove-email"><i class="fas fa-times"></i></button>
                                            @if($loop->last)
                                                <button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>
                                            @endif
                                        </div>
                                        @error('failed_emails.'.$i)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @empty
                                    <div class="mb-2 email-row">
                                        <div class="input-group">
                                            <input type="email" class="form-control" name="failed_emails[]" placeholder="correo@ejemplo.com">
                                            <button type="button" class="btn btn-info remove-email"><i class="fas fa-times"></i></button>
                                            <button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>

                            <div class="alert alert-warning border-0 mb-0 py-2 small mt-3">
                                <i class="fas fa-triangle-exclamation me-1"></i>
                                Se recomienda añadir al menos un destinatario para detectar fallos en reportes programados.
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Guardar ajustes
                        </button>
                    </div>

                </div>

            </form>
        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las notificaciones</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Reporte enviado</h6>
                    <p class="text-muted mb-3">Se envía cuando un reporte programado se genera y envía correctamente. Incluye el archivo adjunto en el formato configurado (PDF, Excel o CSV).</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Error en reporte</h6>
                    <p class="text-muted mb-0">Se envía cuando ocurre un error durante la generación del reporte. El correo incluye el mensaje de error para facilitar el diagnóstico.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div>
                        <h6 class="mb-1 fw-semibold">Plantillas de correo</h6>
                        <p class="text-muted mb-0">
                            Personaliza el diseño de los correos de notificación desde el editor de plantillas.
                        </p>
                    </div>
                    <a href="{{ url('/mailers/templates') }}" class="btn btn-primary w-100 mt-2">Editar</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Consejos de uso</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">Añade siempre un destinatario para <strong>error en reporte</strong> para detectar problemas a tiempo.</li>
                        <li class="mb-2">Puedes añadir múltiples destinatarios en cada tipo de notificación.</li>
                        <li class="mb-2">Los correos se envían desde la dirección configurada en los ajustes de correo del sistema.</li>
                        <li class="mb-0">La notificación se activa automáticamente al añadir un destinatario.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Add email row — removes + from current last row, appends new row with +
    $(document).on('click', '.add-email', function () {
        var $container = $(this).closest('.emails-container');
        var name = $container.data('name');

        $(this).remove();

        var $row = $('<div class="mb-2 email-row"><div class="input-group">' +
            '<input type="email" class="form-control" name="' + name + '" placeholder="correo@ejemplo.com">' +
            '<button type="button" class="btn btn-info remove-email"><i class="fas fa-times"></i></button>' +
            '<button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>' +
            '</div></div>');
        $container.append($row);
        $row.find('input').focus();
    });

    // Remove email row — clears if last, else removes and ensures + is on new last row
    $(document).on('click', '.remove-email', function () {
        var $container = $(this).closest('.emails-container');
        var $rows = $container.find('.email-row');

        if ($rows.length > 1) {
            var $row = $(this).closest('.email-row');
            var wasLast = $row.is(':last-child');
            $row.remove();
            if (wasLast) {
                $container.find('.email-row:last .input-group').append(
                    '<button type="button" class="btn btn-outline-secondary add-email"><i class="fas fa-plus"></i></button>'
                );
            }
        } else {
            $container.find('.email-row input').val('');
        }
    });

});
</script>
@endpush
