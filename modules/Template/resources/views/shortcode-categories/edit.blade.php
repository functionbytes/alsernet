@extends('layouts.theme')

@section('title', 'Editar categoria: ' . $shortcodeCategory->label)

@section('content')
    @include('template::shortcode-categories.form', compact('shortcodeCategory'))
@endsection
