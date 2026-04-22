@extends('template::layouts.default')

@php
    Theme::set('page', $page);

    $galleryImages = collect();
    if (isset($page) && $page instanceof \Modules\Page\Models\Page) {
        try {
            $galleryImages = $page->getMedia('gallery') ?? collect();
        } catch (\Exception $e) {
            // Media library not available or no gallery collection
        }
    }
@endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')


    <section class="mt-60 mb-5">
        <div class="container">
            {{-- Contenido CKEditor --}}
            @if($transContent)
            <div class="ck-content mb-4">
                {!! $transContent !!}
            </div>
            @endif

            {{-- Galería lightbox --}}
            @if($galleryImages->isNotEmpty())
            <div class="row g-3">
                @foreach($galleryImages as $media)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ $media->getUrl() }}" class="popup-image d-block overflow-hidden rounded"
                       style="height:200px;">
                        <img alt="" src="{{ $media->getUrl('thumbnail') ?: $media->getUrl() }}"
                             alt="{{ $media->name }}"
                             class="w-100 h-100 object-fit-cover"
                             loading="lazy">
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>
@endsection
