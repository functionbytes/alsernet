@extends('template::layouts.homepage')

@php
    Theme::set('pageTitle', $page->title ?? 'Inicio');
    Theme::set('hasBreadcrumb', false);
@endphp

@section('content')
    <div class="container">
        <div class="ck-content">
            {!! clean($transContent ?? $page->content ?? '') !!}
        </div>
    </div>
@endsection
