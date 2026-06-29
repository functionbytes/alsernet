@extends('layouts.theme')

@section('title', $campaign->name)

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">{{ $campaign->name }}</h2>
            <p class="text-muted mb-0">"{{ $campaign->subject }}" · {{ $campaign->from_name }} <code>&lt;{{ $campaign->from_email }}&gt;</code></p>
        </div>
        <span class="badge bg-{{ in_array($campaign->status, ['done','sending']) ? 'success' : 'secondary' }} fs-6">
            {{ $campaign->status }}
        </span>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card text-center"><div class="card-body">
                <div class="text-muted small">Suscriptores</div>
                <div class="h4">{{ number_format($stats['subscriber_count'] ?? 0) }}</div>
            </div></div>
        </div>
        <div class="col">
            <div class="card text-center"><div class="card-body">
                <div class="text-muted small">Entregados</div>
                <div class="h4">{{ number_format($stats['delivered_count'] ?? 0) }}</div>
            </div></div>
        </div>
        <div class="col">
            <div class="card text-center"><div class="card-body">
                <div class="text-muted small">Aperturas únicas</div>
                <div class="h4">{{ number_format($stats['unique_open_count'] ?? 0) }}</div>
            </div></div>
        </div>
        <div class="col">
            <div class="card text-center"><div class="card-body">
                <div class="text-muted small">Clicks</div>
                <div class="h4">{{ number_format($stats['click_count'] ?? 0) }}</div>
            </div></div>
        </div>
        <div class="col">
            <div class="card text-center"><div class="card-body">
                <div class="text-muted small">Bounces</div>
                <div class="h4">{{ number_format($stats['bounce_count'] ?? 0) }}</div>
            </div></div>
        </div>
    </div>

    <h5>Acciones</h5>
    <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="{{ route('manager.campaigns.recipients', $campaign->uid) }}" class="btn btn-outline-secondary">Destinatarios</a>
        <a href="{{ route('manager.campaigns.edit', $campaign->uid) }}" class="btn btn-outline-secondary">Editar</a>
        <a href="{{ route('manager.campaigns.tracking.log', $campaign->uid) }}" class="btn btn-outline-secondary">Log de envíos</a>

        @if (in_array($campaign->status, ['new','scheduled','paused']))
            <form method="post" action="{{ route('manager.campaigns.confirm', $campaign->uid) }}" class="d-inline">
                @csrf
                <button class="btn btn-success">Lanzar</button>
            </form>
        @endif
        @if ($campaign->status === 'sending')
            <form method="post" action="{{ route('manager.campaigns.pause', $campaign->uid) }}" class="d-inline">
                @csrf
                <button class="btn btn-warning">Pausar</button>
            </form>
        @endif
        @if ($campaign->status === 'paused')
            <form method="post" action="{{ route('manager.campaigns.resume', $campaign->uid) }}" class="d-inline">
                @csrf
                <button class="btn btn-success">Reanudar</button>
            </form>
        @endif

        <form method="post" action="{{ route('manager.campaigns.destroy', $campaign->uid) }}" class="d-inline"
              onsubmit="return confirm('¿Eliminar campaña?');">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Eliminar</button>
        </form>
    </div>

    @if ($campaign->last_error)
        <div class="alert alert-danger">
            <strong>Último error:</strong>
            <pre class="mb-0 small">{{ Str::limit($campaign->last_error, 1000) }}</pre>
        </div>
    @endif
@endsection
