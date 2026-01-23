@extends('layouts.admin')

@section('title', 'Macro details')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">{{ $macro->name }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb small mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.macros.index') }}">Macros</a></li>
                            <li class="breadcrumb-item active">{{ $macro->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.macros.edit', $macro) }}" class="btn btn-primary">
                        <i class="fas fa-pen-to-square me-2"></i>Edit macro
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-lg-8">
            <!-- Macro Details -->
            <div class="card mb-4">
                <div class="card-header border-bottom">
                    <h5 class="mb-0 fw-bold">Macro details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="mb-0">
                                <label class="text-muted small">Name</label>
                                <p class="mb-0 fw-semibold">{{ $macro->name }}</p>
                            </div>
                        </div>

                        @if($macro->description)
                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="text-muted small">Description</label>
                                    <p class="mb-0">{{ $macro->description }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="col-12 col-md-6">
                            <div class="mb-0">
                                <label class="text-muted small">Visibility</label>
                                <div>
                                    @php
                                        $visibilityLabels = ['Personal', 'Global', 'Team'];
                                        $visibilityColors = ['secondary', 'success', 'info'];
                                    @endphp
                                    <span class="badge bg-{{ $visibilityColors[$macro->visibility] }}-subtle text-{{ $visibilityColors[$macro->visibility] }}">
                                        {{ $visibilityLabels[$macro->visibility] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-0">
                                <label class="text-muted small">Status</label>
                                <div>
                                    @if($macro->enabled)
                                        <span class="badge bg-success-subtle text-success">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Disabled</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-0">
                                <label class="text-muted small">Created by</label>
                                <p class="mb-0">{{ $macro->createdBy->name ?? 'Unknown' }}</p>
                                <small class="text-muted">{{ $macro->created_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-0">
                                <label class="text-muted small">Last updated</label>
                                <p class="mb-0">{{ $macro->updated_at->format('M d, Y') }}</p>
                                <small class="text-muted">{{ $macro->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header border-bottom">
                    <h5 class="mb-0 fw-bold">Actions ({{ count($macro->actions) }})</h5>
                </div>
                <div class="card-body">
                    @if(count($macro->actions) > 0)
                        <div class="list-group">
                            @foreach($macro->actions as $index => $action)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-3">
                                            <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">{{ $index + 1 }}</span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                @php
                                                    $actionLabels = [
                                                        'assign_agent' => 'Assign to agent',
                                                        'assign_team' => 'Assign to team',
                                                        'add_label' => 'Add label',
                                                        'remove_label' => 'Remove label',
                                                        'change_status' => 'Change status',
                                                        'change_priority' => 'Change priority',
                                                        'send_message' => 'Send message',
                                                        'add_private_note' => 'Add private note',
                                                        'mute_conversation' => 'Mute conversation',
                                                        'unmute_conversation' => 'Unmute conversation',
                                                        'resolve_conversation' => 'Resolve conversation',
                                                        'reopen_conversation' => 'Reopen conversation',
                                                    ];
                                                @endphp
                                                {{ $actionLabels[$action['action']] ?? $action['action'] }}
                                            </h6>
                                            @if(isset($action['value']) && !empty($action['value']))
                                                <p class="mb-0 text-muted">
                                                    <i class="fas fa-arrow-right me-1"></i>
                                                    <strong>Value:</strong> {{ $action['value'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-bolt fs-3 mb-2 d-block"></i>
                                <p class="mb-0">No actions configured for this macro</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Conditions -->
            @if($macro->hasConditions())
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0 fw-bold">Conditions</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($macro->conditions['rules']) && count($macro->conditions['rules']) > 0)
                            <div class="list-group">
                                @foreach($macro->conditions['rules'] as $index => $condition)
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <span class="badge bg-info rounded-circle" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">{{ $index + 1 }}</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="mb-0">
                                                    <strong>{{ ucfirst($condition['attribute'] ?? 'Unknown') }}</strong>
                                                    <span class="text-muted mx-1">{{ $condition['operator'] ?? 'equals' }}</span>
                                                    <strong class="text-primary">{{ $condition['value'] ?? 'N/A' }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-filter fs-3 mb-2 d-block"></i>
                                    <p class="mb-0">No conditions specified</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Quick Info -->
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-semibold">Quick info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-bolt text-warning me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block">Actions</strong>
                                    <span class="text-muted">{{ count($macro->actions) }} {{ Str::plural('action', count($macro->actions)) }}</span>
                                </div>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-filter text-info me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block">Conditions</strong>
                                    <span class="text-muted">
                                        @if($macro->hasConditions())
                                            {{ count($macro->conditions['rules'] ?? []) }} {{ Str::plural('condition', count($macro->conditions['rules'] ?? [])) }}
                                        @else
                                            None
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-calendar-check text-success me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block">Created</strong>
                                    <span class="text-muted">{{ $macro->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </li>
                        <li class="mb-0">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-pen-to-square text-primary me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block">Updated</strong>
                                    <span class="text-muted">{{ $macro->updated_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
