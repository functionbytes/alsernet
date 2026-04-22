@extends('layouts.theme')

@section('title', 'Emails - ' . $attention->radicado)

@php
    $hasFilters = request()->hasAny(['type', 'status', 'date_from', 'date_to']);
    $activeFilterCount = collect(['type', 'status', 'date_from', 'date_to'])->filter(fn ($f) => request()->filled($f))->count();
@endphp

@section('content')

    @include('core::components.card', ['title' => 'Emails'])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Emails enviados</h5>
                        <p class="small mb-0 text-muted">Historial de correos electronicos enviados para esta solicitud peticiones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary position-relative" data-bs-toggle="modal" data-bs-target="#filterModal">
                            Filtrar
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $activeFilterCount }}
                                </span>
                            @endif
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customEmailModal">
                            Enviar email
                        </button>
                        <a href="{{ route('attention.show', $attention->uid) }}" class="btn btn-secondary">
                            Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info + Active Filters --}}
            <div class="card-body border-bottom">
                <div class="alert bg-light border mb-0" role="alert">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1">Radicado #{{ $attention->radicado }}</h6>
                                    <p class="mb-0  text-muted">
                                        <strong>Solicitante:</strong> {{ $attention->full_name }}
                                        <span class="mx-2">|</span>
                                        <strong>Email:</strong> {{ $attention->email ?? 'No disponible' }}
                                        <span class="mx-2">|</span>
                                        <strong>Tipo:</strong> {{ $attention->type->name ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                             <span class="badge bg-sm bg-success-subtle text-white me-1">{{ $stats['sent'] }} enviados</span>
                            @if($stats['failed'] > 0)
                                 <span class="badge bg-sm bg-danger-subtle text-danger me-1">{{ $stats['failed'] }} fallidos</span>
                            @endif
                            @if($stats['pending'] > 0)
                                 <span class="badge bg-sm bg-warning-subtle text-warning">{{ $stats['pending'] }} en cola</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Active filter badges --}}
                @if($hasFilters)
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <small class="text-muted fw-semibold">Filtros:</small>
                        @if(request()->filled('type'))
                             <span class="badge bg-sm bg-info-subtle text-info">
                                Tipo: {{ ucfirst(request('type')) }}
                                <a href="{{ route('attention.emails.index', array_merge([$attention->uid], request()->except('type'))) }}" class="ms-1 text-info">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('status'))
                             <span class="badge bg-sm bg-light-subtle text-primary">
                                Estado: {{ ucfirst(request('status')) }}
                                <a href="{{ route('attention.emails.index', array_merge([$attention->uid], request()->except('status'))) }}" class="ms-1 text-primary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('date_from'))
                             <span class="badge bg-sm bg-light-subtle text-secondary">
                                Desde: {{ request('date_from') }}
                                <a href="{{ route('attention.emails.index', array_merge([$attention->uid], request()->except('date_from'))) }}" class="ms-1 text-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('date_to'))
                             <span class="badge bg-sm bg-light-subtle text-secondary">
                                Hasta: {{ request('date_to') }}
                                <a href="{{ route('attention.emails.index', array_merge([$attention->uid], request()->except('date_to'))) }}" class="ms-1 text-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        <a href="{{ route('attention.emails.index', $attention->uid) }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-times me-1"></i>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Emails Table --}}
            @if ($mails->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Asunto</th>
                                <th>Estado</th>
                                <th>Enviado por</th>
                                <th>Tipo</th>
                                <th>Destinatario</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mails as $mail)
                                <tr>
                                    <td>
                                        <p class="mb-0 text-truncate" style="max-width: 300px;" title="{{ $mail->subject }}">
                                            {{ Str::limit($mail->subject, 45) }}
                                        </p>
                                    </td>
                                    <td>
                                        @if($mail->status === 'sent')
                                             <span class="badge bg-sm bg-success-subtle text-success">Enviado</span>
                                        @elseif($mail->status === 'failed')
                                             <span class="badge bg-sm bg-danger-subtle text-danger">Fallido</span>
                                        @else
                                             <span class="badge bg-sm bg-warning-subtle text-warning">En cola</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mail->sender)
                                            <span class="text-muted">{{ $mail->sender->firstname ?? 'Usuario' }}</span>
                                        @else
                                            <span class="text-muted">Sistema</span>
                                        @endif
                                    </td>
                                    <td>
                                         <span class="badge bg-sm bg-info-subtle text-info">{{ $mail->email_type_label ?? ucfirst($mail->email_type) }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($mail->recipient_email, 30) }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ ($mail->sent_at ?? $mail->created_at)?->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attention.emails.preview', [$attention->uid, $mail->uid]) }}" target="_blank">
                                                        Vista previa
                                                    </a>
                                                </li>
                                                @if($mail->status === 'sent' || $mail->status === 'failed')
                                                <li>
                                                    <button type="button" class="dropdown-item resend-email-btn" data-mail-uid="{{ $mail->uid }}">
                                                        Reenviar
                                                    </button>
                                                </li>
                                                @endif
                                                @if($mail->status === 'failed' && $mail->error_message)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#errorModal{{ $mail->id }}">
                                                        Ver error
                                                    </button>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Error Modal --}}
                                @if($mail->status === 'failed' && $mail->error_message)
                                <tr class="d-none">
                                    <td colspan="7">
                                        <div class="modal fade" id="errorModal{{ $mail->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            Error de envio
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-2"><strong>Fecha:</strong> {{ $mail->created_at->format('d/m/Y H:i:s') }}</p>
                                                        <p class="mb-2"><strong>Tipo:</strong> {{ $mail->email_type_label ?? ucfirst($mail->email_type) }}</p>
                                                        <hr>
                                                        <p class="mb-0"><strong>Mensaje de error:</strong></p>
                                                        <pre class="bg-light p-3 rounded mt-2 small">{{ $mail->error_message }}</pre>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-envelope-open-text fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay emails enviados</h5>
                    <p class="text-muted mb-4">
                        @if($hasFilters)
                            No se encontraron emails con los filtros aplicados.
                        @else
                            Aun no se han enviado correos electronicos para esta solicitud.
                        @endif
                    </p>
                    @if($hasFilters)
                        <a href="{{ route('attention.emails.index', $attention->uid) }}" class="btn btn-secondary">
                            Limpiar filtros
                        </a>
                    @else
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customEmailModal">
                            Enviar notificacion
                        </button>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($mails->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $mails->firstItem() }} - {{ $mails->lastItem() }} de {{ $mails->total() }} emails
                        </div>
                        <div>
                            {{ $mails->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

{{-- Filter Modal --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content ">
            <form method="GET" action="{{ route('attention.emails.index', $attention->uid) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">
                        Filtrar correos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de email</label>
                        <select class="select2" name="type" class="form-select">
                            <option value="">Todos los tipos</option>
                            <option value="confirmation" {{ request('type') === 'confirmation' ? 'selected' : '' }}>Confirmacion</option>
                            <option value="assigned" {{ request('type') === 'assigned' ? 'selected' : '' }}>Asignacion</option>
                            <option value="in_process" {{ request('type') === 'in_process' ? 'selected' : '' }}>En proceso</option>
                            <option value="resolution" {{ request('type') === 'resolution' ? 'selected' : '' }}>Resolucion</option>
                            <option value="custom" {{ request('type') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="select2" name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviado</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                            <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>En cola</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Desde</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hasta</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">

                    <button type="submit" class="btn btn-primary  w-100">
                        Aplicar filtros
                    </button>
                    @if($hasFilters)
                        <a href="{{ route('attention.emails.index', $attention->uid) }}" class="btn btn-primary me-auto w-100">
                            Limpiar
                        </a>
                    @endif
                    <button type="button" class="btn btn-secondary  w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Include Custom Email Modal --}}
@include('attention::components.email.modals.custom', ['attention' => $attention])

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.resend-email-btn').on('click', function() {
        var mailUid = $(this).data('mail-uid');
        var $btn = $(this);

        $btn.prop('disabled', true).html('Reenviando...');

        $.ajax({
            url: '{{ route("attention.emails.resend", [$attention->uid, ":mailUid"]) }}'.replace(':mailUid', mailUid),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Email reenviado correctamente');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    toastr.error(response.message || 'No se pudo reenviar el email');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Error al reenviar el email';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('Reenviar');
            }
        });
    });
});
</script>
@endpush
