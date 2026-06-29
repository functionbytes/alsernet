@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' =>  $pageTitle ])
@endsection

@section('content')

    @include('backup::schedules.partials.form', ['schedule' => $schedule])

@endsection
