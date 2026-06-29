@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')

    {{-- Hero --}}
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="page-header-box">
                        <h1>{{ $transTitle ?? 'Opiniones' }}</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
                            <li class="breadcrumb-item active">{{ $transTitle ?? 'Opiniones' }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contenido (shortcode) --}}
    <div class="ck-content">
        {!! $transContent !!}
    </div>

@endsection
