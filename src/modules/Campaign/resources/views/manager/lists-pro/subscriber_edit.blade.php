@extends('campaign::refactor.layout')

@section('title', trans('campaign::lists.form.edit_title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::lists.form.edit_title') }}</h1>
            <p class="mc-page-subtitle">{{ $list->name }} — {{ $subscriber->email }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.lists_pro.subscribers', $list->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::lists.form.cancel') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

    <div class="mc-card" style="max-width:560px;">
        <div class="mc-card-body">
            <form id="SubscriberEditForm"
                  method="POST"
                  action="{{ route('manager.lists_pro.subscriber_update', [$list->uid, $subscriber->uid]) }}">
                @csrf

                {{-- Email --}}
                <div class="mc-form-group" style="margin-bottom:var(--space-5);">
                    <label class="mc-form-label" for="email">
                        {{ trans('campaign::lists.sub.email') }}
                    </label>
                    <input class="mc-form-input"
                           type="email"
                           id="email"
                           name="email"
                           autocomplete="email"
                           placeholder="example@domain.com"
                           value="{{ old('email', $subscriber->email) }}">
                    @error('email')
                        <div class="mc-form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- First name --}}
                <div class="mc-form-group" style="margin-bottom:var(--space-5);">
                    <label class="mc-form-label" for="first_name">
                        {{ trans('campaign::lists.sub.first_name') }}
                    </label>
                    <input class="mc-form-input"
                           type="text"
                           id="first_name"
                           name="first_name"
                           autocomplete="given-name"
                           placeholder="{{ trans('campaign::lists.sub.first_name') }}"
                           value="{{ old('first_name', $subscriber->first_name) }}">
                    @error('first_name')
                        <div class="mc-form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Last name --}}
                <div class="mc-form-group" style="margin-bottom:var(--space-5);">
                    <label class="mc-form-label" for="last_name">
                        {{ trans('campaign::lists.sub.last_name') }}
                    </label>
                    <input class="mc-form-input"
                           type="text"
                           id="last_name"
                           name="last_name"
                           autocomplete="family-name"
                           placeholder="{{ trans('campaign::lists.sub.last_name') }}"
                           value="{{ old('last_name', $subscriber->last_name) }}">
                    @error('last_name')
                        <div class="mc-form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="mc-form-group" style="margin-bottom:var(--space-6);">
                    <label class="mc-form-label" for="status">
                        {{ trans('campaign::lists.sub.status') }}
                    </label>
                    @php
                        $currentStatus = optional($pivot)->status ?? 'subscribed';
                    @endphp
                    <select class="mc-form-input mc-form-select" id="status" name="status">
                        <option value="subscribed"   {{ $currentStatus === 'subscribed'   ? 'selected' : '' }}>Subscribed</option>
                        <option value="unsubscribed" {{ $currentStatus === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                        <option value="unconfirmed"  {{ $currentStatus === 'unconfirmed'  ? 'selected' : '' }}>Unconfirmed</option>
                    </select>
                    @error('status')
                        <div class="mc-form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:flex;gap:var(--space-3);">
                    <button type="submit" class="mc-btn mc-btn-primary">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                        {{ trans('campaign::lists.form.save') }}
                    </button>
                    <a href="{{ route('manager.lists_pro.subscribers', $list->uid) }}" class="mc-btn mc-btn-secondary">
                        {{ trans('campaign::lists.form.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script>
(function () {
    var form = document.getElementById('SubscriberEditForm');
    if (!form) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]') ?
        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    var redirectUrl = '{{ route('manager.lists_pro.subscribers', $list->uid) }}';

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;

        var body = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.href = data.redirect || redirectUrl;
            } else {
                if (btn) btn.disabled = false;
            }
        })
        .catch(function () {
            // Fallback: submit normally
            form.submit();
        });
    });
})();
</script>
@endsection
