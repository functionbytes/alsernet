@extends('theme::layouts.manager')
@section('title', 'Editar layout')
@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>{{ $layout->name }}</h2>

    <form method="post" action="{{ route('manager.campaigns.layouts.update', $layout->uid) }}">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" value="{{ $layout->name }}" class="form-control"></div>
        <div class="mb-3"><label class="form-label">HTML</label>
            <textarea name="content" rows="14" class="form-control font-monospace">{{ $layout->content }}</textarea>
        </div>
        <div class="form-check mb-3"><input type="checkbox" name="default" value="1" id="def" class="form-check-input" @checked($layout->default)><label for="def" class="form-check-label">Layout por defecto</label></div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('manager.campaigns.layouts.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
