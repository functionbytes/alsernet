@extends('theme::layouts.manager')

@section('title', 'Editar suscriptor')

@section('content')
<div class="container py-4" style="max-width:560px">
    <h2>{{ $subscriber->email }}</h2>
    <p class="text-muted">Lista: {{ $list->name }} · Estado actual: <strong>{{ $pivot->status ?? '—' }}</strong></p>

    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.campaigns.maillists.subscribers.update', [$list->uid, $subscriber->uid]) }}">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $subscriber->email) }}" class="form-control"></div>
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">Nombre</label>
                <input type="text" name="first_name" value="{{ old('first_name', $subscriber->first_name) }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Apellido</label>
                <input type="text" name="last_name" value="{{ old('last_name', $subscriber->last_name) }}" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Estado en esta lista</label>
            <select name="status" class="form-select">
                <option value="subscribed" @selected(($pivot->status ?? '') === 'subscribed')>Suscrito</option>
                <option value="unconfirmed" @selected(($pivot->status ?? '') === 'unconfirmed')>Sin confirmar</option>
                <option value="unsubscribed" @selected(($pivot->status ?? '') === 'unsubscribed')>Desuscrito</option>
            </select></div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('manager.campaigns.maillists.subscribers.index', $list->uid) }}" class="btn btn-link">Cancelar</a>
    </form>

    @if ($subscriber->attributes)
        <hr>
        <h6>Atributos personalizados (JSON)</h6>
        <pre class="bg-light p-3 small">{{ json_encode($subscriber->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</div>
@endsection
