@extends('layouts.theme')

@section('title', 'Emails - Submission #' . $submission->id)

@php
    $hasFilters = request()->hasAny(['type', 'status', 'date_from', 'date_to']);
    $activeFilterCount = collect(['type', 'status', 'date_from', 'date_to'])->filter(fn ($f) => request()->filled($f))->count();
@endphp

@section('content')

    @include('core::components.card', ['title' => 'Emails'])

    <div class="widget-content searchable-container list">

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Emails enviados</h5>
                        <p class=" mb-0 text-muted">Historial de correos electronicos enviados para esta submission</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#customEmailModal">
                                    Enviar email
                                </button>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    Filtrar
                                    @if($activeFilterCount > 0)
                                        <span class="badge bg-danger ms-1">{{ $activeFilterCount }}</span>
                                    @endif
                                </button>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('forms.inbox.show', $submission) }}">
                                    Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info bar --}}
            <div class="card-body border-bottom">
                <div class="alert bg-light border mb-0" role="alert">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-1">Submission #{{ $submission->id }}</h6>
                            <p class="mb-0 text-muted">
                                <strong>Formulario:</strong> {{ $submission->form->name ?? '—' }}
                                <span class="mx-2">|</span>
                                <strong>Email:</strong> {{ $submitterEmail ?? 'No disponible' }}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success-subtle text-success me-1">{{ $stats['sent'] }} enviados</span>
                            @if($stats['failed'] > 0)
                                <span class="badge bg-danger-subtle text-danger me-1">{{ $stats['failed'] }} fallidos</span>
                            @endif
                            @if($stats['queued'] > 0)
                                <span class="badge bg-warning-subtle text-warning">{{ $stats['queued'] }} en cola</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Active filter badges --}}
                @if($hasFilters)
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <small class="text-muted fw-semibold">Filtros:</small>
                        @if(request()->filled('type'))
                            <span class="badge bg-info-subtle text-info">
                                Tipo: {{ ucfirst(request('type')) }}
                                <a href="{{ route('forms.inbox.emails.index', array_merge([$submission], request()->except('type'))) }}" class="ms-1 text-info">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('status'))
                            <span class="badge bg-light-subtle text-primary">
                                Estado: {{ ucfirst(request('status')) }}
                                <a href="{{ route('forms.inbox.emails.index', array_merge([$submission], request()->except('status'))) }}" class="ms-1 text-primary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('date_from'))
                            <span class="badge bg-light-subtle text-secondary">
                                Desde: {{ request('date_from') }}
                                <a href="{{ route('forms.inbox.emails.index', array_merge([$submission], request()->except('date_from'))) }}" class="ms-1 text-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        @if(request()->filled('date_to'))
                            <span class="badge bg-light-subtle text-secondary">
                                Hasta: {{ request('date_to') }}
                                <a href="{{ route('forms.inbox.emails.index', array_merge([$submission], request()->except('date_to'))) }}" class="ms-1 text-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endif
                        <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-times me-1"></i>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Emails Table --}}
            @if($emails->count() > 0)
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
                                @foreach($emails as $email)
                                    <tr>
                                        <td>
                                            <p class="mb-0 text-truncate" style="max-width: 300px;" title="{{ $email->subject }}">
                                                {{ Str::limit($email->subject, 45) }}
                                            </p>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $email->status_color }}-subtle text-{{ $email->status_color }}">
                                                {{ $email->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $email->sender?->firstname ?? 'Sistema' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $email->email_type_label }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($email->recipient_email, 30) }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ ($email->sent_at ?? $email->created_at)?->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('forms.inbox.emails.preview', [$submission, $email]) }}" class="dropdown-item">
                                                            Ver preview
                                                        </a>
                                                    </li>
                                                    @if($email->status === 'sent' || $email->status === 'failed')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item resend-email-btn" data-email-id="{{ $email->id }}">
                                                                Reenviar
                                                            </button>
                                                        </li>
                                                    @endif
                                                    @if($email->status === 'failed' && $email->error_message)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#errorModal{{ $email->id }}">
                                                                Ver error
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Error Modal --}}
                                    @if($email->status === 'failed' && $email->error_message)
                                        <tr class="d-none">
                                            <td colspan="7">
                                                <div class="modal fade" id="errorModal{{ $email->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Error de envio</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-2"><strong>Fecha:</strong> {{ $email->created_at->format('d/m/Y H:i:s') }}</p>
                                                                <p class="mb-2"><strong>Tipo:</strong> {{ $email->email_type_label }}</p>
                                                                <hr>
                                                                <p class="mb-0"><strong>Mensaje de error:</strong></p>
                                                                <pre class="bg-light p-3 rounded mt-2 small">{{ $email->error_message }}</pre>
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
                                Aun no se han enviado correos electronicos para esta submission.
                            @endif
                        </p>
                        @if($hasFilters)
                            <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-secondary">
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
            @if($emails->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $emails->firstItem() }} - {{ $emails->lastItem() }} de {{ $emails->total() }} emails
                        </div>
                        <div>
                            {{ $emails->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

{{-- Filter Modal --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('forms.inbox.emails.index', $submission) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filtrar correos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de email</label>
                        <select name="type" class="form-select" data-no-select2 id="filterType">
                            <option value="">Todos los tipos</option>
                            <option value="confirmation" {{ request('type') === 'confirmation' ? 'selected' : '' }}>Confirmacion</option>
                            <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Administrador</option>
                            <option value="custom" {{ request('type') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                            <option value="resend" {{ request('type') === 'resend' ? 'selected' : '' }}>Reenvio</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select" data-no-select2 id="filterStatus">
                            <option value="">Todos los estados</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviado</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                            <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>En cola</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Desde</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                    @if($hasFilters)
                        <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-outline-secondary w-100 mb-2">
                            Limpiar filtros
                        </a>
                    @endif
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Custom Email Modal --}}
<div class="modal fade" id="customEmailModal" tabindex="-1" aria-labelledby="customEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customEmailModalLabel">Enviar email personalizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customEmailForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipient" class="form-label fw-semibold">Destinatario</label>
                        <input type="email" id="recipient" name="recipient" class="form-control"
                               value="{{ $submitterEmail }}" placeholder="email@ejemplo.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">Asunto</label>
                        <input type="text" id="subject" name="subject" class="form-control"
                               placeholder="Asunto del correo" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label fw-semibold">Mensaje</label>
                        <textarea id="message" name="message" class="form-control" rows="5"
                                  placeholder="Escribe el mensaje aqui..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1" id="sendEmailBtn">Enviar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Init filter modal selects on show
$('#filterModal').on('shown.bs.modal', function () {
    var $modal = $(this);
    ['#filterType', '#filterStatus'].forEach(function (sel) {
        var $el = $modal.find(sel);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({ dropdownParent: $modal, width: '100%' });
    });
});

$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Resend email
    $(document).on('click', '.resend-email-btn', function () {
        var emailId = $(this).data('email-id');
        var $btn = $(this);

        if (!confirm('¿Esta seguro de reenviar este email?')) {
            return;
        }

        $btn.prop('disabled', true).text('Reenviando...');

        $.ajax({
            url: '{{ route("forms.inbox.emails.resend", [$submission, ":emailId"]) }}'.replace(':emailId', emailId),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.success(response.message || 'Email reenviado correctamente');
                setTimeout(function () { location.reload(); }, 1500);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al reenviar el email');
                $btn.prop('disabled', false).text('Reenviar');
            }
        });
    });

    // Send custom email
    $('#customEmailForm').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#sendEmailBtn');
        $btn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: '{{ route("forms.inbox.emails.send-custom", $submission) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                recipient: $('#recipient').val(),
                subject: $('#subject').val(),
                message: $('#message').val()
            },
            success: function (response) {
                toastr.success(response.message || 'Email enviado correctamente');
                $('#customEmailModal').modal('hide');
                setTimeout(function () { location.reload(); }, 1500);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar el email');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Enviar');
            }
        });
    });
});
</script>
@endpush
