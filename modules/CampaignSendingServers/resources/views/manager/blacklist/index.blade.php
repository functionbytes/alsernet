@extends('campaign::refactor.layout')

@section('title', trans('campaign::sending-servers.blacklist.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::sending-servers.blacklist.title') }}</h1>
            <p class="mc-page-subtitle">
                Gestiona los emails bloqueados manualmente o importados &middot;
                <strong>{{ number_format($entries->total()) }}</strong> {{ $entries->total() === 1 ? 'entrada' : 'entradas' }}
            </p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Servidores
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Table card --}}
    <div class="mc-card mc-card-table">

        {{-- Search --}}
        <div class="mc-filter-bar">
            <form method="GET" action="{{ route('manager.sending-servers.blacklist.index') }}" class="mc-filter-search" style="flex:1;max-width:360px;">
                <span class="mc-filter-search-icon">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'search', 'size' => 16])
                </span>
                <input class="mc-form-input mc-search-input"
                       type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Buscar por email…">
            </form>
            @if(request('q'))
                <a href="{{ route('manager.sending-servers.blacklist.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                    Limpiar
                </a>
            @endif
        </div>

        @if ($entries->isEmpty())
            <div style="padding:var(--space-16);text-align:center;color:var(--color-text-muted);">
                <div style="margin-bottom:var(--space-4);">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'list', 'size' => 48, 'class' => 'mc-empty-icon'])
                </div>
                <p style="font-weight:var(--font-semibold);margin-bottom:var(--space-2);">
                    {{ request('q') ? 'Sin resultados' : 'Lista negra vacía' }}
                </p>
                <p style="font-size:var(--text-sm);">
                    {{ request('q') ? 'No hay emails que coincidan con «' . request('q') . '».' : 'No hay emails bloqueados actualmente.' }}
                </p>
            </div>
        @else
            <div class="mc-table-wrap">
                <table class="mc-table">
                    <thead>
                        <tr>
                            <th>{{ trans('campaign::sending-servers.blacklist.email') }}</th>
                            <th>{{ trans('campaign::sending-servers.blacklist.reason') }}</th>
                            <th>Origen</th>
                            <th>Fecha</th>
                            <th style="width:48px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $e)
                            <tr>
                                <td>
                                    <code style="font-size:var(--text-sm);">{{ $e->email }}</code>
                                </td>
                                <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                    {{ $e->reason ?: '—' }}
                                </td>
                                <td>
                                    <span class="mc-badge mc-badge-neutral">{{ $e->source ?: 'manual' }}</span>
                                </td>
                                <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                    {{ $e->created_at?->format('d/m/Y') }}
                                </td>
                                <td>
                                    <div class="mc-dropdown" data-mc-dropdown>
                                        <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 16])
                                        </button>
                                        <div class="mc-dropdown-menu mc-dropdown-menu-end">
                                            <button class="mc-dropdown-item mc-dropdown-item-danger"
                                                    type="button"
                                                    data-delete-id="{{ $e->id }}"
                                                    data-delete-email="{{ $e->email }}">
                                                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                                                {{ trans('campaign::sending-servers.action.delete') }}
                                            </button>
                                            <form id="del-{{ $e->id }}"
                                                  method="post"
                                                  action="{{ route('manager.sending-servers.blacklist.destroy', $e->id) }}"
                                                  style="display:none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="mc-table-footer">
                    @include('campaign::refactor.partials._pagination', ['items' => $entries])
                </div>
            @endif
        @endif

    </div>

    {{-- Sidebar: Add + Import --}}
    <div style="display:grid;gap:var(--space-4);position:sticky;top:var(--space-5);">

        {{-- Add email --}}
        <div class="mc-card">
            <form method="post" action="{{ route('manager.sending-servers.blacklist.store') }}">
                @csrf
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
                    <p style="font-weight:var(--font-semibold);margin:0;">Añadir email</p>
                    <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin:var(--space-1) 0 0;">Bloquea manualmente una dirección</p>
                </div>

                @if ($errors->any())
                    <div class="mc-alert mc-alert-danger" style="margin:var(--space-4) var(--space-5) 0;">
                        <div class="mc-alert-content">
                            @foreach ($errors->all() as $err)
                                <div style="font-size:var(--text-sm);">{{ $err }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="padding:var(--space-4) var(--space-5);display:grid;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="email">{{ trans('campaign::sending-servers.blacklist.email') }} <span class="mc-required">*</span></label>
                        <input type="email" name="email" id="email"
                               class="mc-form-input"
                               placeholder="email@example.com"
                               required>
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="reason">{{ trans('campaign::sending-servers.blacklist.reason') }}</label>
                        <input type="text" name="reason" id="reason"
                               class="mc-form-input"
                               placeholder="Opcional (ej: spam, bounce duro)">
                    </div>
                </div>

                <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--color-border);">
                    <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm" style="width:100%;justify-content:center;">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                        Añadir a lista negra
                    </button>
                </div>
            </form>
        </div>

        {{-- CSV Import --}}
        <div class="mc-card">
            <form method="post" action="{{ route('manager.sending-servers.blacklist.import') }}" enctype="multipart/form-data">
                @csrf
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
                    <p style="font-weight:var(--font-semibold);margin:0;">Importar CSV</p>
                    <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin:var(--space-1) 0 0;">Carga masiva desde archivo CSV o TXT</p>
                </div>
                <div style="padding:var(--space-4) var(--space-5);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="file">Archivo CSV o TXT <span class="mc-required">*</span></label>
                        <input type="file" name="file" id="file"
                               class="mc-form-input"
                               accept=".csv,.txt"
                               required>
                        <p class="mc-form-hint">Una dirección de email por línea.</p>
                    </div>
                </div>
                <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--color-border);">
                    <button type="submit" class="mc-btn mc-btn-secondary mc-btn-sm" style="width:100%;justify-content:center;">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'file-text', 'size' => 16])
                        Importar emails
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-delete-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var email = btn.dataset.deleteEmail || 'este email';
            var id    = btn.dataset.deleteId;
            if (confirm('¿Eliminar «' + email + '» de la lista negra?')) {
                var form = document.getElementById('del-' + id);
                if (form) form.submit();
            }
        });
    });
});
</script>
@endsection
