@extends('layouts.theme')

@section('title', 'Nueva categoria')

@section('content')
    @include('template::shortcode-categories.form', ['shortcodeCategory' => null])
@endsection
