<div class="mb-3">
    <label class="form-label">{{ __('Product category ID') }}</label>
    {!! Form::customSelect('category_id', $categories, Arr::get($attributes, 'category_id')) !!}
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Limit') }}</label>
    <input type="number" name="limit" value="{{ Arr::get($attributes, 'limit', 8) }}" class="form-control" placeholder="{{ __('Limit') }}">
</div>
