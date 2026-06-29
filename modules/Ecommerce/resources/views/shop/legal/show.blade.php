@extends('ecommerce::layouts.shop-wowy')

@section('title', $page->meta_title ?: $page->title)

@section('meta')
<meta name="description" content="{{ $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 160) }}">
<meta property="og:title" content="{{ $page->meta_title ?: $page->title }}">
<meta property="og:description" content="{{ $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 160) }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<link rel="canonical" href="{{ url()->current() }}">
@endsection

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ $page->title }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">

                <h1 class="mb-4 fw-bold">{{ $page->title }}</h1>

                <div class="legal-content">
                    {!! $page->content !!}
                </div>

            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
.legal-content {
    font-size: 1rem;
    line-height: 1.8;
    color: var(--color-text);
}
.legal-content h2,
.legal-content h3,
.legal-content h4 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
    color: var(--color-heading);
}
.legal-content p {
    margin-bottom: 1rem;
}
.legal-content ul,
.legal-content ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
.legal-content li {
    margin-bottom: 0.4rem;
}
.legal-content a {
    color: var(--primary-color);
    text-decoration: underline;
}
</style>
@endpush
