@extends('theme::layouts.manager')
@section('title', $automation->name)
@section('content')
<div class="container py-4">
    <h2>{{ $automation->name }}</h2>
    <p class="text-muted">{{ $automation->description }}</p>
    <p>Estado: <span class="badge bg-{{ $automation->status === 'active' ? 'success' : 'secondary' }}">{{ $automation->status }}</span></p>

    <h5>Workflow JSON</h5>
    <pre class="bg-light p-3 small">{{ json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

    <a href="{{ route('manager.campaigns.automations.edit', $automation->uid) }}" class="btn btn-primary">Editar</a>
    <a href="{{ route('manager.campaigns.automations.index') }}" class="btn btn-link">Volver</a>
</div>
@endsection
