<!-- Attention Status Timeline -->
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Historial de estado</h5>
        <p class="small mb-0 text-muted" style="font-size: 0.75rem;">Transiciones del PQRSF</p>
    </div>
    <div class="card-body p-2">

        @if($attention->actions && $attention->actions->where('action', 'status_changed')->count() > 0)
            @php
                $statusChanges = $attention->actions->where('action', 'status_changed')->sortByDesc('created_at');
                $totalChanges = $statusChanges->count();
                $showLimit = 5;
                $hasMore = $totalChanges > $showLimit;
            @endphp

            @if($hasMore)
                <div class="alert alert-info py-1 px-2 mb-2" role="alert">
                    <i class="fas fa-info-circle me-1"></i> Últimos {{ $showLimit }}/{{ $totalChanges }}
                </div>
            @endif

            <div class="status-timeline-scroll @if($hasMore) scrollable @endif" @if($hasMore) style="max-height: 350px; overflow-y: auto;" @endif>

                <ul class="timeline-widget mb-0 list-unstyled">
                    @foreach($statusChanges->take($showLimit) as $change)
                        <li class="p-3 border-bottom small">

                            {{-- Fecha --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Fecha</span>
                                <span class="fw-semibold">
                                    {{ $change->created_at->format('d/m H:i') }}
                                </span>
                            </div>

                            {{-- Cambio --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Cambio</span>
                                <span class="fw-semibold">
                                    {{ $change->description }}
                                </span>
                            </div>

                            {{-- Autor --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Actualizado por</span>
                                <span class="fw-semibold">
                                    {{ $change->user->name ?? 'Sistema' }}
                                </span>
                            </div>

                        </li>
                    @endforeach
                </ul>

            </div>
        @else
            <div class="alert alert-info alert-sm mb-2 mx-3 mt-3" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fas fa-circle-info text-black me-1 mt-1" style="font-size: 0.7rem;"></i>
                    <div>
                        <div class="fw-semibold" style="font-size: 0.7rem;">Sin cambios</div>
                        <div class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">No hay historial registrado</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .timeline-widget {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .timeline-item {
        margin-bottom: 0;
    }

    .timeline-badge {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        position: relative;
        z-index: 2;
    }

    .timeline-badge-border {
        width: 1.5px;
        height: 30px;
        background: #e9ecef;
        position: relative;
        margin-top: -3px;
        margin-bottom: -3px;
    }

    .timeline-time {
        line-height: 1.2;
    }

    .timeline-desc {
        transition: all 0.15s ease;
    }

    .timeline-item:hover .timeline-desc {
        background-color: #f8f9fa;
        border-radius: 3px;
        padding: 0.25rem;
        margin-left: -0.25rem;
    }

    .timeline-badge-wrap {
        position: relative;
        z-index: 1;
    }

    .status-timeline-scroll {
        border-radius: 3px;
        padding-right: 0.25rem;
    }

    .status-timeline-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .status-timeline-scroll::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 3px;
    }

    .status-timeline-scroll::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 3px;
    }

    .status-timeline-scroll::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }
</style>
@endpush
