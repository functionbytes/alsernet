@extends('template::layouts.homepage')

@php
    Theme::layout('homepage');
    Theme::set('page', $page);
@endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')

{!! $transContent ?? '' !!}

@endsection
