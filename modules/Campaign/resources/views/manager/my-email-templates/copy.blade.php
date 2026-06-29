@extends('campaign::refactor.popup')

@section('content')
<form id="form-template-copy-form" action="{{ route('manager.my_email_templates.store_copy', $item->uid) }}" method="POST">
    @csrf

    <div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-5);">
        <div class="mc-alert-icon">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 18])
        </div>
        <div class="mc-alert-content">
            <div class="mc-alert-text">{{ trans('campaign::email-templates.copy.desc') }}</div>
        </div>
    </div>

    <div class="mc-form-group">
        <label class="mc-form-label">{{ trans('campaign::email-templates.fields.name') }}</label>
        <input type="text" name="name" class="mc-form-input" value="{{ $item->name }} (copy)" autofocus>
        <div class="mc-form-help">{{ trans('campaign::email-templates.field_help.name') }}</div>
        <div class="mc-form-error mc-hidden" data-error="name"></div>
    </div>

    <div class="mc-confirm-actions" style="margin-top:var(--space-5);">
        <button type="button" class="mc-btn mc-btn-secondary" onclick="McPopup.close()">{{ trans('campaign::email-templates.buttons.cancel') }}</button>
        <button type="submit" class="mc-btn mc-btn-primary">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'copy', 'size' => 16])
            {{ trans('campaign::email-templates.action.copy') }}
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function() {
    var form = document.getElementById('form-template-copy-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        form.querySelectorAll('[data-error]').forEach(function(el) { el.classList.add('mc-hidden'); el.textContent = ''; });

        var formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': formData.get('_token'), 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        }).then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(res) {
            if (res.status === 422 && res.data.errors) {
                Object.keys(res.data.errors).forEach(function(field) {
                    var errEl = form.querySelector('[data-error="' + field + '"]');
                    if (errEl) {
                        errEl.textContent = res.data.errors[field][0];
                        errEl.classList.remove('mc-hidden');
                    }
                });
            } else if (res.data.status === 'success') {
                window.McPopup.close();
                window.McNotify.success(res.data.message);
                document.dispatchEvent(new CustomEvent('list:reload'));
            }
        });
    });
})();
</script>
@endsection
