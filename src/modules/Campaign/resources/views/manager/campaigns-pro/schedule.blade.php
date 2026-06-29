@php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag; @endphp
@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.wizard.schedule_title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.wizard.schedule_title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.wizard.schedule_desc') }}</p>
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
        <a href="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}" class="mc-badge">{{ trans('campaign::campaigns.wizard.step_1') }}</a>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <a href="{{ route('manager.campaigns_pro.setup', $campaign->uid) }}" class="mc-badge">{{ trans('campaign::campaigns.wizard.step_2') }}</a>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge mc-badge-teal">{{ trans('campaign::campaigns.wizard.step_schedule') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_5') }}</span>
    </div>

    <form method="POST" action="{{ route('manager.campaigns_pro.schedule', $campaign->uid) }}" id="schedule-form">
        @csrf

        <div class="mc-card" style="padding:var(--space-6)">

            <@php if(isset($errors) && $errors->has('run_at')): @endphp
                <div class="mc-alert mc-alert-danger" style="margin-bottom:var(--space-4)">{{ $message }}</div>
            @enderror

            {{-- Send now --}}
            <label class="mc-list-pick-row {{ old('send_option', 'now') === 'now' ? 'mc-list-pick-row--selected' : '' }}"
                   id="option-now-row"
                   style="display:flex;align-items:flex-start;gap:var(--space-4);padding:var(--space-5);border:1.5px solid {{ old('send_option', 'now') === 'now' ? 'var(--color-teal)' : 'var(--color-border)' }};border-radius:var(--radius-lg);cursor:pointer;transition:border-color 0.15s;margin-bottom:var(--space-3)">
                <input type="radio"
                       name="send_option"
                       value="now"
                       id="send-now"
                       {{ old('send_option', 'now') === 'now' ? 'checked' : '' }}
                       style="margin-top:3px;accent-color:var(--color-teal)">
                <div>
                    <div style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin-bottom:2px">
                        <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px;color:var(--color-teal);margin-right:4px">send</span>
                        {{ trans('campaign::campaigns.wizard.send_now') }}
                    </div>
                    <div style="font-size:var(--text-xs);color:var(--color-text-secondary)">
                        {{ trans('campaign::campaigns.wizard.send_now_desc') }}
                    </div>
                </div>
            </label>

            {{-- Schedule for later --}}
            <label class="mc-list-pick-row {{ old('send_option') === 'scheduled' ? 'mc-list-pick-row--selected' : '' }}"
                   id="option-scheduled-row"
                   style="display:flex;align-items:flex-start;gap:var(--space-4);padding:var(--space-5);border:1.5px solid {{ old('send_option') === 'scheduled' ? 'var(--color-teal)' : 'var(--color-border)' }};border-radius:var(--radius-lg);cursor:pointer;transition:border-color 0.15s">
                <input type="radio"
                       name="send_option"
                       value="scheduled"
                       id="send-scheduled"
                       {{ old('send_option') === 'scheduled' ? 'checked' : '' }}
                       style="margin-top:3px;accent-color:var(--color-teal)">
                <div style="flex:1">
                    <div style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin-bottom:2px">
                        <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px;color:var(--color-text-secondary);margin-right:4px">schedule</span>
                        {{ trans('campaign::campaigns.wizard.schedule_later') }}
                    </div>
                    <div style="font-size:var(--text-xs);color:var(--color-text-secondary);margin-bottom:var(--space-3)">
                        {{ trans('campaign::campaigns.wizard.schedule_later_desc') }}
                    </div>

                    {{-- Datetime input (shown only when scheduled selected) --}}
                    <div id="datetime-input" style="{{ old('send_option') === 'scheduled' ? '' : 'display:none' }}">
                        <label class="mc-form-label" style="font-size:var(--text-xs)">
                            {{ trans('campaign::campaigns.wizard.scheduled_at') }}
                        </label>
                        <input type="datetime-local"
                               name="run_at"
                               class="mc-form-input <@php if(isset($errors) && $errors->has('run_at')): @endphp is-invalid @enderror"
                               value="{{ old('run_at', $campaign->run_at ? $campaign->run_at->format('Y-m-d\TH:i') : '') }}"
                               style="max-width:280px">
                        <p style="font-size:var(--text-xs);color:var(--color-text-disabled);margin-top:4px">
                            {{ trans('campaign::campaigns.wizard.timezone_info', ['timezone' => config('app.timezone', 'UTC')]) }}
                        </p>
                    </div>
                </div>
            </label>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-6)">
            <a href="{{ route('manager.campaigns_pro.setup', $campaign->uid) }}" class="mc-btn mc-btn-ghost mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 14])
                {{ trans('campaign::campaigns.wizard.back') }}
            </a>
            <button type="submit" class="mc-btn mc-btn-primary">
                {{ trans('campaign::campaigns.wizard.next') }}
                <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px;margin-left:4px">arrow_forward</span>
            </button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
(function () {
    var nowRow       = document.getElementById('option-now-row');
    var scheduledRow = document.getElementById('option-scheduled-row');
    var datetimeDiv  = document.getElementById('datetime-input');
    var radios       = document.querySelectorAll('input[name="send_option"]');

    function updateUI() {
        var val = document.querySelector('input[name="send_option"]:checked').value;
        if (val === 'scheduled') {
            scheduledRow.style.borderColor = 'var(--color-teal)';
            nowRow.style.borderColor       = 'var(--color-border)';
            datetimeDiv.style.display      = 'block';
        } else {
            nowRow.style.borderColor       = 'var(--color-teal)';
            scheduledRow.style.borderColor = 'var(--color-border)';
            datetimeDiv.style.display      = 'none';
        }
    }

    radios.forEach(function (r) {
        r.addEventListener('change', updateUI);
    });
})();
</script>
@endsection
