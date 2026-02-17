@extends('layouts.theme')

@section('page_title', 'Editar shortcode: ' . $shortcode->name)

@section('content')

    @include('core::components.card', ['title' => 'Editar shortcode: ' . $shortcode->name])
    @include('core::components.alerts')

    @include('template::shortcodes.form', ['shortcode' => $shortcode])

@endsection
