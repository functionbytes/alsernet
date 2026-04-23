@php
    $columns = (int) ($columns ?? 3);
    $title   = $title ?? null;
    $items   = $items ?? [];

    $colClass = match ($columns) {
        2       => 'col-lg-6',
        4       => 'col-lg-3',
        default => 'col-lg-4',
    };
@endphp

@if(! empty($items))


<section class="projects-widget py-5">
    <div class="container">

        @if($title)
        <div class="row justify-content-center mb-4">
            <div class="col-lg-9 text-center heading6">
                <h2 class="tg-element-title wow fadeInUp">{{ $title }}</h2>
            </div>
        </div>
        @endif

        <div class="row g-4">
            @foreach($items as $item)
            <div class="{{ $colClass }} col-md-6 col-sm-6 projects-sc-item">
                <div class="project-card"
                     data-bs-toggle="modal"
                     data-bs-target="#projects-lightbox"
                     data-img="{{ $item['image'] }}"
                     data-title="{{ $item['title'] }}"
                     data-location="{{ $item['location'] }}">
                    <img src="{{ $item['image'] }}"
                         alt="{{ $item['title'] }}"
                         class="project-card-img"
                         loading="lazy">
                    <div class="project-card-overlay">
                        @if($item['title'])
                        <div class="project-card-title">{{ $item['title'] }}</div>
                        @endif
                        @if($item['location'])
                        <div class="project-card-location">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $item['location'] }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- Lightbox modal (Bootstrap) --}}
<div class="modal fade" id="projects-lightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title text-white mb-0" id="plb-title"></h6>
                    <small class="text-muted" id="plb-location"></small>
                </div>
                <button aria-label="Cerrar" type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="plb-img" src="" alt="" class="img-fluid rounded lightbox-img">
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var el = document.getElementById('projects-lightbox');
    if (! el) { return; }
    el.addEventListener('show.bs.modal', function (e) {
        var t = e.relatedTarget;
        document.getElementById('plb-img').src      = t ? t.dataset.img      : '';
        document.getElementById('plb-img').alt       = t ? t.dataset.title    : '';
        document.getElementById('plb-title').textContent    = t ? t.dataset.title    : '';
        document.getElementById('plb-location').textContent = t ? t.dataset.location : '';
    });
    el.addEventListener('hidden.bs.modal', function () {
        document.getElementById('plb-img').src = '';
    });
}());
</script>
@endif
