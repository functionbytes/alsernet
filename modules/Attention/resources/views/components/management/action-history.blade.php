<!-- Attention Action History -->
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Historial de acciones</h5>
        <p class="small mb-0 text-muted">Registro completo de todas las acciones realizadas en este PQRSF</p>
    </div>

    @if($attention->actions && $attention->actions->count() > 0)
        @php
            $actions = $attention->actions->sortByDesc('created_at');
            $totalActions = $actions->count();
            $showLimit = 3;
            $hasMore = $totalActions > $showLimit;
        @endphp

        <div class="card-body p-0">
            @if($hasMore)
                <div class="alert alert-info alert-sm mb-2 mx-3 mt-3" role="alert">
                    <small><i class="fas fa-info-circle me-1"></i> Mostrando las últimas {{ $showLimit }} acciones de {{ $totalActions }} totales</small>
                </div>
            @endif

            <div class="action-history-scroll">
                @foreach($actions->take($showLimit) as $action)
                    <div class="comment-row border-bottom p-4 action-item">
                        <div class="comment-text w-100 small">

                            {{-- Fecha --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Fecha</span>
                                <span class="fw-semibold text-end">
                                    {{ $action->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>

                            {{-- Acción --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Acción</span>
                                <span class="fw-semibold text-end">
                                    {{ $action->action }}
                                </span>
                            </div>

                            {{-- Realizado por --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Realizado por</span>
                                <span class="fw-semibold text-end">
                                    @if($action->user_id && $action->user)
                                        @php
                                            $firstname = ucfirst(strtolower($action->user->firstname ?? ''));
                                            $lastnameInitial = $action->user->lastname ? strtoupper(substr($action->user->lastname, 0, 1)) : '';
                                            $displayName = trim($firstname . ' ' . $lastnameInitial);
                                        @endphp
                                        {{ $displayName ?: 'Usuario' }}
                                    @else
                                        Sistema
                                    @endif
                                </span>
                            </div>

                            {{-- Descripción --}}
                            @if($action->description)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Descripción</span>
                                    <span class="fw-semibold text-end" style="max-width: 60%; word-break: break-word;">
                                        {{ Str::limit($action->description, 160) }}
                                    </span>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="card-body p-4 text-center">
            <div class="mb-3">
                <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
            </div>
            <p class="text-muted small mb-0">
                <strong>Sin acciones registradas</strong>
                <br>
                No hay acciones registradas para este PQRSF aún.
            </p>
        </div>
    @endif
</div>

@push('styles')
    <style>
        .action-history-scroll {
            overflow-y: auto;
            border-top: 1px solid #e9ecef;
            height: 200px;
        }

        @media (min-width: 576px) { .action-history-scroll { height: 220px; } }
        @media (min-width: 768px) { .action-history-scroll { height: 240px; } }
        @media (min-width: 992px) { .action-history-scroll { height: 280px; } }

        .action-history-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .action-history-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .action-history-scroll::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }
        .action-history-scroll::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .action-item {
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            transition: all 0.2s ease;
            border-color: #e9ecef !important;
        }

        .action-item:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6 !important;
        }

        .comment-text {
            overflow: hidden;
        }

        @media (max-width: 575px) {
            .action-item .small {
                font-size: 0.75rem !important;
            }
        }
    </style>
@endpush
