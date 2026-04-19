<div class="mb-3">
    <label class="form-label" for="widget-name">{{ trans('core/base::forms.name') }}</label>
    <input type="text" class="form-control" name="name" value="{{ $config['name'] }}">
</div>

<div class="mb-3">
    <label class="form-label" for="widget-name">{{ trans('core/base::forms.description') }}</label>
    <textarea rows="3" class="form-control" name="description">{{ $config['description'] }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label" for="widget-name">{{ trans('core/base::forms.image') }}</label>
    {!! Form::mediaImage('image', $config['image']) !!}
</div>
