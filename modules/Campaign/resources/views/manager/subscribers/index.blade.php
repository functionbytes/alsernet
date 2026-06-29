@extends('layouts.theme')

@section('title', "Suscriptores · {$list->name}")

@section('content')
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Suscriptores</h2>
                <p class="text-muted mb-0">Lista: <a href="{{ route('manager.maillists.show', $list->uid) }}">{{ $list->name }}</a></p>
            </div>
            <a href="{{ route('manager.maillists.subscribers.create', $list->uid) }}" class="btn btn-primary">Añadir suscriptor</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-7"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por email/nombre…"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="subscribed" @selected(request('status') === 'subscribed')>Suscrito</option>
                    <option value="unsubscribed" @selected(request('status') === 'unsubscribed')>Desuscrito</option>
                    <option value="unconfirmed" @selected(request('status') === 'unconfirmed')>Sin confirmar</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filtrar</button></div>
        </form>

        <table class="table table-sm">
            <thead><tr><th>Email</th><th>Nombre</th><th>Estado</th><th>Suscrito</th><th></th></tr></thead>
            <tbody>
                @forelse ($subscribers as $sub)
                    <tr>
                        <td>{{ $sub->email }}</td>
                        <td>{{ trim($sub->first_name.' '.$sub->last_name) }}</td>
                        <td><span class="badge bg-secondary">{{ $sub->pivot->status }}</span></td>
                        <td>{{ optional($sub->pivot->subscribed_at)?->format('Y-m-d') ?: ($sub->pivot->subscribed_at ?? '—') }}</td>
                        <td>
                            <a href="{{ route('manager.maillists.subscribers.edit', [$list->uid, $sub->uid]) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            <form method="post" action="{{ route('manager.maillists.subscribers.destroy', [$list->uid, $sub->uid]) }}" class="d-inline" onsubmit="return confirm('¿Desasociar de la lista?');">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Quitar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Sin suscriptores. <a href="{{ route('manager.maillists.show', $list->uid) }}">Importa un CSV</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $subscribers->links() }}
@endsection
