{{-- Trigger-specific configuration fields (loaded via AJAX) --}}
@php
    $triggerHints = [
        'welcome-new-subscriber' => trans('campaign::automations.create.hint_welcome'),
        'say-goodbye-subscriber' => trans('campaign::automations.create.hint_goodbye'),
        'say-happy-birthday'     => trans('campaign::automations.create.hint_birthday'),
        'subscriber-added-date'  => trans('campaign::automations.create.hint_anniversary'),
        'specific-date'          => trans('campaign::automations.create.hint_date'),
        'api-3-0'                => trans('campaign::automations.create.hint_api'),
        'weekly-recurring'       => trans('campaign::automations.create.hint_weekly'),
        'monthly-recurring'      => trans('campaign::automations.create.hint_monthly'),
        'tag-based'              => trans('campaign::automations.create.hint_tag'),
        'remove-tag'             => trans('campaign::automations.create.hint_remove_tag'),
        'attribute-update'       => trans('campaign::automations.create.hint_attribute'),
    ];
@endphp

<div class="mc-auto-options">

    {{-- Trigger info hint --}}
    @if (!empty($triggerHints[$triggerType]))
    <div class="mc-alert mc-alert-teal" style="margin-bottom:0">
        <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0">lightbulb</span>
        <span>{{ $triggerHints[$triggerType] }}</span>
    </div>
    @endif

    {{-- Mail List (rich select with icon + count) --}}
    <div class="mc-form-group">
        <label class="mc-form-label">{{ trans('campaign::automations.create.mail_list') }}</label>
        <div class="mc-rich-select" data-rich-select id="auto-list-select">
            <button class="mc-rich-select-trigger" type="button" data-rich-select-trigger>
                <span class="mc-rich-select-value mc-rich-select-placeholder">{{ trans('campaign::automations.create.select_list') }}</span>
                <span class="material-symbols-rounded mc-rich-select-arrow">expand_more</span>
            </button>
            <input type="hidden" name="mail_list_uid" data-rich-select-input>
            <div class="mc-rich-select-dropdown">
                @foreach ($lists as $list)
                <div class="mc-rich-select-option" data-value="{{ $list->uid }}" data-label="{{ $list->name }}">
                    <div class="mc-rich-select-option-icon">
                        <span class="material-symbols-rounded">contacts</span>
                    </div>
                    <div class="mc-rich-select-option-content">
                        <div class="mc-rich-select-option-label">{{ $list->name }}</div>
                        <div class="mc-rich-select-option-desc">{{ number_format(0) }} {{ trans('campaign::automations.create.subscribers_label') }}</div>
                    </div>
                </div>
                @endforeach
                @if ($lists->isEmpty())
                <div class="mc-rich-select-empty">{{ trans('campaign::automations.create.no_lists') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Segment (rich select — populated dynamically) --}}
    <div class="mc-form-group" id="auto-segment-group">
        <label class="mc-form-label">{{ trans('campaign::automations.create.segment') }}</label>
        <div class="mc-rich-select" data-rich-select id="auto-segment-select">
            <button class="mc-rich-select-trigger" type="button" data-rich-select-trigger>
                <span class="mc-rich-select-value mc-rich-select-placeholder">{{ trans('campaign::automations.create.all_subscribers') }}</span>
                <span class="material-symbols-rounded mc-rich-select-arrow">expand_more</span>
            </button>
            <input type="hidden" name="segment_uid" data-rich-select-input>
            <div class="mc-rich-select-dropdown">
                <div class="mc-rich-select-option" data-value="" data-label="{{ trans('campaign::automations.create.all_subscribers') }}">
                    <div class="mc-rich-select-option-icon">
                        <span class="material-symbols-rounded">group</span>
                    </div>
                    <div class="mc-rich-select-option-content">
                        <div class="mc-rich-select-option-label">{{ trans('campaign::automations.create.all_subscribers') }}</div>
                        <div class="mc-rich-select-option-desc">{{ trans('campaign::automations.create.segment_help') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Trigger-specific fields --}}
    @if ($triggerType === 'say-happy-birthday')
    <div class="mc-form-group">
        <label class="mc-form-label">{{ trans('campaign::automations.create.dob_field') }}</label>
        <div class="mc-rich-select" data-rich-select id="auto-dob-field-select">
            <button class="mc-rich-select-trigger" type="button" data-rich-select-trigger>
                <span class="mc-rich-select-value mc-rich-select-placeholder">{{ trans('campaign::automations.create.select_dob_field') }}</span>
                <span class="material-symbols-rounded mc-rich-select-arrow">expand_more</span>
            </button>
            <input type="hidden" name="options[field]" data-rich-select-input>
            <div class="mc-rich-select-dropdown">
                <div class="mc-rich-select-empty">{{ trans('campaign::automations.create.select_list_first') }}</div>
            </div>
        </div>
    </div>
    <div class="mc-auto-options-row">
        <div class="mc-form-group" style="flex:1">
            <label class="mc-form-label">{{ trans('campaign::automations.create.before_days') }}</label>
            <select name="options[before]" class="mc-form-input mc-form-select">
                @foreach ([0, 1, 2, 3, 5, 7, 14, 30] as $d)
                <option value="{{ $d }}">{{ $d }} {{ trans('campaign::automations.create.days') }}</option>
                @endforeach
            </select>
        </div>
        <div class="mc-form-group mc-auto-time-field">
            <label class="mc-form-label">{{ trans('campaign::automations.create.send_at') }}</label>
            <input type="time" name="options[at]" class="mc-form-input" value="08:00">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="event">

    @elseif ($triggerType === 'subscriber-added-date')
    <div class="mc-auto-options-row">
        <div class="mc-form-group" style="flex:1">
            <label class="mc-form-label">{{ trans('campaign::automations.create.delay_days') }}</label>
            <select name="options[delay]" class="mc-form-input mc-form-select">
                @foreach ([0, 1, 2, 3, 5, 7, 14, 30] as $d)
                <option value="{{ $d }}">{{ $d }} {{ trans('campaign::automations.create.days') }}</option>
                @endforeach
            </select>
        </div>
        <div class="mc-form-group mc-auto-time-field">
            <label class="mc-form-label">{{ trans('campaign::automations.create.send_at') }}</label>
            <input type="time" name="options[at]" class="mc-form-input" value="08:00">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="event">

    @elseif ($triggerType === 'specific-date')
    <div class="mc-auto-options-row">
        <div class="mc-form-group" style="flex:1">
            <label class="mc-form-label">{{ trans('campaign::automations.create.trigger_date') }}</label>
            <input type="date" name="options[date]" class="mc-form-input" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
        </div>
        <div class="mc-form-group mc-auto-time-field">
            <label class="mc-form-label">{{ trans('campaign::automations.create.send_at') }}</label>
            <input type="time" name="options[at]" class="mc-form-input" value="08:00">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="datetime">

    @elseif ($triggerType === 'weekly-recurring')
    <div class="mc-auto-options-row" style="flex-wrap:wrap">
        <div class="mc-form-group" style="flex:1 1 100%">
            <label class="mc-form-label">{{ trans('campaign::automations.create.days_of_week') }}</label>
            <div class="mc-auto-day-grid">
                @foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $k => $d)
                <label class="mc-auto-day-toggle">
                    <input type="checkbox" name="options[days_of_week][]" value="{{ $k }}">
                    <span>{{ $d }}</span>
                </label>
                @endforeach
            </div>
        </div>
        <div class="mc-form-group mc-auto-time-field">
            <label class="mc-form-label">{{ trans('campaign::automations.create.send_at') }}</label>
            <input type="time" name="options[at]" class="mc-form-input" value="08:00">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="datetime">

    @elseif ($triggerType === 'monthly-recurring')
    <div class="mc-auto-options-row" style="flex-wrap:wrap">
        <div class="mc-form-group" style="flex:1 1 100%">
            <label class="mc-form-label">{{ trans('campaign::automations.create.days_of_month') }}</label>
            <div class="mc-auto-month-grid">
                @for ($i = 1; $i <= 28; $i++)
                <label class="mc-auto-day-toggle mc-auto-day-toggle-sm">
                    <input type="checkbox" name="options[days_of_month][]" value="{{ $i }}">
                    <span>{{ $i }}</span>
                </label>
                @endfor
            </div>
        </div>
        <div class="mc-form-group mc-auto-time-field">
            <label class="mc-form-label">{{ trans('campaign::automations.create.send_at') }}</label>
            <input type="time" name="options[at]" class="mc-form-input" value="08:00">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="datetime">

    @elseif (in_array($triggerType, ['tag-based', 'remove-tag']))
    <div class="mc-form-group">
        <label class="mc-form-label">{{ trans('campaign::automations.create.tags') }}</label>
        <div class="mc-tag-input" data-mc-tag-input data-name="options[tags][]" id="auto-tag-input">
            <div class="mc-tag-input-chips" data-mc-tag-chips></div>
            <input type="text" class="mc-tag-input-field" data-mc-tag-field
                   placeholder="{{ trans('campaign::automations.create.tags_placeholder') }}">
        </div>
        <span class="mc-form-help">{{ trans('campaign::automations.create.tags_help') }}</span>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="{{ $triggerType }}">

    @elseif ($triggerType === 'api-3-0')
    @php $apiUid = uniqid(); @endphp
    <div class="mc-form-group">
        <label class="mc-form-label">{{ trans('campaign::automations.create.api_endpoint') }}</label>
        <input type="text" class="mc-form-input" readonly
               value="POST {{ url('api/v3/automation/'.$apiUid.'/execute') }}"
               style="font-family:var(--font-mono,monospace);font-size:var(--text-xs)">
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="api">
    <input type="hidden" name="options[endpoint]" value="{{ url('api/v3/automation/'.$apiUid.'/execute') }}">
    <input type="hidden" name="uid" value="{{ $apiUid }}">

    @elseif ($triggerType === 'attribute-update')
    <div class="mc-auto-options-row">
        <div class="mc-form-group" style="flex:1">
            <label class="mc-form-label">{{ trans('campaign::automations.create.field') }}</label>
            <div class="mc-rich-select" data-rich-select id="auto-field-select">
                <button class="mc-rich-select-trigger" type="button" data-rich-select-trigger>
                    <span class="mc-rich-select-value mc-rich-select-placeholder">{{ trans('campaign::automations.create.select_field') }}</span>
                    <span class="material-symbols-rounded mc-rich-select-arrow">expand_more</span>
                </button>
                <input type="hidden" name="options[field_uid]" data-rich-select-input>
                <div class="mc-rich-select-dropdown">
                    <div class="mc-rich-select-empty">{{ trans('campaign::automations.create.select_list_first') }}</div>
                </div>
            </div>
        </div>
        <div class="mc-form-group" style="flex:1">
            <label class="mc-form-label">{{ trans('campaign::automations.create.field_value') }}</label>
            <input type="text" name="options[value]" class="mc-form-input"
                   placeholder="{{ trans('campaign::automations.create.field_value_placeholder') }}">
        </div>
    </div>
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="attribute-update">

    @else
    {{-- welcome-new-subscriber, say-goodbye-subscriber, woo-abandoned-cart --}}
    <input type="hidden" name="options[key]" value="{{ $triggerType }}">
    <input type="hidden" name="options[type]" value="{{ $triggerType === 'woo-abandoned-cart' ? 'woo-abandoned-cart' : 'list-subscription' }}">
    @endif
</div>
