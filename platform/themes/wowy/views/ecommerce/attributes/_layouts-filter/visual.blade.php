@if (($attributes = $attributes->where('attribute_set_id', $set->id)) && $attributes->isNotEmpty())
    <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item" data-type="visual">
        @include('plugins/ecommerce::themes.attributes._layouts-filter.visual')
    </div>
@endif
