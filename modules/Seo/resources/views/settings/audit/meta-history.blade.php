@extends('layouts.theme')

@section('title', 'Historial de auditorías - ' . ($meta->display_title ?? "Meta #{$meta->id}"))

@section('page_header')
    @include('core::components.card', ['title' => 'Historial de auditorías'])
@endsection

@section('content')
    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card mb-4">
            <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fas fa-history me-2 text-primary"></i>
                        {{ $meta->display_title ?? "Meta #{$meta->id}" }}
                    </h5>
                    @if ($meta->canonical_url)
                        <small class="text-muted">{{ $meta->canonical_url }}</small>
                    @endif
                </div>
                <a href="{{ route('settings.seo.audit.history') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver al historial
                </a>
            </div>

            <div class="card-body">
                @if ($logs->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay auditorías registradas para este elemento.</p>
                    </div>
                @else
                    {{-- Timeline --}}
                    <div class="timeline-list">
                        @foreach ($logs as $log)
                            @php
                                $score = $log->score ?? 0;
                                if ($score >= 90) $color = '#13C672';
                                elseif ($score >= 75) $color = '#b10100';
                                elseif ($score >= 60) $color = '#FEC90F';
                                elseif ($score >= 40) $color = '#FA896B';
                                else $color = '#dc3545';
                            @endphp
                            <div class="d-flex gap-3 mb-4">
                                <div class="flex-shrink-0 text-center" style="width: 60px;">
                                    <span class="badge rounded-pill fw-bold px-2 py-2 d-block"
                                          style="background: {{ $color }}; color: #fff; font-size: 1rem;">
                                        {{ $score }}
                                    </span>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem; color: {{ $color }} !important; font-weight: bold;">
                                        {{ $log->grade ?? '—' }}
                                    </small>
                                </div>

                                <div class="flex-grow-1 border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $log->audited_at?->format('d/m/Y H:i') ?? '—' }}
                                        </small>
                                        <div class="d-flex gap-2">
                                            @if ($log->issues_count > 0)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $log->issues_count }} problema(s)
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Sin problemas
                                                </span>
                                            @endif
                                            @if ($log->passed_count > 0)
                                                <span class="badge bg-light text-dark">
                                                    {{ $log->passed_count }} OK
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if (!empty($log->issues))
                                        <button class="btn btn-sm btn-link p-0 text-decoration-none"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#meta-audit-issues-{{ $log->id }}">
                                            <i class="fas fa-chevron-down me-1"></i>Ver problemas
                                        </button>
                                        <div class="collapse mt-2" id="meta-audit-issues-{{ $log->id }}">
                                            <ul class="list-unstyled mb-0">
                                                @foreach ($log->issues as $issue)
                                                    <li class="d-flex align-items-start py-1">
                                                        @if (($issue['status'] ?? '') === 'error')
                                                            <i class="fas fa-times-circle text-danger me-2 mt-1 flex-shrink-0"></i>
                                                        @elseif (($issue['status'] ?? '') === 'warning')
                                                            <i class="fas fa-exclamation-triangle text-warning me-2 mt-1 flex-shrink-0"></i>
                                                        @else
                                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                                        @endif
                                                        <div>
                                                            <div class="small fw-semibold">{{ $issue['message'] ?? '' }}</div>
                                                            @if (!empty($issue['recommendation']))
                                                                <div class="small text-muted">{{ $issue['recommendation'] }}</div>
                                                            @endif
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
