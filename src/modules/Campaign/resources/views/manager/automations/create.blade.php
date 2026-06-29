@extends('layouts.theme')
@section('title', 'Nueva automatización')
@section('content')
<div class="container py-4" style="max-width:680px">
    <h2>Nueva automatización</h2>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.automations.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>

        <div class="mb-3"><label class="form-label">Lista *</label>
            <select name="mail_list_id" class="form-select" required>
                <option value="">— seleccionar —</option>
                @foreach ($mailLists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
            </select></div>

        <div class="mb-3"><label class="form-label">Trigger *</label>
            <select name="trigger_type" class="form-select" required>
                @foreach ($triggerTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select></div>

        <button type="submit" class="btn btn-primary">Crear y editar workflow</button>
        <a href="{{ route('manager.automations.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
