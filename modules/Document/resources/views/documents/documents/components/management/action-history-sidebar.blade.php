<!-- Action History Sidebar (Col-3) -->
<div class="card">
    <div class="card-header  border-bottom py-2">
        <h6 class="mb-0 fw-bold text-dark" >
            Historial
        </h6>
    </div>
    <div class="card-body p-2" >
        @if(!$document || $document->actions->isEmpty())
            <div class="alert alert-sm alert-info py-2 px-2 mb-0" role="alert" >
                <i class="fas fa-circle-info me-1"></i>
                Sin acciones
            </div>
        @else
            <div class="timeline-compact">
                @foreach($document->actions->take(10) as $action)
                    <div class="timeline-item-compact mb-2">
                        <div class="d-flex gap-1 align-items-flex-start">
                            <!-- Timeline Marker -->
                            <div class="timeline-marker-compact flex-shrink-0" >
                                @switch($action->action_type)
                                    @case('email_initial_request')
                                        <i class="fas fa-envelope"></i>
                                        @break
                                    @case('email_reminder')
                                        <i class="fas fa-bell"></i>
                                        @break
                                    @case('email_missing_documents')
                                        <i class="fas fa-file-circle-exclamation"></i>
                                        @break
                                    @case('upload_confirmed')
                                        <i class="fas fa-check"></i>
                                        @break
                                    @case('documents_uploaded')
                                        <i class="fas fa-upload"></i>
                                        @break
                                    @case('note_added')
                                        <i class="fas fa-sticky-note"></i>
                                        @break
                                    @case('admin_documents_uploaded')
                                        <i class="fas fa-user-upload"></i>
                                        @break
                                    @case('document_deleted')
                                        <i class="fas fa-trash"></i>
                                        @break
                                    @default
                                        <i class="fas fa-dot-circle"></i>
                                @endswitch
                            </div>

                            <!-- Timeline Content -->
                            <div class="timeline-content-compact flex-grow-1" style="min-width: 0;">
                                <div class="d-flex justify-content-between align-items-flex-start gap-1 mb-0">
                                    <div style="flex: 1;">
                                        <p class="mb-0 fw-bold small" style="font-size: 0.8rem; line-height: 1.2;">
                                            {{ Str::limit($action->action_name, 25) }}
                                        </p>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            {{ $action->created_at->format('d/m H:i') }}
                                        </small>
                                    </div>
                                    <span class="badge bg-secondary" style="font-size: 0.6rem; padding: 0.25rem 0.4rem; white-space: nowrap;">
                                        @switch($action->performed_by_type)
                                            @case('admin')
                                                A
                                                @break
                                            @case('customer')
                                                C
                                                @break
                                            @default
                                                S
                                        @endswitch
                                    </span>
                                </div>
                                @if($action->description)
                                    <p class="mb-0 small text-dark" style="font-size: 0.75rem; line-height: 1.2;">
                                        {{ Str::limit($action->description, 60) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <hr class="my-1">
                    </div>
                @endforeach

                @if($document->actions->count() > 10)
                    <div class="text-center mt-2">
                        <small class="text-muted">+{{ $document->actions->count() - 10 }} más</small>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
    .timeline-marker-compact {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: white;
        flex-shrink: 0;
        margin-top: 0;
    }

    .timeline-content-compact {
        padding: 0;
    }

    .timeline-item-compact {
        border-left: 2px solid #dee2e6;
        padding-left: 0.5rem;
    }

    .timeline-item-compact:last-child {
        border-left: none;
    }

    .card-body {
        scrollbar-width: thin;
        scrollbar-color: #ccc #f5f5f5;
    }

    .card-body::-webkit-scrollbar {
        width: 6px;
    }

    .card-body::-webkit-scrollbar-track {
        background: #f5f5f5;
    }

    .card-body::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .card-body::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
</style>
