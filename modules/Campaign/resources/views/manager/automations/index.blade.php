@extends('theme::layouts.manager')
@section('title', 'Automatizaciones')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Automatizaciones</h2>
        <a href="{{ route('manager.campaigns.automations.create') }}" class="btn btn-primary">Nueva</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <table class="table">
        <thead><tr><th>Nombre</th><th>Lista</th><th>Estado</th><th>Última ejecución</th><th></th></tr></thead>
        <tbody>
            @forelse ($automations as $a)
                <tr>
                    <td><a href="{{ route('manager.campaigns.automations.edit', $a->uid) }}">{{ $a->name }}</a></td>
                    <td><small>{{ optional(\Modules\Campaign\Models\CampaignMaillist::find($a->mail_list_id))->name ?: '—' }}</small></td>
                    <td><span class="badge bg-{{ $a->status === 'active' ? 'success' : 'secondary' }}">{{ $a->status }}</span></td>
                    <td>{{ $a->last_executed_at?->format('Y-m-d H:i') ?: 'nunca' }}</td>
                    <td>
                        @if ($a->status === 'active')
                            <form method="post" action="{{ route('manager.campaigns.automations.disable', $a->uid) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning">Pausar</button></form>
                        @else
                            <form method="post" action="{{ route('manager.campaigns.automations.enable', $a->uid) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Activar</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Sin automatizaciones.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $automations->links() }}
</div>
@endsection
