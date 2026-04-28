@extends('theme::layouts.manager')

@section('title', 'Nuevo dominio')

@section('content')
    <div class="container py-4">
        <h2>Nuevo dominio de envío</h2>
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('manager.sending-servers.domains.store') }}">
            @csrf
            <div class="form-group">
                <label>Dominio *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="example.com" required>
            </div>
            <div class="form-group">
                <label>Servidor de envío</label>
                <select name="sending_server_id" class="form-control">
                    <option value="">Sin asociar</option>
                    @foreach ($servers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->getTypeName() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="signing_enabled" value="1" id="signing_enabled" class="form-check-input">
                <label for="signing_enabled" class="form-check-label">Activar firma DKIM</label>
            </div>
            <div class="form-group">
                <label>Selector DKIM</label>
                <input type="text" name="dkim_selector" value="{{ old('dkim_selector', 'mail') }}" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('manager.sending-servers.domains.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
