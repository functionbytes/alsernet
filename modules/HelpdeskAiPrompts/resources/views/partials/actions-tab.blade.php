{{--
    Pestaña "Acciones" del índice.
    Espera: $actionsPanel (stats + rows de ActionPanel::forIndex), $canManage.
--}}
@php
    $t = fn (string $key, array $replace = []) => __('helpdeskaiprompts::ai-prompts.actions.'.$key, $replace);
    $actionStats = $actionsPanel['stats'];
    $typeBadges = ['builtin' => 'secondary', 'bridge' => 'primary', 'http' => 'info'];
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-light-primary h-100 mb-0">
            <div class="card-body">
                <h6 class="card-title mb-1">{{ $t('stat_active') }}</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($actionStats['active_actions']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-light-secondary h-100 mb-0">
            <div class="card-body">
                <h6 class="card-title mb-1">{{ $t('stat_runs') }}</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($actionStats['runs_30d']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-light-warning h-100 mb-0">
            <div class="card-body">
                <h6 class="card-title mb-1">{{ $t('stat_errors') }}</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($actionStats['error_rate'], 1) }}%</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card bg-light-success h-100 mb-0">
            <div class="card-body">
                <h6 class="card-title mb-1">{{ $t('stat_denied') }}</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($actionStats['denied_rate'], 1) }}%</h3>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted small mb-0">{{ $t('intro') }}</p>
    @if($canManage)
        <div class="dropdown">
            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-plus me-1"></i>{{ $t('new_action') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.actions.create', ['type' => 'bridge']) }}">{{ $t('new_bridge') }}</a></li>
                <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.actions.create', ['type' => 'http']) }}">{{ $t('new_http') }}</a></li>
            </ul>
        </div>
    @endif
</div>

@if($actionsPanel['rows'] === [])
    <div class="text-center py-5">
        <i class="fas fa-bolt fa-3x mb-3 text-muted opacity-50"></i>
        <p class="text-muted mb-0">{{ $t('empty') }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ $t('col_action') }}</th>
                    <th>{{ $t('col_type') }}</th>
                    <th>{{ $t('col_target') }}</th>
                    <th>{{ $t('col_ownership') }}</th>
                    <th>{{ $t('col_write') }}</th>
                    <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_active') }}</th>
                    <th class="text-center">{{ $t('col_uses') }}</th>
                    @if($canManage)
                        <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($actionsPanel['rows'] as $row)
                    @php
                        $action = $row['action'];
                        $isBuiltin = $action->type === 'builtin';
                        $ownership = $action->rules['ownership'] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $action->name }}</div>
                            <div class="text-muted small">{{ $action->key }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $typeBadges[$action->type] ?? 'secondary' }}-subtle text-{{ $typeBadges[$action->type] ?? 'secondary' }}">{{ $t('type_'.$action->type) }}</span>
                        </td>
                        <td class="small">
                            @if($row['target'])
                                <code>{{ $row['target'] }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($ownership)
                                {{ $t('ownership_'.$ownership) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($isBuiltin)
                                <span class="text-muted">—</span>
                            @elseif($row['write'])
                                <span class="badge bg-warning-subtle text-warning">{{ $t('write_confirm') }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">{{ $t('read') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                <input type="checkbox" class="form-check-input ai-toggle-active" role="switch"
                                       aria-label="{{ __('helpdeskaiprompts::ai-prompts.col_active') }}: {{ $action->name }}"
                                       data-url="{{ route('helpdesk-ai-prompts.actions.toggle-active', $action) }}"
                                       @checked($action->is_active) @disabled(! $canManage)>
                            </div>
                        </td>
                        <td class="text-center">{{ number_format($row['uses']) }}</td>
                        @if($canManage)
                            <td class="text-center">
                                <div class="dropdown">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="{{ __('helpdeskaiprompts::ai-prompts.col_actions') }}">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.actions.edit', $action) }}">{{ __('helpdeskaiprompts::ai-prompts.action_edit') }}</a></li>
                                        @unless($isBuiltin)
                                            <li>
                                                <a class="dropdown-item ai-action-test" href="#"
                                                   data-url="{{ route('helpdesk-ai-prompts.actions.test', $action) }}"
                                                   data-name="{{ $action->name }}"
                                                   data-active="{{ $action->is_active ? 1 : 0 }}"
                                                   data-write="{{ $row['write'] ? 1 : 0 }}"
                                                   data-params="{{ json_encode($row['test_params'], JSON_UNESCAPED_UNICODE) }}">
                                                    {{ __('helpdeskaiprompts::ai-prompts.action_test') }}
                                                </a>
                                            </li>
                                        @endunless
                                        <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.actions.history', $action) }}">{{ __('helpdeskaiprompts::ai-prompts.action_history') }}</a></li>
                                        @unless($isBuiltin)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item delete-btn" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                   data-url="{{ route('helpdesk-ai-prompts.actions.destroy', $action) }}"
                                                   data-title="{{ __('helpdeskaiprompts::ai-prompts.action_delete') }}: {{ $action->name }}">
                                                    {{ __('helpdeskaiprompts::ai-prompts.action_delete') }}
                                                </a>
                                            </li>
                                        @endunless
                                    </ul>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
