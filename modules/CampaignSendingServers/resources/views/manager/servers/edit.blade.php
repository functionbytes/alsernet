@extends('theme::layouts.manager')

@section('title', 'Editar servidor')

@section('content')
    <div class="container py-4">
        <h2>Editar servidor: {{ $server->name }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('manager.sending-servers.update', $server->uid) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="name" value="{{ old('name', $server->name) }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="status" class="form-control">
                    <option value="active" @selected($server->status === 'active')>Activo</option>
                    <option value="inactive" @selected($server->status === 'inactive')>Inactivo</option>
                </select>
            </div>

            <div class="form-group">
                <label>Email de envío por defecto</label>
                <input type="email" name="default_from_email" value="{{ old('default_from_email', $server->default_from_email) }}" class="form-control">
            </div>

            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Host SMTP</label>
                    <input type="text" name="host" value="{{ old('host', $server->host) }}" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>Puerto</label>
                    <input type="number" name="smtp_port" value="{{ old('smtp_port', $server->smtp_port) }}" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>Protocolo</label>
                    <select name="smtp_protocol" class="form-control">
                        @foreach (['tls', 'ssl', 'none'] as $p)
                            <option value="{{ $p }}" @selected($server->smtp_protocol === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Usuario SMTP</label>
                    <input type="text" name="smtp_username" value="{{ old('smtp_username', $server->smtp_username) }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Contraseña SMTP <small class="text-muted">(dejar vacío para mantener)</small></label>
                    <input type="password" name="smtp_password" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Cuota</label>
                    <input type="number" name="quota_value" value="{{ old('quota_value', $server->quota_value) }}" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Base</label>
                    <input type="number" name="quota_base" value="{{ old('quota_base', $server->quota_base) }}" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Unidad</label>
                    <select name="quota_unit" class="form-control">
                        @foreach (['second', 'minute', 'hour', 'day'] as $u)
                            <option value="{{ $u }}" @selected($server->quota_unit === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('manager.sending-servers.show', $server->uid) }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
