@extends('layouts.theme')

@section('title', __('helpdesklivechat::triggers.title'))

@section('content')

    @include('core::components.card', ['title' => __('helpdesklivechat::triggers.title')])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ __('helpdesklivechat::triggers.title') }}</h5>
                        <p class="small mb-0 text-muted">{{ __('helpdesklivechat::triggers.subtitle') }}</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk-livechat.triggers.create') }}" class="btn bg-primary-subtle text-primary">
                            {{ __('helpdesklivechat::triggers.new') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <div class="row g-3">
                    @foreach(['total', 'active', 'message', 'cart'] as $stat)
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">{{ __('helpdesklivechat::triggers.stats_'.$stat) }}</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($stats[$stat]) }}</h4>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk-livechat.triggers.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1"><i class="fas fa-search text-muted"></i></span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="{{ __('helpdesklivechat::triggers.search') }}" value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0"><i class="fas fa-search"></i></button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk-livechat.triggers.index') }}" class="btn btn-outline-secondary flex-shrink-0"><i class="fas fa-times"></i></a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body">
                @if($triggers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('helpdesklivechat::triggers.col_name') }}</th>
                                    <th>{{ __('helpdesklivechat::triggers.col_channel') }}</th>
                                    <th>{{ __('helpdesklivechat::triggers.col_conditions') }}</th>
                                    <th>{{ __('helpdesklivechat::triggers.col_action') }}</th>
                                    <th class="text-center">{{ __('helpdesklivechat::triggers.col_status') }}</th>
                                    <th class="text-center">{{ __('helpdesklivechat::triggers.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($triggers as $trigger)
                                    <tr>
                                        <td>
                                            <div class="small fw-semibold">{{ $trigger->name }}</div>
                                            <div class="small text-muted">{{ __('helpdesklivechat::triggers.frequency_'.$trigger->frequency) }} · P{{ $trigger->priority }}</div>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary">{{ $trigger->web?->inbox?->name ?? $trigger->web?->website_url ?? '#'.$trigger->web_id }}</span></td>
                                        <td class="small">
                                            @foreach((array) $trigger->conditions as $c)
                                                <div>{{ __('helpdesklivechat::triggers.types.'.($c['type'] ?? '')) }} {{ __('helpdesklivechat::triggers.ops.'.($c['op'] ?? '')) }} <span class="fw-semibold">{{ $c['value'] ?? '' }}</span></div>
                                            @endforeach
                                            <div class="text-muted">{{ $trigger->match === 'any' ? __('helpdesklivechat::triggers.match_any') : __('helpdesklivechat::triggers.match_all') }}</div>
                                        </td>
                                        <td class="small">{{ __('helpdesklivechat::triggers.action_'.$trigger->action) }}</td>
                                        <td class="text-center">
                                            @if($trigger->is_active)
                                                <span class="badge bg-success-subtle text-success">{{ __('helpdesklivechat::triggers.active') }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ __('helpdesklivechat::triggers.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('settings.helpdesk-livechat.triggers.edit', $trigger->id) }}">{{ __('helpdesklivechat::triggers.edit_action') }}</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk-livechat.triggers.destroy', $trigger->id) }}"
                                                           data-title="{{ __('helpdesklivechat::triggers.delete_action') }}: {{ $trigger->name }}">
                                                            {{ __('helpdesklivechat::triggers.delete_action') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-bolt fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">{{ __('helpdesklivechat::triggers.empty') }}</h5>
                        <p class="text-muted mb-4">{{ __('helpdesklivechat::triggers.empty_sub') }}</p>
                        <a href="{{ route('settings.helpdesk-livechat.triggers.create') }}" class="btn btn-primary">{{ __('helpdesklivechat::triggers.new') }}</a>
                    </div>
                @endif
            </div>

            @if($triggers->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $triggers->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesklivechat/js/widget-triggers-admin.js') }}?v={{ @filemtime(public_path('modules/helpdesklivechat/js/widget-triggers-admin.js')) }}"></script>
@endpush
