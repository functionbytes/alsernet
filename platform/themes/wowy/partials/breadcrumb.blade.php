<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ url('/') }}">{{ __('Inicio') }}</a>
            <span></span>
            <div class="breadcrumb-item d-inline-block active">
                <i>{{ $transTitle ?? $page->title ?? 'Página' }}</i>
            </div>
        </div>
    </div>
</div>
