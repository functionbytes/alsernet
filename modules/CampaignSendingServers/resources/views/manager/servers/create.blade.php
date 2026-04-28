@extends('theme::layouts.manager')

@section('title', 'Nuevo servidor de envío')

@section('content')
    <div class="container py-4">
        <h2>Nuevo servidor de envío</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('manager.sending-servers.store') }}">
            @csrf

            <div class="form-group">
                <label>Tipo de proveedor</label>
                <select name="type" class="form-control" required>
                    @foreach ($providers as $key => $meta)
                        <option value="{{ $key }}" @selected($key === $type)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Nombre interno *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Email de envío por defecto</label>
                <input type="email" name="default_from_email" value="{{ old('default_from_email') }}" class="form-control">
            </div>

            <hr>
            <h5>SMTP / Sendmail (rellenar según el tipo)</h5>

            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Host SMTP</label>
                    <input type="text" name="host" value="{{ old('host') }}" class="form-control" placeholder="smtp.example.com o email-smtp.us-east-1.amazonaws.com">
                </div>
                <div class="form-group col-md-2">
                    <label>Puerto</label>
                    <input type="number" name="smtp_port" value="{{ old('smtp_port', 587) }}" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>Protocolo</label>
                    <select name="smtp_protocol" class="form-control">
                        <option value="tls">tls</option>
                        <option value="ssl">ssl</option>
                        <option value="none">ninguno</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Usuario SMTP</label>
                    <input type="text" name="smtp_username" value="{{ old('smtp_username') }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Contraseña SMTP</label>
                    <input type="password" name="smtp_password" value="" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Región AWS (sólo Amazon SES)</label>
                <input type="text" name="aws_region" value="{{ old('aws_region') }}" class="form-control" placeholder="us-east-1">
            </div>

            <div class="form-group">
                <label>Path Sendmail (sólo Sendmail)</label>
                <input type="text" name="sendmail_path" value="{{ old('sendmail_path', '/usr/sbin/sendmail') }}" class="form-control">
            </div>

            <hr>
            <h5>Cuota / rate limit</h5>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Cantidad máx</label>
                    <input type="number" name="quota_value" value="{{ old('quota_value', 60) }}" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Cada (base)</label>
                    <input type="number" name="quota_base" value="{{ old('quota_base', 1) }}" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Unidad</label>
                    <select name="quota_unit" class="form-control">
                        <option value="second">segundo</option>
                        <option value="minute" selected>minuto</option>
                        <option value="hour">hora</option>
                        <option value="day">día</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('manager.sending-servers.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
