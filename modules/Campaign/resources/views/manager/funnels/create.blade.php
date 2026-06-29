@extends('campaign::refactor.layout')

@section('title', trans('campaign::funnels.create.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::funnels.create.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::funnels.create.subtitle') }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.funnels.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                {{ trans('campaign::funnels.buttons.cancel') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form id="funnel-create-form" method="POST" action="{{ route('manager.funnels.store') }}">
        @csrf

        <div class="mc-card" style="margin-bottom:var(--space-5);max-width:640px;">
            <div class="mc-card-header">
                <h3 class="mc-card-title">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 18])
                    {{ trans('campaign::funnels.banner.title') }}
                </h3>
            </div>
            <div style="padding:0 var(--space-5) var(--space-5);">
                {{-- Funnel name --}}
                <div class="mc-form-group">
                    <label class="mc-form-label" for="funnel-name">
                        {{ trans('campaign::funnels.create.name') }}
                        <span style="color:var(--color-error)">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="funnel-name"
                           class="mc-form-input"
                           value="{{ old('name') }}"
                           autofocus
                           required>
                    <div class="mc-form-error mc-hidden" data-error="name"></div>
                </div>

                {{-- Mailing list (optional) --}}
                <div class="mc-form-group" style="margin-top:var(--space-4);">
                    <label class="mc-form-label" for="mail_list_uid">
                        {{ trans('campaign::funnels.create.list') }}
                    </label>
                    <select name="mail_list_uid" id="mail_list_uid" class="mc-form-input mc-form-select">
                        <option value="">{{ trans('campaign::funnels.create.select_list') }}</option>
                        @foreach ($lists as $list)
                            <option value="{{ $list->uid }}" {{ old('mail_list_uid') === $list->uid ? 'selected' : '' }}>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="mc-form-error mc-hidden" data-error="mail_list_uid"></div>
                </div>
            </div>
        </div>

        <div class="mc-form-actions" style="display:flex;justify-content:flex-end;gap:var(--space-3);max-width:640px;">
            <a href="{{ route('manager.funnels.index') }}" class="mc-btn mc-btn-secondary">
                {{ trans('campaign::funnels.buttons.cancel') }}
            </a>
            <button type="submit" id="funnel-create-submit" class="mc-btn mc-btn-primary">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                {{ trans('campaign::funnels.buttons.save') }}
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
(function() {
    var form      = document.getElementById('funnel-create-form');
    var submitBtn = document.getElementById('funnel-create-submit');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        form.querySelectorAll('[data-error]').forEach(function(el) {
            el.classList.add('mc-hidden');
            el.textContent = '';
        });

        var formData = new FormData(form);
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': formData.get('_token'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(res) {
            if (res.status === 422 && res.data.errors) {
                Object.keys(res.data.errors).forEach(function(field) {
                    var errEl = form.querySelector('[data-error="' + field + '"]');
                    if (errEl) {
                        errEl.textContent = res.data.errors[field][0];
                        errEl.classList.remove('mc-hidden');
                    }
                });
                submitBtn.disabled = false;
            } else if (res.data.status === 'success') {
                if (window.McNotify && res.data.message) window.McNotify.success(res.data.message);
                window.location.href = res.data.redirect || '{{ route('manager.funnels.index') }}';
            } else {
                if (window.McNotify && res.data.message) window.McNotify.error(res.data.message);
                submitBtn.disabled = false;
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
        });
    });
})();
</script>
@endsection
