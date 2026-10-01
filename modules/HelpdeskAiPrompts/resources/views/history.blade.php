@extends('layouts.theme')

@section('title', __('helpdeskaiprompts::ai-prompts.history_title'))

@section('content')
    @include('core::components.card', ['title' => __('helpdeskaiprompts::ai-prompts.history_title')])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">{{ $subjectLabel }}</h5>
                <p class="small mb-0 text-muted">{{ __('helpdeskaiprompts::ai-prompts.history_subtitle') }}</p>
            </div>

            <div class="card-body">
                @if($versions->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-clock-rotate-left fa-3x mb-3 text-muted opacity-50"></i>
                        <p class="text-muted mb-0">{{ __('helpdeskaiprompts::ai-prompts.no_versions') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('helpdeskaiprompts::ai-prompts.th_version') }}</th>
                                    <th>{{ __('helpdeskaiprompts::ai-prompts.th_date') }}</th>
                                    <th>{{ __('helpdeskaiprompts::ai-prompts.th_author') }}</th>
                                    <th>{{ __('helpdeskaiprompts::ai-prompts.th_changes') }}</th>
                                    <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $version)
                                    @php $changes = $diffs[$version->id] ?? []; @endphp
                                    <tr>
                                        <td>
                                            v{{ $version->version }}
                                            @if($version->version === $currentVersion)
                                                <span class="badge bg-success-subtle text-success ms-1">{{ __('helpdeskaiprompts::ai-prompts.current_version_badge') }}</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $version->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="small">{{ \App\Models\User::find($version->created_by)?->name ?? __('helpdeskaiprompts::ai-prompts.unknown_author') }}</td>
                                        <td class="small">
                                            @if($changes === [])
                                                <span class="text-muted">{{ __('helpdeskaiprompts::ai-prompts.no_changes') }}</span>
                                            @else
                                                <ul class="mb-0 ps-3">
                                                    @foreach($changes as $change)
                                                        <li>
                                                            <span class="fw-semibold">{{ $change['field'] }}</span>:
                                                            <span class="text-danger text-decoration-line-through">{{ $change['before'] ?: '—' }}</span>
                                                            → <span class="text-success">{{ $change['after'] ?: '—' }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($version->version !== $currentVersion)
                                                <button type="button" class="btn btn-outline-primary btn-sm ai-restore-btn" data-bs-toggle="modal" data-bs-target="#restore-modal"
                                                        data-url="{{ ($restoreRoute)($version) }}" data-version="{{ $version->version }}">
                                                    {{ __('helpdeskaiprompts::ai-prompts.btn_restore') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @isset($runs)
                <div class="card-body border-top">
                    <h6 class="fw-semibold mb-3">{{ __('helpdeskaiprompts::ai-prompts.actions.runs_title') }}</h6>
                    @if($runs->isEmpty())
                        <p class="text-muted small mb-0">{{ __('helpdeskaiprompts::ai-prompts.actions.runs_empty') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('helpdeskaiprompts::ai-prompts.th_date') }}</th>
                                        <th>{{ __('helpdeskaiprompts::ai-prompts.actions.run_source') }}</th>
                                        <th>{{ __('helpdeskaiprompts::ai-prompts.actions.run_status') }}</th>
                                        <th class="text-end">{{ __('helpdeskaiprompts::ai-prompts.actions.run_latency') }}</th>
                                        <th>{{ __('helpdeskaiprompts::ai-prompts.actions.run_args') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($runs as $run)
                                        @php $badge = ['ok' => 'success', 'denied' => 'warning', 'error' => 'danger'][$run->status] ?? 'secondary'; @endphp
                                        <tr>
                                            <td class="small text-muted">{{ $run->created_at?->format('d/m/Y H:i:s') }}</td>
                                            <td class="small">{{ $run->source }}</td>
                                            <td>
                                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ __('helpdeskaiprompts::ai-prompts.actions.status_'.$run->status) }}</span>
                                                @if($run->error)<div class="small text-muted">{{ $run->error }}</div>@endif
                                            </td>
                                            <td class="small text-end">{{ $run->latency_ms }} ms</td>
                                            <td class="small"><code>{{ collect((array) $run->args_summary)->map(fn ($v, $k) => $k.'='.(is_scalar($v) ? var_export($v, true) : json_encode($v)))->implode(', ') ?: '—' }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endisset

            <div class="card-footer bg-white border-top">
                <a href="{{ $backRoute }}" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i>{{ __('helpdeskaiprompts::ai-prompts.back') }} {{ $backLabel }}</a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restore-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="restore-form" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('helpdeskaiprompts::ai-prompts.modal_restore_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" id="restore-modal-body"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('helpdeskaiprompts::ai-prompts.modal_restore_confirm') }}</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const restoreBodyTemplate = @json(__('helpdeskaiprompts::ai-prompts.modal_restore_body'));

            $('.ai-restore-btn').on('click', function () {
                const url = $(this).data('url');
                const version = $(this).data('version');
                $('#restore-form').attr('action', url);
                $('#restore-modal-body').text(restoreBodyTemplate.replace(':version', version));
            });
        });
    </script>
@endpush
