@extends('layouts.theme')

@section('title', 'Submission #' . $submission->id . ': ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('settings.forms.index') }}">Formularios</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('settings.forms.edit', $form) }}">{{ $form->name }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('settings.forms.submissions.index', $form) }}">Submissions</a>
                    </li>
                    <li class="breadcrumb-item active">#{{ $submission->id }}</li>
                </ol>
            </nav>

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-1 fw-bold">Submission #{{ $submission->id }}</h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @php
                                $statusMap = [
                                    'new'       => ['label' => 'Nuevo',      'class' => 'bg-primary'],
                                    'in_review' => ['label' => 'En revisión', 'class' => 'bg-warning text-dark'],
                                    'resolved'  => ['label' => 'Resuelto',    'class' => 'bg-success'],
                                    'rejected'  => ['label' => 'Rechazado',   'class' => 'bg-danger'],
                                ];
                                $st = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
                            @endphp
                            <span class="badge {{ $st['class'] }}" id="statusBadge">{{ $st['label'] }}</span>
                            <select id="statusSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="new"       {{ ($submission->status ?? 'new') === 'new'       ? 'selected' : '' }}>Nuevo</option>
                                <option value="in_review" {{ ($submission->status ?? 'new') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                                <option value="resolved"  {{ ($submission->status ?? 'new') === 'resolved'  ? 'selected' : '' }}>Resuelto</option>
                                <option value="rejected"  {{ ($submission->status ?? 'new') === 'rejected'  ? 'selected' : '' }}>Rechazado</option>
                            </select>
                            <label class="small text-muted mb-0">Asignado a:</label>
                            <select id="assignedToSelect" class="form-select form-select-sm" style="width: auto;">
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
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('settings.forms.submissions.pdf', [$form, $submission]) }}"
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-info" id="resendEmailBtn"
                            data-type="admin">
                        Reenviar email
                    </button>
                    <button type="button" class="btn btn-sm {{ $submission->is_spam ? 'btn-warning' : 'btn-outline-warning' }}" id="toggleSpamBtn">
                        {{ $submission->is_spam ? 'Desmarcar spam' : 'Marcar spam' }}
                    </button>
                    <form method="POST" action="{{ route('settings.forms.submissions.destroy', [$form, $submission]) }}"
                          onsubmit="return confirm('¿Eliminar esta submission? Esta acción no se puede deshacer.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-4">

                {{-- Col principal: respuestas --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Respuestas</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Campo</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($submission->values->reject(fn($v) => $v->field_type === 'hidden') as $value)
                                            <tr>
                                                <td class="text-muted fw-semibold">{{ $value->field_label ?: $value->field_key }}</td>
                                                <td>
                                                    @if ($value->field_type === 'signature' && $value->value)
                                                        <img src="{{ $value->value }}" alt="Firma" style="max-height: 60px; border: 1px solid #ddd; border-radius: 4px;">
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
                </div>

                {{-- Col lateral --}}
                <div class="col-lg-4">

                    {{-- Información --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Información</h6>
                            <dl class="row g-0 mb-0 small">
                                <dt class="col-5 text-muted">Fecha</dt>
                                <dd class="col-7">{{ $submission->created_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                                <dt class="col-5 text-muted">IP</dt>
                                <dd class="col-7">{{ $submission->ip_address ?? '—' }}</dd>

                                <dt class="col-5 text-muted">País / Ciudad</dt>
                                <dd class="col-7">{{ implode(', ', array_filter([$submission->country, $submission->city])) ?: '—' }}</dd>

                                <dt class="col-5 text-muted">User Agent</dt>
                                <dd class="col-7 text-truncate" title="{{ $submission->user_agent ?? '' }}" style="max-width: 160px;">
                                    {{ $submission->user_agent ?? '—' }}
                                </dd>

                                <dt class="col-5 text-muted">Tiempo</dt>
                                <dd class="col-7">
                                    @if ($submission->time_to_complete)
                                        {{ gmdate('i:s', $submission->time_to_complete) }} min
                                    @else
                                        —
                                    @endif
                                </dd>

                                @if ($submission->utm_source)
                                    <dt class="col-5 text-muted">UTM Source</dt>
                                    <dd class="col-7">{{ $submission->utm_source }}</dd>
                                @endif

                                @if ($submission->utm_campaign)
                                    <dt class="col-5 text-muted">UTM Campaign</dt>
                                    <dd class="col-7">{{ $submission->utm_campaign }}</dd>
                                @endif

                                @if ($submission->referrer_url)
                                    <dt class="col-5 text-muted">Referrer</dt>
                                    <dd class="col-7 text-truncate" title="{{ $submission->referrer_url }}" style="max-width: 160px;">
                                        {{ $submission->referrer_url }}
                                    </dd>
                                @endif

                                @if ($submission->user)
                                    <dt class="col-5 text-muted">Usuario</dt>
                                    <dd class="col-7">{{ $submission->user->full_name }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    {{-- Notas internas --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Notas internas</h6>

                            <div id="notesList">
                                @forelse ($submission->notes as $note)
                                    <div class="border-bottom pb-2 mb-2">
                                        <strong class="small">{{ $note->user ? $note->user->firstname.' '.$note->user->lastname : 'Sistema' }}</strong><br>
                                        <span class="small">{{ $note->note }}</span>
                                        <small class="text-muted d-block">{{ $note->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                @empty
                                    <p class="text-muted" id="emptyNotes">Sin notas aún.</p>
                                @endforelse
                            </div>

                            <form id="addNoteForm" class="mt-3">
                                <div class="mb-2">
                                    <textarea id="noteText" class="form-control form-control-sm" rows="3"
                                              placeholder="Añadir nota interna..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i> Guardar nota
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
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
        if (!note) return;
        $.ajax({
            url: addNoteUrl,
            method: 'POST',
            data: { note: note },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#emptyNotes').remove();
                $('#notesList').append(
                    '<div class="border-bottom pb-2 mb-2">' +
                        '<strong class="small">' + res.user + '</strong><br>' +
                        '<span class="small">' + res.note + '</span>' +
                        '<small class="text-muted d-block">' + res.created_at + '</small>' +
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
</script>
@endpush
