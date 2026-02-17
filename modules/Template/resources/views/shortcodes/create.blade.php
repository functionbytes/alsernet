@extends('layouts.theme')

@section('page_title', 'Nuevo shortcode')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo shortcode'])
    @include('core::components.alerts')

    @include('template::shortcodes.form', ['shortcode' => null])

@endsection
