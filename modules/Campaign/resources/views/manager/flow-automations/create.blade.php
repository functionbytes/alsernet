@extends('campaign::refactor.layout')

@section('title', trans('campaign::automations.create.title'))

@push('styles')
<link rel="stylesheet" href="{{ asset('refactor/css/wizard.css') }}">
@endpush

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::automations.create.title') }}</h1>
            <p class="mc-page-subtitle">
                <a href="{{ route('manager.flow_automations.index') }}" class="mc-breadcrumb-link">{{ trans('campaign::automations.page.title') }}</a>
                <span class="mc-breadcrumb-sep">/</span>
                {{ trans('campaign::automations.create.breadcrumb') }}
            </p>
        </div>
    </div>
@endsection

@section('content')
    {{-- 2-step wizard nav --}}
    <nav class="mc-wizard-nav" id="auto-wizard-nav">
        <span class="mc-wizard-nav-item active" data-wizard-step="1">
            <span class="mc-wizard-nav-icon"><span class="material-symbols-rounded">bolt</span></span>
            <span class="mc-wizard-nav-label">{{ trans('campaign::automations.create.step_trigger') }}</span>
        </span>
        <span class="mc-wizard-nav-divider"></span>
        <span class="mc-wizard-nav-item locked" data-wizard-step="2">
            <span class="mc-wizard-nav-icon"><span class="material-symbols-rounded">tune</span></span>
            <span class="mc-wizard-nav-label">{{ trans('campaign::automations.create.step_configure') }}</span>
        </span>
    </nav>

    {{-- ================================================================
         STEP 1: Choose Trigger
         ================================================================ --}}
    <div id="step-1" class="mc-wizard-step">
        <div class="mc-wizard-header">
            <div class="mc-wizard-header-text">
                <h2 class="mc-wizard-title">{{ trans('campaign::automations.create.trigger_title') }}</h2>
                <p class="mc-wizard-desc">{{ trans('campaign::automations.create.trigger_desc') }}</p>
            </div>
            <div class="mc-wizard-header-illust">
                <svg viewBox="0 0 120 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="50" r="40" fill="var(--chart-4-bg)"/>
                    <circle cx="44" cy="40" r="14" fill="var(--color-card-bg)" stroke="var(--chart-4)" stroke-width="1.5"/>
                    <path d="M44 34v12l6-6" stroke="var(--chart-4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="76" cy="40" r="14" fill="var(--color-card-bg)" stroke="var(--chart-2)" stroke-width="1.5"/>
                    <rect x="70" y="36" width="12" height="8" rx="1" fill="var(--chart-2)" opacity="0.2"/>
                    <line x1="58" y1="40" x2="62" y2="40" stroke="var(--color-text-muted)" stroke-width="1" stroke-dasharray="2 2"/>
                    <circle cx="60" cy="70" r="14" fill="var(--color-card-bg)" stroke="var(--color-teal)" stroke-width="1.5"/>
                    <path d="M55 70l4 4 6-8" stroke="var(--color-teal)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="60" y1="54" x2="60" y2="56" stroke="var(--color-text-muted)" stroke-width="1" stroke-dasharray="2 2"/>
                </svg>
            </div>
        </div>

        <div class="mc-auto-trigger-grid">
            @foreach ($triggers as $trigger)
            <button type="button" class="mc-auto-trigger-card" data-trigger="{{ $trigger['key'] }}">
                <span class="mc-auto-trigger-icon">
                    <span class="material-symbols-rounded">{{ $trigger['icon'] }}</span>
                </span>
                <div class="mc-auto-trigger-text">
                    <span class="mc-auto-trigger-name">{{ $trigger['name'] }}</span>
                    <span class="mc-auto-trigger-desc">{{ $trigger['desc'] }}</span>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- ================================================================
         STEP 2: Configure + Name + Create (merged)
         ================================================================ --}}
    <div id="step-2" class="mc-wizard-step" style="display:none">
        <div class="mc-wizard-header">
            <div class="mc-wizard-header-text">
                <h2 class="mc-wizard-title">{{ trans('campaign::automations.create.configure_title') }}</h2>
                <p class="mc-wizard-desc" id="step2-desc"></p>
            </div>
        </div>

        <div class="mc-card mc-auto-configure-card">
            {{-- Automation name (at the top) --}}
            <div class="mc-auto-options" style="border-bottom:1px solid var(--color-border);padding-bottom:var(--space-4)">
                <div class="mc-form-group">
                    <label class="mc-form-label" for="auto-name">{{ trans('campaign::automations.create.name_label') }}</label>
                    <input type="text" id="auto-name" class="mc-form-input"
                           placeholder="{{ trans('campaign::automations.create.name_placeholder') }}">
                    <span class="mc-form-error" id="auto-name-error" style="display:none"></span>
                </div>
            </div>

            {{-- Trigger options (loaded via AJAX) --}}
            <div id="trigger-options-container">
                <div style="padding:var(--space-8);text-align:center;color:var(--color-text-muted)">
                    {{ trans('campaign::automations.common.loading') }}
                </div>
            </div>
        </div>

        <div class="mc-wizard-footer" style="margin-top:var(--space-4)">
            <button type="button" class="mc-btn mc-btn-secondary" id="step2-back">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 18, 'class' => 'mc-btn-icon-left'])
                {{ trans('campaign::automations.create.back') }}
            </button>
            <button type="button" class="mc-btn mc-btn-primary" id="step2-create">
                <span class="material-symbols-rounded" style="font-size:18px;margin-right:var(--space-1)">rocket_launch</span>
                {{ trans('campaign::automations.create.create_btn') }}
            </button>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function() {
    var selectedTrigger = '';
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Step navigation (2 steps only)
    function goToStep(step) {
        document.getElementById('step-1').style.display = step === 1 ? '' : 'none';
        document.getElementById('step-2').style.display = step === 2 ? '' : 'none';
        document.querySelectorAll('[data-wizard-step]').forEach(function(el) {
            var s = parseInt(el.dataset.wizardStep);
            el.className = 'mc-wizard-nav-item ' + (s < step ? 'completed' : s === step ? 'active' : 'locked');
        });
        document.querySelectorAll('.mc-wizard-nav-divider').forEach(function(el, i) {
            el.className = 'mc-wizard-nav-divider' + (i + 1 < step ? ' filled' : '');
        });
    }

    // Nav items clickable (go back to completed steps)
    document.querySelectorAll('[data-wizard-step]').forEach(function(nav) {
        nav.style.cursor = 'pointer';
        nav.addEventListener('click', function() {
            var target = parseInt(this.dataset.wizardStep);
            if (this.classList.contains('completed') || this.classList.contains('active')) {
                goToStep(target);
            }
        });
    });

    // Step 1: Trigger selection → auto advance to step 2
    document.querySelectorAll('[data-trigger]').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('[data-trigger]').forEach(function(c) { c.classList.remove('selected'); });
            this.classList.add('selected');
            selectedTrigger = this.dataset.trigger;

            var container = document.getElementById('trigger-options-container');
            container.innerHTML = '<div style="padding:var(--space-6);text-align:center;color:var(--color-text-muted)">{{ trans("campaign::automations.common.loading") }}</div>';

            var name = this.querySelector('.mc-auto-trigger-name').textContent;
            document.getElementById('step2-desc').textContent = name;

            fetch('{{ route("manager.flow_automations.trigger_options") }}?trigger_type=' + selectedTrigger, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                container.innerHTML = html;
                if (typeof RichSelect !== 'undefined') RichSelect.init();
                if (typeof McTagInput !== 'undefined') McTagInput.initAll(container);
                bindListChangeEvents();
                goToStep(2);
                document.getElementById('auto-name').focus();
            });
        });
    });

    // Step 2: Back
    document.getElementById('step2-back').addEventListener('click', function() { goToStep(1); });

    // Step 2: Create (validates + submits)
    document.getElementById('step2-create').addEventListener('click', function() {
        var name = document.getElementById('auto-name').value.trim();
        var nameError = document.getElementById('auto-name-error');
        var listSelect = document.querySelector('#trigger-options-container select[name="mail_list_uid"]');

        // Validate name
        if (!name) {
            nameError.textContent = '{{ trans("campaign::automations.create.name_required") }}';
            nameError.style.display = '';
            document.getElementById('auto-name').focus();
            return;
        }
        nameError.style.display = 'none';

        // Validate mail list (hidden input from RichSelect)
        var listInput = document.querySelector('#trigger-options-container input[name="mail_list_uid"]');
        if (listInput && !listInput.value) {
            var listTrigger = document.querySelector('#auto-list-select .mc-rich-select-trigger');
            if (listTrigger) {
                listTrigger.style.borderColor = 'var(--color-danger, #e74c3c)';
                listTrigger.focus();
                setTimeout(function() { listTrigger.style.borderColor = ''; }, 2000);
            }
            return;
        }

        // Validate DOB field (birthday trigger)
        var dobFieldInput = document.querySelector('#trigger-options-container input[name="options[field]"]');
        var dobFieldEl = document.getElementById('auto-dob-field-select');
        if (dobFieldInput && dobFieldEl && !dobFieldInput.value) {
            var dobTrigger = dobFieldEl.querySelector('.mc-rich-select-trigger');
            if (dobTrigger) {
                dobTrigger.style.borderColor = 'var(--color-danger, #e74c3c)';
                dobTrigger.focus();
                setTimeout(function() { dobTrigger.style.borderColor = ''; }, 2000);
            }
            return;
        }

        // Gather trigger form data (supports array names like options[tags][])
        var triggerData = {};
        document.querySelectorAll('#trigger-options-container input:not([type="checkbox"]), #trigger-options-container select').forEach(function(el) {
            if (!el.name) return;
            if (el.name.endsWith('[]')) {
                if (!triggerData[el.name]) triggerData[el.name] = [];
                triggerData[el.name].push(el.value);
            } else {
                triggerData[el.name] = el.value;
            }
        });
        document.querySelectorAll('#trigger-options-container input[type="checkbox"]:checked').forEach(function(el) {
            if (el.name) {
                if (!triggerData[el.name]) triggerData[el.name] = [];
                if (Array.isArray(triggerData[el.name])) triggerData[el.name].push(el.value);
                else triggerData[el.name] = el.value;
            }
        });

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;margin-right:var(--space-1);animation:spin 1s linear infinite">progress_activity</span> {{ trans("campaign::automations.create.creating") }}';

        var body = Object.assign({}, triggerData, { name: name, trigger_type: selectedTrigger });
        var formData = new URLSearchParams();
        Object.keys(body).forEach(function(k) {
            var v = body[k];
            if (Array.isArray(v)) v.forEach(function(item) { formData.append(k, item); });
            else formData.append(k, v);
        });

        fetch('{{ route("manager.flow_automations.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData.toString()
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(result) {
            var data = result.data;
            if (result.ok && data.status === 'success') {
                window.location.href = data.url;
                return;
            }

            var msg = '';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join(', ');
            } else if (data.message) {
                msg = data.message;
            }
            if (msg && window.McNotify) McNotify.error(msg);
            if (msg) { nameError.textContent = msg; nameError.style.display = ''; }
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;margin-right:var(--space-1)">rocket_launch</span> {{ trans("campaign::automations.create.create_btn") }}';
        })
        .catch(function(err) {
            if (window.McNotify) McNotify.error(err.message || '{{ trans("campaign::automations.common.error.generic") }}');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;margin-right:var(--space-1)">rocket_launch</span> {{ trans("campaign::automations.create.create_btn") }}';
        });
    });

    // Dynamic segment loading when list changes (works with RichSelect)
    function bindListChangeEvents() {
        var listInput = document.querySelector('#trigger-options-container input[name="mail_list_uid"]');
        var segEl = document.getElementById('auto-segment-select');
        if (!listInput || !segEl) return;

        var observer = new MutationObserver(function() {
            var listUid = listInput.value;

            // ── Reset segments ──
            var dropdown = segEl.querySelector('.mc-rich-select-dropdown');
            dropdown.innerHTML = '<div class="mc-rich-select-option selected" data-value="" data-label="{{ trans("campaign::automations.create.all_subscribers") }}">' +
                '<div class="mc-rich-select-option-icon"><span class="material-symbols-rounded">group</span></div>' +
                '<div class="mc-rich-select-option-content"><div class="mc-rich-select-option-label">{{ trans("campaign::automations.create.all_subscribers") }}</div>' +
                '<div class="mc-rich-select-option-desc">{{ trans("campaign::automations.create.segment_help") }}</div></div></div>';
            var valEl = segEl.querySelector('.mc-rich-select-value');
            if (valEl) { valEl.textContent = '{{ trans("campaign::automations.create.all_subscribers") }}'; valEl.classList.add('mc-rich-select-placeholder'); }
            var segInput = segEl.querySelector('input[name="segment_uid"]');
            if (segInput) segInput.value = '';
            if (segEl._richSelect) { delete segEl._richSelect; }

            if (typeof RichSelect !== 'undefined') RichSelect.init();

            if (!listUid) {
                return;
            }

            // ── Load segments ──
            fetch('{{ route("manager.flow_automations.segment_select") }}?list_uid=' + listUid, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(segs) {
                segs.forEach(function(s) {
                    var opt = document.createElement('div');
                    opt.className = 'mc-rich-select-option';
                    opt.dataset.value = s.uid;
                    opt.dataset.label = s.name;
                    var countLabel = s.subscribers_count !== undefined
                        ? s.subscribers_count.toLocaleString() + ' {{ trans("campaign::automations.create.subscribers_label") }}'
                        : '';
                    opt.innerHTML = '<div class="mc-rich-select-option-icon"><span class="material-symbols-rounded">filter_alt</span></div>' +
                        '<div class="mc-rich-select-option-content"><div class="mc-rich-select-option-label">' + s.name + '</div>' +
                        (countLabel ? '<div class="mc-rich-select-option-desc">' + countLabel + '</div>' : '') +
                        '</div>';
                    dropdown.appendChild(opt);
                });
                if (segEl._richSelect) { delete segEl._richSelect; }
                if (typeof RichSelect !== 'undefined') RichSelect.init();
            });
        });
        observer.observe(listInput, { attributes: true, attributeFilter: ['value'] });

        listInput.addEventListener('change', function() { observer.takeRecords(); });
    }
})();
</script>
@endsection
