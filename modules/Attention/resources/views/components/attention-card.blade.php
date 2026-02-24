@props(['attention'])

<div {{ $attributes->merge(['class' => 'card mb-3 attention-card']) }}>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="avatar-circle bg-light-primary text-primary">
                            <i class="fa-duotone fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <a href="{{ route('attention.show', $attention->uid) }}" class="text-decoration-none">
                                {{ $attention->subject }}
                            </a>
                        </h6>
                        <p class="mb-2 text-muted small">
                            <span class="badge bg-light-info text-info me-2">{{ $attention->type->name }}</span>
                            <strong>Radicado:</strong> {{ $attention->radicado }}
                        </p>
                        <p class="mb-0 text-muted small">
                            <i class="fa-duotone fa-user me-1"></i> {{ $attention->full_name }}
                            <span class="mx-2">|</span>
                            <i class="fa-duotone fa-calendar me-1"></i> {{ $attention->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <x-attention::status-badge :status="$attention->status" class="mb-2" />
                <div class=" text-muted">
                    @if($attention->assigned_user_id)
                        <i class="fa-duotone fa-user-check"></i> {{ $attention->assignedUser->name }}
                    @elseif($attention->department_id)
                        <i class="fa-duotone fa-building"></i> {{ $attention->department->name }}
                    @else
                        <i class="fa-duotone fa-clock"></i> Sin asignar
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .avatar-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .attention-card {
            transition: box-shadow 0.3s;
        }

        .attention-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
@endpush
