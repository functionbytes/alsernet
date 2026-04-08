@extends('layouts.theme')

@section('title', 'Editar shortcode: ' . $shortcode->name)

@section('content')

    @include('template::shortcodes.form', ['shortcode' => $shortcode, 'themeCssUrls' => $themeCssUrls, 'themeJsUrls' => $themeJsUrls])

@endsection
