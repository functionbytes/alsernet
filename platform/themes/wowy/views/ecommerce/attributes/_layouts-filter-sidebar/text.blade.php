@if (($attributes = $attributes->where('attribute_set_id', $set->id)) && $attributes->isNotEmpty())
    <div class="bb-product-filter-attribute-item">
        <h4 class="bb-product-filter-title">{{ $set->title }}</h4>

        <div class="bb-product-filter-content">
            <ul class="list-filter size-filter font-small">
                @foreach($attributes->where('attribute_set_id', $set->id) as $attribute)
                    <li data-slug="{{ $attribute->slug }}">
                        <label>
                            <input class="product-filter-item" type="checkbox" name="attributes[{{ $set->slug }}][]" value="{{ $attribute->id }}" {{ in_array($attribute->id, $selected) ? 'checked' : '' }}>
                            <span>{{ $attribute->title }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
