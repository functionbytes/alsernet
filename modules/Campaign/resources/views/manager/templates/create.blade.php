@extends('theme::layouts.manager')

@section('title', 'Nueva plantilla')

@section('content')
    <div class="container py-4" style="max-width:680px">
        <h2>Nueva plantilla</h2>

        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('manager.campaigns.templates.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Asunto</label><input type="text" name="subject" value="{{ old('subject') }}" class="form-control"></div>
            <div class="mb-3">
                <label class="form-label">Layout</label>
                <select name="layout_id" class="form-select">
                    <option value="">— sin layout —</option>
                    @foreach ($layouts as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">HTML inicial</label>
                <textarea name="html" rows="10" class="form-control font-monospace">{{ old('html', '<h1>Hola {{FIRST_NAME}}</h1><p>Tu contenido aquí…</p>') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Crear y editar</button>
            <a href="{{ route('manager.campaigns.templates.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
