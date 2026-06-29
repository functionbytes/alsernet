<div class="mb-3">
    <label class="form-label" for="layout" class="form-label">{{ __('Layout') }}</label>
    {!! Form::customSelect('layout', get_product_single_layouts(), $layout, ['class' => 'form-control', 'id' => 'layout']) !!}
</div>
