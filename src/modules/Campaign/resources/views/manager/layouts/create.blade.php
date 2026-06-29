@extends('layouts.theme')
@section('title', 'Nuevo layout')
@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>Nuevo layout</h2>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.layouts.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">HTML (header/footer reusable)</label>
            <textarea name="content" rows="14" class="form-control font-monospace">{{ old('content', '<table width="100%"><tr><td>%email_content%</td></tr></table>') }}</textarea>
            <small class="text-muted">Usa <code>%email_content%</code> donde se inyectará el contenido de cada plantilla.</small>
        </div>
        <div class="form-check mb-3"><input type="checkbox" name="default" value="1" id="def" class="form-check-input"><label for="def" class="form-check-label">Layout por defecto</label></div>

        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ route('manager.layouts.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
