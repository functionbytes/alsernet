@extends('template::layouts.default')

@section('title', $transTitle ?? 'Opiniones de Clientes')
@section('description', $transDescription ?? 'Lo que nuestros clientes dicen sobre nosotros. Reseñas verificadas de Google.')

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
