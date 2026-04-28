@extends('theme::layouts.manager')
@section('title', 'Layouts')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Layouts de email</h2>
        <a href="{{ route('manager.campaigns.layouts.create') }}" class="btn btn-primary">Nuevo</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <table class="table">
        <thead><tr><th>Nombre</th><th>Default</th><th>Orden</th><th></th></tr></thead>
        <tbody>
            @forelse ($layouts as $l)
                <tr>
                    <td><a href="{{ route('manager.campaigns.layouts.edit', $l->uid) }}">{{ $l->name }}</a></td>
                    <td>{{ $l->default ? '✓' : '' }}</td>
                    <td>{{ $l->order }}</td>
                    <td>
                        <form method="post" action="{{ route('manager.campaigns.layouts.destroy', $l->uid) }}" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                            @csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Sin layouts.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $layouts->links() }}
</div>
@endsection
