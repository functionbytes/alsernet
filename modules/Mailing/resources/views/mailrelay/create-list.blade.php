<!-- resources/views/mailrelay/create-list.blade.php -->

@extends('layouts.theme')

@section('title', 'Crear Lista de Suscriptores')

@section('content')
    <div class="container">
        <h1>Crear Nueva Lista de Suscriptores</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('mailrelay.create-list') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="list_name">Nombre de la Lista</label>
                <input type="text" name="list_name" id="list_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <input type="text" name="description" id="description" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Crear Lista</button>
        </form>
    </div>
@endsection
