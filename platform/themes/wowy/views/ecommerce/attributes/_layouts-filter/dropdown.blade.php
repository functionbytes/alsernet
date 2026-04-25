@if (($attributes = $attributes->where('attribute_set_id', $set->id)) && $attributes->isNotEmpty())
    <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item" data-type="dropdown">
        @include('plugins/ecommerce::themes.attributes._layouts-filter.dropdown')
    </div>
@endif
