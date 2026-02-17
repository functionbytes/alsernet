@extends('layouts.theme')

@section('title', 'Nueva variable')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Nueva variable</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.variables.index') }}">Variables</a></li>
                    <li class="breadcrumb-item active">Nueva</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.variables.store') }}" method="POST">
        @csrf
        @include('mailrelay::settings.variables._form')
    </form>
</div>
@endsection
