@extends('layouts.theme')

@section('title', 'Nuevo post')

@section('content')
    @include('core::components.card', ['title' => 'Nuevo post'])
    @include('blog::posts.form', ['post' => null, 'categories' => $categories, 'tags' => $tags, 'statuses' => $statuses])
@endsection
