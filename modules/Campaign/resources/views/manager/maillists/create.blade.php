@extends('layouts.theme')

@section('title', 'Nueva lista')

@section('content')
    <div class="container py-4" style="max-width:680px">
        <h2>Nueva lista de correo</h2>

        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('manager.maillists.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>

            <div class="row mb-3">
                <div class="col-md-6"><label class="form-label">From email</label><input type="email" name="from_email" value="{{ old('from_email') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">From name</label><input type="text" name="from_name" value="{{ old('from_name') }}" class="form-control"></div>
            </div>

            <div class="mb-3"><label class="form-label">Asunto por defecto</label><input type="text" name="default_subject" value="{{ old('default_subject') }}" class="form-control"></div>

            <div class="form-check mb-2"><input type="checkbox" name="subscribe_confirmation" value="1" id="dco" checked class="form-check-input"><label for="dco" class="form-check-label">Doble opt-in (email de confirmación)</label></div>
            <div class="form-check mb-2"><input type="checkbox" name="send_welcome_email" value="1" id="we" class="form-check-input"><label for="we" class="form-check-label">Enviar email de bienvenida</label></div>

            <button type="submit" class="btn btn-primary">Crear</button>
            <a href="{{ route('manager.maillists.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
