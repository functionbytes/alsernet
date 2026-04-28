@extends('theme::layouts.manager')

@section('title', 'Nuevo dominio de tracking')

@section('content')
    <div class="container py-4">
        <h2>Nuevo dominio de tracking</h2>
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('manager.sending-servers.tracking-domains.store') }}">
            @csrf
            <div class="form-group">
                <label>Dominio (subdominio recomendado) *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="track.example.com" required>
            </div>
            <div class="form-group">
                <label>Método de verificación</label>
                <select name="verification_method" class="form-control">
                    <option value="cname">CNAME (recomendado)</option>
                    <option value="host">HOST (registro A)</option>
                    <option value="caddy">Caddy AutoSSL</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('manager.sending-servers.tracking-domains.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
