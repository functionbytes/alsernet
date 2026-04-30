@extends('layouts.theme')

@section('title', 'Editar post')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar post'])
@endsection

@section('content')
    @include('blog::posts.form', ['post' => $post, 'categories' => $categories, 'tags' => $tags, 'statuses' => $statuses])
@endsection
