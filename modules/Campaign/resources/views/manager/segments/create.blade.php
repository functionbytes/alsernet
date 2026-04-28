@extends('theme::layouts.manager')
@section('title', 'Nuevo segmento')
@section('content')
<div class="container py-4" style="max-width:880px" x-data="{ rows: [{ field: 'email', operator: 'contains', value: '' }] }">
    <h2>Nuevo segmento</h2>
    <p class="text-muted">Lista: {{ $list->name }}</p>

    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.campaigns.maillists.segments.store', $list->uid) }}">
        @csrf
        <div class="mb-3"><label class="form-label">Nombre *</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Match</label>
            <select name="matching" class="form-select">
                <option value="and">TODAS las condiciones (AND)</option>
                <option value="or">CUALQUIERA de las condiciones (OR)</option>
            </select></div>

        <h5>Condiciones</h5>
        <template x-for="(row, i) in rows" :key="i">
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-md-3">
                    <select :name="`conditions[${i}][field]`" x-model="row.field" class="form-select">
                        <option value="email">email</option>
                        <option value="first_name">first_name</option>
                        <option value="last_name">last_name</option>
                        <option value="open_count">open_count</option>
                        <option value="click_count">click_count</option>
                        <option value="tag">tag</option>
                        <option value="subscribed_at">subscribed_at</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select :name="`conditions[${i}][operator]`" x-model="row.operator" class="form-select">
                        <option value="equals">equals</option>
                        <option value="not_equals">not equals</option>
                        <option value="contains">contains</option>
                        <option value="not_contains">not contains</option>
                        <option value="gt">&gt;</option>
                        <option value="lt">&lt;</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" :name="`conditions[${i}][value]`" x-model="row.value" class="form-control" placeholder="valor">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" @click="rows.splice(i,1)" class="btn btn-link text-danger">×</button>
                </div>
            </div>
        </template>
        <button type="button" @click="rows.push({field:'email',operator:'contains',value:''})" class="btn btn-outline-secondary btn-sm mb-3">+ Condición</button>

        <hr>
        <button type="submit" class="btn btn-primary">Crear segmento</button>
        <a href="{{ route('manager.campaigns.maillists.segments.index', $list->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
@endsection
