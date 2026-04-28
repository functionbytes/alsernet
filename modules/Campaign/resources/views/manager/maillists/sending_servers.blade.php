@extends('theme::layouts.manager')

@section('title', 'Servidores de la lista')

@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>Servidores de envío</h2>
    <p class="text-muted">Lista: {{ $list->name }}. La campaña usará uno de estos al enviar (selección ponderada por priority).</p>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="post" action="{{ route('manager.campaigns.maillists.sending-servers', $list->uid) }}">
        @csrf

        <table class="table">
            <thead><tr><th></th><th>Servidor</th><th>Tipo</th><th>Priority</th></tr></thead>
            <tbody>
                @php $current = $list->sendingServers->keyBy('id'); @endphp
                @foreach ($allServers as $srv)
                    @php $isAttached = $current->has($srv->id); $priority = $isAttached ? $current[$srv->id]->pivot->priority : 1; @endphp
                    <tr>
                        <td><input type="checkbox" name="servers[{{ $loop->index }}][id]" value="{{ $srv->id }}" @checked($isAttached) class="form-check-input"></td>
                        <td>{{ $srv->name }}</td>
                        <td><small>{{ $srv->getTypeName() }}</small></td>
                        <td><input type="number" name="servers[{{ $loop->index }}][priority]" value="{{ $priority }}" min="1" max="100" class="form-control form-control-sm" style="width:80px"></td>
                    </tr>
                @endforeach
                @if ($allServers->isEmpty())
                    <tr><td colspan="4" class="text-center text-muted">No hay servidores. <a href="{{ route('manager.sending-servers.create') }}">Crear uno</a>.</td></tr>
                @endif
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('manager.campaigns.maillists.show', $list->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
