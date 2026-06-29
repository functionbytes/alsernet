@extends('template::layouts.default')

@php
    Theme::set('page', $page);
    Theme::set('pageTitle', $transTitle ?? $page->title ?? 'Página');
    Theme::set('hasBreadcrumb', true);
@endphp

@section('content')
    <section class="mt-60 mb-60">
        <div class="container">
            <div class="ck-content">
                {!! clean($transContent ?? $page->content ?? '') !!}
            </div>
        </div>
    </section>
@endsection
