@extends('layouts.theme')

@section('title', 'Nuevo post')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo post'])
@endsection

@section('content')
    @include('blog::posts.form', ['post' => null, 'categories' => $categories, 'tags' => $tags, 'statuses' => $statuses])
@endsection
