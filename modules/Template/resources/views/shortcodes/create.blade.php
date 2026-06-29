@extends('layouts.theme')

@section('title', 'Nuevo shortcode')

@section('content')

    @include('template::shortcodes.form', ['shortcode' => null])

@endsection
