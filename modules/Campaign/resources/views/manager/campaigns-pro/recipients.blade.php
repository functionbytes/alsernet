@php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag; @endphp
@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.wizard.recipients_title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.wizard.recipients_title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.wizard.recipients_desc') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.campaigns_pro.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::campaigns.wizard.all_campaigns') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div style="max-width:720px;margin:0 auto">

    {{-- Breadcrumb / steps --}}
    <div class="mc-wizard-steps" style="display:flex;gap:var(--space-2);margin-bottom:var(--space-6);align-items:center;flex-wrap:wrap">
        <span class="mc-badge mc-badge-teal">{{ trans('campaign::campaigns.wizard.step_recipients') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_2') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_4') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_5') }}</span>
    </div>

    <form method="POST" action="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}">
        @csrf

        <div class="mc-card" style="padding:var(--space-6)">
            <h2 class="mc-card-title" style="margin-bottom:var(--space-1);font-size:var(--text-base);font-weight:var(--font-semibold)">
                {{ trans('campaign::campaigns.wizard.select_lists') }}
            </h2>
            <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:var(--space-5)">
                {{ trans('campaign::campaigns.wizard.recipients_info') }}
            </p>

            @error('list_uids')
                <div class="mc-alert mc-alert-danger" style="margin-bottom:var(--space-4)">{{ $message }}</div>
            @enderror

            @if ($lists->isEmpty())
                <div class="mc-empty-state" style="padding:var(--space-8) 0">
                    <div class="mc-empty-state-title">{{ trans('campaign::campaigns.wizard.no_lists') }}</div>
                    <a href="{{ route('manager.maillists.create') }}" class="mc-btn mc-btn-primary mc-btn-sm" style="margin-top:var(--space-4)">
                        <span class="material-symbols-rounded">add</span>
                        Create mailing list
                    </a>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                    @foreach ($lists as $list)
                        @php
                            $subCount = 0;
                            try { $subCount = $list->subscribeCount(); } catch (\Throwable $e) {}
                            $isChecked = in_array($list->uid, $selectedUids ?? []);
                        @endphp
                        <label class="mc-list-pick-row {{ $isChecked ? 'mc-list-pick-row--selected' : '' }}"
                               style="display:flex;align-items:center;gap:var(--space-4);padding:var(--space-4);border:1.5px solid {{ $isChecked ? 'var(--color-teal)' : 'var(--color-border)' }};border-radius:var(--radius-lg);cursor:pointer;transition:border-color 0.15s">
                            <input type="checkbox"
                                   name="list_uids[]"
                                   value="{{ $list->uid }}"
                                   class="mc-checkbox"
                                   {{ $isChecked ? 'checked' : '' }}
                                   style="width:18px;height:18px;flex-shrink:0;accent-color:var(--color-teal)">
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:var(--font-medium);font-size:var(--text-sm)">{{ $list->name }}</div>
                                @if ($list->description)
                                    <div style="font-size:var(--text-xs);color:var(--color-text-secondary);margin-top:2px">{{ $list->description }}</div>
                                @endif
                            </div>
                            <div style="flex-shrink:0;text-align:right">
                                <span class="mc-badge mc-badge-default">
                                    <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">group</span>
                                    {{ number_format($subCount) }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-6)">
            <a href="{{ route('manager.campaigns_pro.index') }}" class="mc-btn mc-btn-ghost mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 14])
                {{ trans('campaign::campaigns.wizard.back') }}
            </a>
            @if (! $lists->isEmpty())
                <button type="submit" class="mc-btn mc-btn-primary">
                    {{ trans('campaign::campaigns.wizard.next') }}
                    <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px;margin-left:4px">arrow_forward</span>
                </button>
            @endif
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
(function () {
    // Highlight selected rows on checkbox change
    document.querySelectorAll('input[name="list_uids[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var row = cb.closest('label');
            if (cb.checked) {
                row.style.borderColor = 'var(--color-teal)';
            } else {
                row.style.borderColor = 'var(--color-border)';
            }
        });
    });
})();
</script>
@endsection
