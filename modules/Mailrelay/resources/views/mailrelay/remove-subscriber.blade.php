{{-- resources/views/mailrelay/remove-subscriber.blade.php --}}

@extends('layouts.theme')

@section('title', 'Eliminar Suscriptor')

@section('content')
    <div class="container">
        <h1>Eliminar Suscriptor</h1>

        <form action="{{ route('mailrelay.subscribers.unsubscribe', $subscriber->id ?? null) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="list_id">ID de la Lista</label>
                <input type="text" name="list_id" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-danger">Eliminar Suscriptor</button>
        </form>
    </div>
@endsection
