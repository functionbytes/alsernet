@extends('layouts.theme')

@section('title', 'Editar post')

@section('content')
    @include('core::components.card', ['title' => 'Editar post'])
    @include('blog::posts.form', ['post' => $post, 'categories' => $categories, 'tags' => $tags, 'statuses' => $statuses])
@endsection
