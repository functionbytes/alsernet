@extends('campaign::refactor.layout')

@section('title', trans('campaign::sending-servers.page.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::sending-servers.page.title') }}</h1>
            <p class="mc-page-subtitle">{{ trans('campaign::sending-servers.page.subtitle', [], null, null) ?: 'Configura los proveedores SMTP y API para el envío de emails' }}</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.create') }}"
               class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                Nuevo servidor
            </a>
        </div>
    </div>
@endsection

@section('content')

{{-- Stats row --}}
@php
    $total    = $servers->total();
    $active   = \Modules\CampaignSendingServers\Models\SendingServer::where('status','active')->count();
    $inactive = \Modules\CampaignSendingServers\Models\SendingServer::where('status','inactive')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-4);margin-bottom:var(--space-5);">
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-1-bg);color:var(--chart-1);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::sending-servers.stat.total') }}</div>
            <div class="mc-stat-value">{{ number_format($total) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-2-bg);color:var(--chart-2);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::sending-servers.stat.active') }}</div>
            <div class="mc-stat-value">{{ number_format($active) }}</div>
        </div>
    </div>
    <div class="mc-stat-card">
        <div class="mc-stat-icon" style="background:var(--chart-3-bg);color:var(--chart-3);">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 22])
        </div>
        <div class="mc-stat-content">
            <div class="mc-stat-label">{{ trans('campaign::sending-servers.stat.inactive') }}</div>
            <div class="mc-stat-value">{{ number_format($inactive) }}</div>
        </div>
    </div>
</div>

{{-- Main card --}}
<div class="mc-card mc-card-table">

    {{-- Filter bar --}}
    <div class="mc-filter-bar">
        <form method="GET" action="{{ route('manager.sending-servers.index') }}" class="mc-filter-search" style="flex:1;max-width:360px;">
            <span class="mc-filter-search-icon">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'search', 'size' => 16])
            </span>
            <input class="mc-form-input mc-search-input"
                   type="search"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Buscar por nombre, host o email…">
        </form>
        @if(request('q'))
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                Limpiar
            </a>
        @endif
    </div>

    @if ($servers->isEmpty())
        <div style="padding:var(--space-16);text-align:center;color:var(--color-text-muted);">
            <div style="margin-bottom:var(--space-4);">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 48, 'class' => 'mc-empty-icon'])
            </div>
            <p style="font-weight:var(--font-semibold);margin-bottom:var(--space-2);">No hay servidores configurados</p>
            <p style="font-size:var(--text-sm);margin-bottom:var(--space-4);">Agrega un servidor SMTP o API para empezar a enviar emails</p>
            <a href="{{ route('manager.sending-servers.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                Nuevo servidor
            </a>
        </div>
    @else
        <div class="mc-table-wrap">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>{{ trans('campaign::sending-servers.col.name') }}</th>
                        <th>{{ trans('campaign::sending-servers.col.type') }}</th>
                        <th>{{ trans('campaign::sending-servers.col.status') }}</th>
                        <th>From por defecto</th>
                        <th>{{ trans('campaign::sending-servers.col.quota') }}</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($servers as $server)
                        <tr>
                            <td>
                                <a href="{{ route('manager.sending-servers.show', $server->uid) }}"
                                   style="font-weight:var(--font-semibold);color:var(--color-text);text-decoration:none;">
                                    {{ $server->name }}
                                </a>
                            </td>
                            <td style="color:var(--color-text-muted);font-size:var(--text-sm);">
                                {{ $providers[$server->type]['label'] ?? $server->type }}
                            </td>
                            <td>
                                @if($server->status === 'active')
                                    <span class="mc-badge mc-badge-success">{{ trans('campaign::sending-servers.status.active') }}</span>
                                @else
                                    <span class="mc-badge mc-badge-neutral">{{ trans('campaign::sending-servers.status.inactive') }}</span>
                                @endif
                            </td>
                            <td style="font-size:var(--text-sm);">
                                <code>{{ $server->default_from_email ?: '—' }}</code>
                            </td>
                            <td style="color:var(--color-text-muted);font-size:var(--text-sm);">
                                @if ($server->quota_value)
                                    {{ number_format($server->quota_value) }} / {{ $server->quota_base }} {{ $server->quota_unit }}
                                @else
                                    Sin límite
                                @endif
                            </td>
                            <td>
                                <div class="mc-dropdown" data-mc-dropdown>
                                    <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 16])
                                    </button>
                                    <div class="mc-dropdown-menu mc-dropdown-menu-end">
                                        <a class="mc-dropdown-item" href="{{ route('manager.sending-servers.show', $server->uid) }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
                                            Ver detalle
                                        </a>
                                        <a class="mc-dropdown-item" href="{{ route('manager.sending-servers.edit', $server->uid) }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.edit') }}
                                        </a>
                                        <button class="mc-dropdown-item" form="test-{{ $server->uid }}" type="submit">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.test') }}
                                        </button>
                                        <form id="test-{{ $server->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.test', $server->uid) }}"
                                              style="display:none;">
                                            @csrf
                                        </form>
                                        <div class="mc-dropdown-divider"></div>
                                        <button class="mc-dropdown-item mc-dropdown-item-danger"
                                                type="button"
                                                data-mc-confirm="¿Eliminar el servidor «{{ $server->name }}»? Esta acción no se puede deshacer."
                                                data-confirm-ok="Eliminar"
                                                data-confirm-type="danger"
                                                data-confirm-action="submit-form"
                                                data-confirm-target="del-{{ $server->uid }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.delete') }}
                                        </button>
                                        <form id="del-{{ $server->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.destroy', $server->uid) }}"
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

        {{-- Pagination --}}
        @if ($servers->hasPages())
            <div class="mc-table-footer">
                @include('campaign::refactor.partials._pagination', ['items' => $servers])
            </div>
        @endif
    @endif

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Wire confirm-delete buttons
    document.querySelectorAll('[data-mc-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var msg    = btn.dataset.mcConfirm || '¿Estás seguro?';
            var formId = btn.dataset.confirmTarget;
            if (confirm(msg)) {
                var form = document.getElementById(formId);
                if (form) form.submit();
            }
        });
    });
});
</script>
@endsection
