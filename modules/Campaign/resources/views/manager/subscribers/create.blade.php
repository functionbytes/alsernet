@extends('theme::layouts.manager')

@section('title', 'Añadir suscriptor')

@section('content')
<div class="container py-4" style="max-width:560px">
    <h2>Añadir suscriptor</h2>
    <p class="text-muted">Lista: {{ $list->name }}</p>

    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.campaigns.maillists.subscribers.store', $list->uid) }}">
        @csrf
        <div class="mb-3"><label class="form-label">Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">Nombre</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Apellido</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Estado</label>
            <select name="status" class="form-select">
                <option value="subscribed">Suscrito</option>
                <option value="unconfirmed">Sin confirmar</option>
                <option value="unsubscribed">Desuscrito</option>
            </select></div>

        @if ($fields->isNotEmpty())
            <h5>Campos personalizados</h5>
            @foreach ($fields as $f)
                @if (! in_array($f->tag, ['EMAIL', 'FIRST_NAME', 'LAST_NAME']))
                    <div class="mb-3"><label class="form-label">{{ $f->label }}</label>
                        <input type="text" name="attributes[{{ $f->tag }}]" class="form-control"></div>
                @endif
            @endforeach
        @endif

        <button type="submit" class="btn btn-primary">Añadir</button>
        <a href="{{ route('manager.campaigns.maillists.subscribers.index', $list->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
