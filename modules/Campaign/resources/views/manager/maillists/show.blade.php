@extends('layouts.theme')

@section('title', $list->name)

@section('content')
    <div class="container py-4" style="max-width:980px">
        <h2>{{ $list->name }}</h2>
        <p class="text-muted">{{ $list->description }}</p>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center"><div class="card-body">
                    <div class="text-muted small">Suscritos</div>
                    <div class="h4">{{ number_format($subscribed) }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card text-center"><div class="card-body">
                    <div class="text-muted small">Desuscritos</div>
                    <div class="h4">{{ number_format($unsubscribed) }}</div>
                </div></div>
            </div>
        </div>

        <h5>Acciones</h5>
        <div class="mb-3">
            <a href="{{ route('manager.maillists.subscribers.index', $list->uid) }}" class="btn btn-outline-secondary">Suscriptores</a>
            <a href="{{ route('manager.maillists.segments.index', $list->uid) }}" class="btn btn-outline-secondary">Segmentos</a>
            <a href="{{ route('manager.maillists.fields', $list->uid) }}" class="btn btn-outline-secondary">🏷️ Campos / Variables</a>
            <a href="{{ route('manager.maillists.sending-servers', $list->uid) }}" class="btn btn-outline-secondary">Servidores</a>
            <a href="{{ route('manager.maillists.edit', $list->uid) }}" class="btn btn-outline-secondary">Editar</a>
            <a href="{{ route('campaign.subscribe.form', $list->uid) }}" target="_blank" class="btn btn-outline-info">Vista pública del formulario ↗</a>
        </div>

        <h5>Importar suscriptores</h5>
        <form method="post" action="{{ route('manager.maillists.import-csv', $list->uid) }}" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-9"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Importar CSV</button></div>
        </form>
        <p class="small text-muted mt-2">El CSV debe tener columna `email`. Las columnas `first_name`/`last_name` se mapean automáticamente; las demás van al JSON `attributes`.</p>
    </div>
@endsection
