@extends('layouts.theme')
@section('title', 'Editar automatización')
@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>{{ $automation->name }}</h2>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="post" action="{{ route('manager.automations.update', $automation->uid) }}">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" value="{{ $automation->name }}" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2">{{ $automation->description }}</textarea></div>

        <div class="mb-3"><label class="form-label">Workflow JSON</label>
            <textarea name="data" rows="20" class="form-control font-monospace" style="font-size:.85rem">{{ json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
            <small class="text-muted">Estructura: {"trigger":{"type":"…","options":{}},"children":[…]}</small>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        @if ($automation->status === 'active')
            <form method="post" action="{{ route('manager.automations.disable', $automation->uid) }}" class="d-inline">@csrf<button class="btn btn-outline-warning">Pausar</button></form>
        @else
            <form method="post" action="{{ route('manager.automations.enable', $automation->uid) }}" class="d-inline">@csrf<button class="btn btn-outline-success">Activar</button></form>
        @endif
        <a href="{{ route('manager.automations.index') }}" class="btn btn-link">Volver</a>
    </form>
</div>
@endsection
