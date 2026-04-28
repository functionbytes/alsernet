@extends('theme::layouts.manager')

@section('title', 'Lista negra')

@section('content')
    <div class="container py-4">
        <h2>Lista negra</h2>
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="row">
            <div class="col-md-6">
                <h5>Añadir email</h5>
                <form method="post" action="{{ route('manager.sending-servers.blacklist.store') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                        </div>
                        <div class="form-group col-md-5">
                            <input type="text" name="reason" class="form-control" placeholder="motivo (opcional)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Añadir</button>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Importar CSV</h5>
                <form method="post" action="{{ route('manager.sending-servers.blacklist.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control-file" accept=".csv,.txt" required>
                    <button type="submit" class="btn btn-secondary btn-sm mt-2">Importar</button>
                </form>
            </div>
        </div>

        <hr>

        <form method="get" class="mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar email">
        </form>

        <table class="table table-sm">
            <thead><tr><th>Email</th><th>Motivo</th><th>Origen</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
                @forelse ($entries as $e)
                    <tr>
                        <td>{{ $e->email }}</td>
                        <td>{{ $e->reason }}</td>
                        <td>{{ $e->source }}</td>
                        <td>{{ $e->created_at?->format('Y-m-d') }}</td>
                        <td>
                            <form method="post" action="{{ route('manager.sending-servers.blacklist.destroy', $e->id) }}" onsubmit="return confirm('¿Eliminar?');">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Eliminar</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Lista vacía.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $entries->links() }}
    </div>
@endsection
