@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="page-header-box">
                        <h1>{{ $transTitle ?? 'Opiniones de clientes' }}</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
                            <li class="breadcrumb-item active">Opiniones</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {!! $transContent !!}

@endsection
