@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' =>  $pageTitle ])

    @include('backup::schedules.partials.form', ['schedule' => $schedule])

@endsection
