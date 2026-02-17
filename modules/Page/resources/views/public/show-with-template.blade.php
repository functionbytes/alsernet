@extends('template::layouts.default')

@section('title', $page->seo_title ?? $page->title)
@section('description', $page->seo_description ?? $page->description)
@section('keywords', $page->seo_keywords)

@section('content')
<div class="page-content" style="padding: 2rem 0;">
    @if($page->content)
        {!! $page->content !!}
    @else
        <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
    @endif
</div>
@endsection
