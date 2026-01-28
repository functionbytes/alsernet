<!-- resources/views/mailrelay/add-subscriber.blade.php -->

@extends('layouts.theme')

@section('title', 'Añadir Suscriptor')

@section('content')
    <div class="container">
        <h1>Añadir Suscriptor</h1>

        <!-- Mostrar mensaje de éxito o error -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Formulario para agregar un suscriptor -->
        <form action="{{ route('mailrelay.subscribers.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="name">Nombre del Suscriptor</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="list_id">ID de la Lista</label>
                <input type="text" name="list_id" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Añadir Suscriptor</button>
        </form>
    </div>
@endsection
