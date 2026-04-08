@extends('layouts.theme')

@section('title', 'Submission #' . $submission->id . ' - ' . ($submission->form->name ?? ''))

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
                            <p class="small mb-0 text-muted">Detalles de la submission enviada</p>
                        </div>
                        <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Formulario</label>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $submission->form->name ?? '—' }}</span>
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
                            <label class="form-label fw-semibold text-muted">Con estrella</label>
                            <p class="mb-0">
                                @if ($submission->is_starred)
                                    <span class="badge bg-warning text-dark">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Asignado a</label>
                            <p class="mb-0">
                                @if ($submission->assignedTo)
                                    <span class="badge bg-light-success text-success">{{ $submission->assignedTo->full_name }}</span>
                                @else
                                    <span class="text-muted">Sin asignar</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Respuestas del formulario --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Respuestas</h5>
                    <p class="small mb-0 text-muted">Campos enviados por el usuario</p>
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
                        <p class="small mb-0 text-muted">Información técnica del envío</p>
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
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">Referrer URL</label>
                                    <p class="mb-0 text-truncate" title="{{ $submission->referrer_url }}">
                                        {{ $submission->referrer_url }}
                                    </p>
                                </div>
                            @endif
                            @if ($submission->time_to_complete)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold text-muted">Tiempo de completado</label>
                                    <p class="mb-0">{{ gmdate('i:s', $submission->time_to_complete) }} min</p>
                                </div>
                            @endif
                            @if ($submission->user_agent)
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">User Agent</label>
                                    <p class="mb-0 small text-muted">{{ $submission->user_agent }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar derecho --}}
        <div class="col-lg-4">

            {{-- Asignación --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Asignación</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Asignado a</label>
                        <p class="mb-0">
                            @if ($submission->assignedTo)
                                <span class="badge bg-light-success text-success">{{ $submission->assignedTo->full_name }}</span>
                            @else
                                <span class="text-muted">Sin asignar</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-muted">Estado actual</label>
                        <p class="mb-0">
                            <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                        </p>
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
                        <a href="{{ route('forms.inbox.manage', $submission) }}" class="btn btn-primary">
                            Gestionar
                        </a>
                        <a href="{{ route('settings.forms.submissions.pdf', [$submission->form, $submission]) }}"
                           class="btn btn-primary" target="_blank">
                            Descargar PDF
                        </a>
                        <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-outline-info">
                            Ver emails
                        </a>
                        <a href="{{ route('forms.inbox.index') }}" class="btn btn-outline-secondary">
                            Volver al inbox
                        </a>
                    </div>
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

@endsection
