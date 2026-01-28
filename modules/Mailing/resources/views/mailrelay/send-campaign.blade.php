{{-- resources/views/mailrelay/send-campaign.blade.php --}}

@extends('layouts.theme')

@section('title', 'Enviar Campaña')

@section('content')
    <div class="container">
        <h1>Enviar Campaña</h1>

        <form action="{{ route('mailrelay.campaigns.send', $campaign->id ?? null) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="campaign_id">ID de la Campaña</label>
                <input type="text" name="campaign_id" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success">Enviar Campaña</button>
        </form>
    </div>
@endsection
