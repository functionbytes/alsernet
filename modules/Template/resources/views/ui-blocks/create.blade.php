@extends('layouts.theme')

@section('page_title', 'Nuevo bloque UI')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo bloque UI'])
    @include('core::components.alerts')

    @include('template::ui-blocks.form', ['block' => null])

@endsection
