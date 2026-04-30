@extends('layouts.theme')

@section('title', 'Segmentos')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h2>Segmentos</h2><p class="text-muted mb-0">Lista: {{ $list->name }}</p></div>
        <a href="{{ route('manager.maillists.segments.create', $list->uid) }}" class="btn btn-primary">Nuevo segmento</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <table class="table">
        <thead><tr><th>Nombre</th><th>Match</th><th>Condiciones</th><th></th></tr></thead>
        <tbody>
            @forelse ($segments as $seg)
                <tr>
                    <td><a href="{{ route('manager.maillists.segments.edit', [$list->uid, $seg->uid]) }}">{{ $seg->name }}</a></td>
                    <td><code>{{ $seg->matching }}</code></td>
                    <td>{{ $seg->conditions_count }} condición(es)</td>
                    <td>
                        <form method="post" action="{{ route('manager.maillists.segments.destroy', [$list->uid, $seg->uid]) }}" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                            @csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Sin segmentos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
