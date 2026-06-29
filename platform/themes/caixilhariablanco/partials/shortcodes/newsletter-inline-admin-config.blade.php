<div class="mb-3">
    <label class="form-label">{{ __('Title') }}</label>
    <input type="text" name="title" value="{{ Arr::get($attributes, 'title') }}" class="form-control" placeholder="{{ __('Stay Updated!') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Subtitle') }}</label>
    <input type="text" name="subtitle" value="{{ Arr::get($attributes, 'subtitle') }}" class="form-control" placeholder="{{ __('Subscribe') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Description') }}</label>
    <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Join our newsletter to get the latest news and updates') }}">{{ Arr::get($attributes, 'description') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Style') }}</label>
    <select name="style" class="form-control">
        <option value="default" {{ Arr::get($attributes, 'style') == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
        <option value="card" {{ Arr::get($attributes, 'style') == 'card' ? 'selected' : '' }}>{{ __('Card Style') }}</option>
        <option value="minimal" {{ Arr::get($attributes, 'style') == 'minimal' ? 'selected' : '' }}>{{ __('Minimal Style') }}</option>
        <option value="brand" {{ Arr::get($attributes, 'style') == 'brand' ? 'selected' : '' }}>{{ __('Brand Style') }}</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Show Image') }}</label>
    <select name="show_image" class="form-control">
        <option value="no" {{ Arr::get($attributes, 'show_image') != 'yes' ? 'selected' : '' }}>{{ __('No') }}</option>
        <option value="yes" {{ Arr::get($attributes, 'show_image') == 'yes' ? 'selected' : '' }}>{{ __('Yes') }}</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Image') }}</label>
    {!! Form::mediaImage('image', Arr::get($attributes, 'image')) !!}
</div>