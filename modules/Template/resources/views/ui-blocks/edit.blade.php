@extends('layouts.theme')

@section('page_title', 'Editar bloque: ' . $uiBlock->name)

@section('content')

    @include('core::components.card', ['title' => 'Editar bloque: ' . $uiBlock->name])
    @include('core::components.alerts')

    @include('template::ui-blocks.form', ['block' => $uiBlock])

@endsection
