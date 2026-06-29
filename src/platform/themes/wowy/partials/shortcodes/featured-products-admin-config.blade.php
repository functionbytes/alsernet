<div class="mb-3">
    <label class="form-label">{{ __('Title') }}</label>
    <input type="text" name="title" value="{{ Arr::get($attributes, 'title') }}" class="form-control" placeholder="Title">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Subtitle') }}</label>
    <input type="text" name="subtitle" value="{{ Arr::get($attributes, 'subtitle') }}" class="form-control" placeholder="{{ __('Subtitle') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Limit') }}</label>
    <input type="number" name="limit" value="{{ Arr::get($attributes, 'limit', 8) }}" class="form-control" placeholder="{{ __('Limit') }}">
</div>

{!! Theme::partial('shortcodes.includes.autoplay-settings', compact('attributes')) !!}
