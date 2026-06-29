@php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag; @endphp
@extends('campaign::refactor.layout')

@section('title', trans('campaign::campaigns.wizard.setup_title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::campaigns.wizard.setup_title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::campaigns.wizard.setup_desc') }}</p>
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
        <span class="mc-badge mc-badge-teal">{{ trans('campaign::campaigns.wizard.step_setup') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_4') }}</span>
        <span class="mc-wizard-arrow" style="color:var(--color-text-disabled)">›</span>
        <span class="mc-badge" style="color:var(--color-text-disabled)">{{ trans('campaign::campaigns.wizard.step_5') }}</span>
    </div>

    <form method="POST" action="{{ route('manager.campaigns_pro.setup', $campaign->uid) }}">
        @csrf

        {{-- Basic info --}}
        <div class="mc-card" style="padding:var(--space-6);margin-bottom:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-5)">
                {{ trans('campaign::campaigns.wizard.section_basic') }}
            </h2>

            <div class="mc-form-group" style="margin-bottom:var(--space-4)">
                <label class="mc-form-label">{{ trans('campaign::campaigns.wizard.field_name') }}</label>
                <input type="text"
                       name="title"
                       class="mc-form-input <@php if(isset($errors) && $errors->has('title')): @endphp is-invalid @enderror"
                       value="{{ old('title', $campaign->title) }}"
                       placeholder="{{ trans('campaign::campaigns.wizard.placeholder_name') }}">
                <@php if(isset($errors) && $errors->has('title')): @endphp<div class="mc-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="mc-form-group" style="margin-bottom:0">
                <label class="mc-form-label">{{ trans('campaign::campaigns.wizard.field_subject') }}</label>
                <input type="text"
                       name="subject"
                       class="mc-form-input <@php if(isset($errors) && $errors->has('subject')): @endphp is-invalid @enderror"
                       value="{{ old('subject', $campaign->subject) }}"
                       placeholder="{{ trans('campaign::campaigns.wizard.placeholder_subject') }}">
                <@php if(isset($errors) && $errors->has('subject')): @endphp<div class="mc-form-error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Sender details --}}
        <div class="mc-card" style="padding:var(--space-6);margin-bottom:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-5)">
                {{ trans('campaign::campaigns.wizard.section_sender') }}
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4)">
                <div class="mc-form-group" style="margin-bottom:0">
                    <label class="mc-form-label">{{ trans('campaign::campaigns.wizard.field_from_name') }}</label>
                    <input type="text"
                           name="from_name"
                           class="mc-form-input <@php if(isset($errors) && $errors->has('from_name')): @endphp is-invalid @enderror"
                           value="{{ old('from_name', $campaign->from_name) }}"
                           placeholder="{{ trans('campaign::campaigns.wizard.placeholder_from_name') }}">
                    <@php if(isset($errors) && $errors->has('from_name')): @endphp<div class="mc-form-error">{{ $message }}</div>@enderror
                </div>
                <div class="mc-form-group" style="margin-bottom:0">
                    <label class="mc-form-label">{{ trans('campaign::campaigns.wizard.field_from_email') }}</label>
                    <input type="email"
                           name="from_email"
                           class="mc-form-input <@php if(isset($errors) && $errors->has('from_email')): @endphp is-invalid @enderror"
                           value="{{ old('from_email', $campaign->from_email) }}"
                           placeholder="{{ trans('campaign::campaigns.wizard.placeholder_from_email') }}">
                    <@php if(isset($errors) && $errors->has('from_email')): @endphp<div class="mc-form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mc-form-group" style="margin-bottom:0">
                <label class="mc-form-label">
                    {{ trans('campaign::campaigns.wizard.field_reply_to') }}
                    <span style="color:var(--color-text-disabled);font-weight:400"> ({{ __('optional') }})</span>
                </label>
                <input type="email"
                       name="reply_to"
                       class="mc-form-input <@php if(isset($errors) && $errors->has('reply_to')): @endphp is-invalid @enderror"
                       value="{{ old('reply_to', $campaign->reply_to) }}"
                       placeholder="{{ trans('campaign::campaigns.wizard.placeholder_reply_to') }}">
                <@php if(isset($errors) && $errors->has('reply_to')): @endphp<div class="mc-form-error">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Preheader --}}
        <div class="mc-card" style="padding:var(--space-6);margin-bottom:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.wizard.preheader_text') }}
            </h2>
            <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:var(--space-4)">
                {{ trans('campaign::campaigns.wizard.preheader_desc') }}
            </p>
            <textarea name="preheader"
                      class="mc-form-input"
                      rows="2"
                      maxlength="998"
                      placeholder="{{ trans('campaign::campaigns.wizard.placeholder_preheader') }}"
                      style="resize:vertical">{{ old('preheader', $campaign->preheader) }}</textarea>
        </div>

        {{-- Advanced: tracking & DKIM --}}
        <div class="mc-card" style="padding:var(--space-6);margin-bottom:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-5)">
                {{ trans('campaign::campaigns.wizard.section_advanced_settings') }}
            </h2>
            <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:var(--space-5)">
                {{ trans('campaign::campaigns.wizard.section_advanced_settings_desc') }}
            </p>

            <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                <label style="display:flex;align-items:flex-start;gap:var(--space-3);cursor:pointer">
                    <input type="checkbox"
                           name="track_open"
                           value="1"
                           {{ old('track_open', $campaign->track_open) ? 'checked' : '' }}
                           style="margin-top:2px;accent-color:var(--color-teal)">
                    <div>
                        <div style="font-size:var(--text-sm);font-weight:var(--font-medium)">{{ trans('campaign::campaigns.wizard.track_opens') }}</div>
                        <div style="font-size:var(--text-xs);color:var(--color-text-secondary)">{{ trans('campaign::campaigns.wizard.track_opens_desc') }}</div>
                    </div>
                </label>

                <label style="display:flex;align-items:flex-start;gap:var(--space-3);cursor:pointer">
                    <input type="checkbox"
                           name="track_click"
                           value="1"
                           {{ old('track_click', $campaign->track_click) ? 'checked' : '' }}
                           style="margin-top:2px;accent-color:var(--color-teal)">
                    <div>
                        <div style="font-size:var(--text-sm);font-weight:var(--font-medium)">{{ trans('campaign::campaigns.wizard.track_clicks') }}</div>
                        <div style="font-size:var(--text-xs);color:var(--color-text-secondary)">{{ trans('campaign::campaigns.wizard.track_clicks_desc') }}</div>
                    </div>
                </label>

                <label style="display:flex;align-items:flex-start;gap:var(--space-3);cursor:pointer">
                    <input type="checkbox"
                           name="sign_dkim"
                           value="1"
                           {{ old('sign_dkim', $campaign->sign_dkim) ? 'checked' : '' }}
                           style="margin-top:2px;accent-color:var(--color-teal)">
                    <div>
                        <div style="font-size:var(--text-sm);font-weight:var(--font-medium)">{{ trans('campaign::campaigns.wizard.enable_dkim') }}</div>
                        <div style="font-size:var(--text-xs);color:var(--color-text-secondary)">{{ trans('campaign::campaigns.wizard.enable_dkim_desc') }}</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Sending Servers (informativo) --}}
        @if ($sendingServers->count() > 0)
        <div class="mc-card" style="padding:var(--space-6);margin-bottom:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-2)">
                {{ trans('campaign::campaigns.wizard.section_sending_servers') }}
            </h2>
            <p style="color:var(--color-text-secondary);font-size:var(--text-sm);margin-bottom:var(--space-4)">
                {{ trans('campaign::campaigns.wizard.hint_sending_servers_default') }}
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-2)">
                @foreach ($sendingServers as $server)
                    <span class="mc-badge mc-badge-default">
                        <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">dns</span>
                        {{ $server->name }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-6)">
            <a href="{{ route('manager.campaigns_pro.recipients', $campaign->uid) }}" class="mc-btn mc-btn-ghost mc-btn-sm">
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
