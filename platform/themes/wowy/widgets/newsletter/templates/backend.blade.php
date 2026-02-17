<div class="form-group mb-3">
    <label class="form-label" for="widget-name">{{ trans('core/base::forms.name') }}</label>
    <input type="text" class="form-control" name="name" value="{{ $config['name'] }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Subtitle') }}</label>
    <input type="text" name="subtitle" value="{{ $config['subtitle'] }}" class="form-control" placeholder="{{ __('Subtitle') }}">
</div>
