@if(!empty($images))
@php $colClass = 'col-' . (12 / max(1, min(6, $columns ?? 3))); @endphp
<div class="gallery-grid row g-3 my-3">
    @foreach($images as $imageUrl)
    <div class="{{ $colClass }}">
        @if($lightbox ?? true)
        <a href="{{ $imageUrl }}"
           class="d-block overflow-hidden rounded"
           data-fancybox="gallery-{{ md5(implode(',', $images)) }}">
        @else
        <div class="d-block overflow-hidden rounded">
        @endif
            <img src="{{ $imageUrl }}"
                 alt="{{ pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_FILENAME) ?: 'Imagen ' . $loop->iteration }}"
                 class="img-fluid w-100 hover-scale"
                 style="height:220px;object-fit:cover;"
                 loading="lazy">
        @if($lightbox ?? true)
        </a>
        @else
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif
