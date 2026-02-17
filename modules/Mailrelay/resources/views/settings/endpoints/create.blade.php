@extends('layouts.theme')

@section('title', 'Nuevo endpoint')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Nuevo endpoint</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.endpoints.index') }}">Endpoints</a></li>
                    <li class="breadcrumb-item active">Nuevo</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.endpoints.store') }}" method="POST">
        @csrf
        @include('mailrelay::settings.endpoints._form')
    </form>
</div>
@endsection
