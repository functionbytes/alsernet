{{--
    Shared table for both block kinds (conocimiento/prompt-base): same
    columns, only the create link and empty state text change.
    Expects: $blocks, $kind ('knowledge'|'base'), $emptyKey, $newKey.
--}}
@if($canManage)
    <div class="text-end mb-3">
        <a href="{{ route('helpdesk-ai-prompts.blocks.create', ['kind' => $kind]) }}" class="btn bg-primary-subtle text-primary">
            <i class="fas fa-plus me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.'.$newKey) }}
        </a>
    </div>
@endif

@if($blocks->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-book fa-3x mb-3 text-muted opacity-50"></i>
        <p class="text-muted mb-0">{{ __('helpdeskaiprompts::ai-prompts.'.$emptyKey) }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('helpdeskaiprompts::ai-prompts.col_name') }}</th>
                    <th>{{ __('helpdeskaiprompts::ai-prompts.col_channel_locale') }}</th>
                    <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_version') }}</th>
                    <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_active') }}</th>
                    <th>{{ __('helpdeskaiprompts::ai-prompts.col_updated') }}</th>
                    @if($canManage)
                        <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($blocks as $block)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $block->name }}</div>
                            <div class="text-muted small">{{ $block->key }}</div>
                        </td>
                        <td class="small">
                            {{ $block->channel ? __('helpdeskaiprompts::ai-prompts.channels.'.$block->channel) : __('helpdeskaiprompts::ai-prompts.channel_global') }}
                            · {{ $block->locale ?: __('helpdeskaiprompts::ai-prompts.locale_any') }}
                        </td>
                        <td class="text-center">v{{ $block->version }}</td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                <input type="checkbox" class="form-check-input ai-toggle-active" role="switch"
                                       data-url="{{ route('helpdesk-ai-prompts.blocks.toggle-active', $block) }}"
                                       @checked($block->is_active) @disabled(! $canManage)>
                            </div>
                        </td>
                        <td class="small text-muted">{{ $block->updated_at?->format('d/m/Y H:i') }}</td>
                        @if($canManage)
                            <td class="text-center">
                                <div class="dropdown">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.blocks.edit', $block) }}">{{ __('helpdeskaiprompts::ai-prompts.action_edit') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('helpdesk-ai-prompts.blocks.history', $block) }}">{{ __('helpdeskaiprompts::ai-prompts.action_history') }}</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item delete-btn" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
                                               data-url="{{ route('helpdesk-ai-prompts.blocks.destroy', $block) }}"
                                               data-title="{{ __('helpdeskaiprompts::ai-prompts.action_delete') }}: {{ $block->name }}">
                                                {{ __('helpdeskaiprompts::ai-prompts.action_delete') }}
                                            </a>
                                        </li>
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
