@extends('layouts.theme')

@section('title', 'Submission #' . $submission->id . ' - ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'Detalle de la submission'])

    @php
        $statusMap = [
            'new'       => ['label' => 'Nuevo',       'class' => 'bg-primary'],
            'in_review' => ['label' => 'En revisión', 'class' => 'bg-warning text-dark'],
            'resolved'  => ['label' => 'Resuelto',    'class' => 'bg-success'],
            'rejected'  => ['label' => 'Rechazado',   'class' => 'bg-danger'],
        ];
        $st = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
    @endphp

    <div class="row">
        <div class="col-lg-8">

            {{-- Información general --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Información general</h5>
                            <p class="mb-0 text-muted">Detalles de la submission enviada</p>
                        </div>
                        <span class="badge {{ $st['class'] }}" id="statusBadge">{{ $st['label'] }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Formulario</label>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $form->name }}</span>
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de envío</label>
                            <p class="mb-0">{{ $submission->created_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">IP</label>
                            <p class="mb-0">{{ $submission->ip_address ?? '—' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">País / Ciudad</label>
                            <p class="mb-0">{{ implode(', ', array_filter([$submission->country, $submission->city])) ?: '—' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Leído</label>
                            <p class="mb-0">
                                @if ($submission->is_read)
                                    <span class="badge bg-success">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Spam</label>
                            <p class="mb-0">
                                @if ($submission->is_spam)
                                    <span class="badge bg-danger">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                        @if ($submission->user)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Usuario</label>
                                <p class="mb-0">{{ $submission->user->full_name }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Respuestas del formulario --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Respuestas</h5>
                    <p class="mb-0 text-muted">Campos enviados por el usuario</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Campo</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($submission->values->reject(fn($v) => $v->field_type === 'hidden') as $value)
                                    <tr>
                                        <td class="text-muted fw-semibold">
                                            {{ $value->field_label ?: $value->field_key }}
                                        </td>
                                        <td>
                                            @if ($value->field_type === 'signature' && $value->value)
                                                <img src="{{ $value->value }}" alt="Firma"
                                                     style="max-height: 60px; border: 1px solid #ddd; border-radius: 4px;">
                                            @else
                                                {{ $value->getDisplayValue() ?: '—' }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Sin respuestas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Metadatos --}}
            @if ($submission->utm_source || $submission->utm_medium || $submission->utm_campaign || $submission->utm_term || $submission->referrer_url || $submission->time_to_complete || $submission->user_agent)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Metadatos</h5>
                        <p class="mb-0 text-muted">Información técnica del envío</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if ($submission->utm_source)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Source</label>
                                    <p class="mb-0">{{ $submission->utm_source }}</p>
                                </div>
                            @endif
                            @if ($submission->utm_medium)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Medium</label>
                                    <p class="mb-0">{{ $submission->utm_medium }}</p>
                                </div>
                            @endif
                            @if ($submission->utm_campaign)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Campaign</label>
                                    <p class="mb-0">{{ $submission->utm_campaign }}</p>
                                </div>
                            @endif
                            @if ($submission->utm_term)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">UTM Term</label>
                                    <p class="mb-0">{{ $submission->utm_term }}</p>
                                </div>
                            @endif
                            @if ($submission->referrer_url)
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Referrer URL</label>
                                    <p class="mb-0 text-truncate" title="{{ $submission->referrer_url }}">
                                        {{ $submission->referrer_url }}
                                    </p>
                                </div>
                            @endif
                            @if ($submission->time_to_complete)
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Tiempo de completado</label>
                                    <p class="mb-0">{{ gmdate('i:s', $submission->time_to_complete) }} min</p>
                                </div>
                            @endif
                            @if ($submission->user_agent)
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">User Agent</label>
                                    <p class="mb-0 text-muted">{{ $submission->user_agent }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar derecho --}}
        <div class="col-lg-4">

            {{-- Estado y asignación --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Estado y asignación</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Estado</label>
                        <select id="statusSelect" class="form-select">
                            <option value="new"       {{ ($submission->status ?? 'new') === 'new'       ? 'selected' : '' }}>Nuevo</option>
                            <option value="in_review" {{ ($submission->status ?? 'new') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                            <option value="resolved"  {{ ($submission->status ?? 'new') === 'resolved'  ? 'selected' : '' }}>Resuelto</option>
                            <option value="rejected"  {{ ($submission->status ?? 'new') === 'rejected'  ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-muted">Asignado a</label>
                        <select id="assignedToSelect" class="form-select">
                            <option value="">— Sin asignar —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $submission->assigned_to == $user->id ? 'selected' : '' }}>
                                    {{ $user->firstname }} {{ $user->lastname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.forms.submissions.pdf', [$form, $submission]) }}"
                           class="btn btn-primary" target="_blank">
                            Descargar PDF
                        </a>
                        <button type="button" class="btn btn-outline-info" id="resendEmailBtn" data-type="admin">
                            Reenviar email
                        </button>
                        <button type="button" class="btn {{ $submission->is_spam ? 'btn-warning' : 'btn-outline-warning' }}" id="toggleSpamBtn">
                            {{ $submission->is_spam ? 'Desmarcar spam' : 'Marcar spam' }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="deleteSubmissionBtn">
                            Eliminar
                        </button>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary">
                            Volver a submissions
                        </a>
                    </div>
                </div>
            </div>

            {{-- Notas internas --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Notas internas</h5>
                </div>
                <div class="card-body">
                    <div id="notesList" class="mb-3">
                        @forelse ($submission->notes as $note)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $note->user ? $note->user->firstname.' '.$note->user->lastname : 'Sistema' }}</strong>
                                <p class="mb-1">{{ $note->note }}</p>
                                <span class="text-muted">{{ $note->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0" id="emptyNotes">Sin notas aún.</p>
                        @endforelse
                    </div>
                    <form id="addNoteForm">
                        <div class="mb-2">
                            <textarea id="noteText" class="form-control" rows="3"
                                      placeholder="Añadir nota interna..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Guardar nota
                        </button>
                    </form>
                </div>
            </div>

            {{-- Estadísticas --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Notas internas:</span>
                            <span class="fw-bold">{{ $submission->notes->count() }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Valores registrados:</span>
                            <span class="fw-bold">{{ $submission->values->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Delete form (hidden) --}}
    <form id="deleteForm" method="POST"
          action="{{ route('settings.forms.submissions.destroy', [$form, $submission]) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
<script>
    const updateStatusUrl = '{{ route('settings.forms.submissions.status', [$form, $submission]) }}';
    const assignUrl       = '{{ route('settings.forms.submissions.assign', [$form, $submission]) }}';
    const addNoteUrl      = '{{ route('settings.forms.submissions.notes.add', [$form, $submission]) }}';
    const toggleSpamUrl   = '{{ route('settings.forms.submissions.toggle-spam', [$form, $submission]) }}';
    const resendEmailUrl  = '{{ route('settings.forms.submissions.resend-email', [$form, $submission]) }}';

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#statusSelect').on('change', function () {
        $.ajax({
            url: updateStatusUrl,
            method: 'PATCH',
            data: { status: $(this).val() },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () { toastr.success('Estado actualizado'); }
        });
    });

    $('#assignedToSelect').on('change', function () {
        $.ajax({
            url: assignUrl,
            method: 'PATCH',
            data: { assigned_to: $(this).val() || null },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () { toastr.success('Asignado correctamente'); }
        });
    });

    $('#addNoteForm').on('submit', function (e) {
        e.preventDefault();
        const note = $('#noteText').val().trim();
        if (!note) { return; }
        $.ajax({
            url: addNoteUrl,
            method: 'POST',
            data: { note: note },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#emptyNotes').remove();
                $('#notesList').prepend(
                    '<div class="border-bottom pb-2 mb-2">' +
                        '<strong>' + res.user + '</strong>' +
                        '<p class="mb-1">' + res.note + '</p>' +
                        '<span class="text-muted">' + res.created_at + '</span>' +
                    '</div>'
                );
                $('#noteText').val('');
                toastr.success('Nota añadida');
            },
            error: function () { toastr.error('Error al añadir nota'); }
        });
    });

    $('#toggleSpamBtn').on('click', function () {
        $.ajax({
            url: toggleSpamUrl,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(res.is_spam ? 'Marcado como spam' : 'Desmarcado como spam');
                location.reload();
            }
        });
    });

    $('#resendEmailBtn').on('click', function () {
        $.ajax({
            url: resendEmailUrl,
            method: 'POST',
            data: { type: $(this).data('type') || 'admin' },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) { toastr.success(res.message); },
            error: function () { toastr.error('Error al reenviar el email'); }
        });
    });

    $('#deleteSubmissionBtn').on('click', function () {
        if (confirm('¿Eliminar esta submission? Esta acción no se puede deshacer.')) {
            $('#deleteForm').submit();
        }
    });
</script>
@endpush
