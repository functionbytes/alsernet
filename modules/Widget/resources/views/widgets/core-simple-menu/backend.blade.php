<div class="mb-3">
    <label for="widget-name">{{ trans('core/base::forms.name') }}</label>
    <input class="form-control" name="name" type="text" value="{{ $config['name'] }}">
</div>

<div class="pt-3 border-top">
    <h4 class="fs-5">{{ trans('core/base::forms.content') }}</h4>

    @if (function_exists('form_repeater'))
        {!! form_repeater('items', $config['items'], $fields) !!}
    @else
        <div class="alert alert-info">
            Form repeater not available. Install required dependencies.
        </div>
    @endif
</div>
