@extends('campaign::refactor.layout')

@section('title', trans('campaign::sending-servers.domain.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::sending-servers.domain.title') }}</h1>
            <p class="mc-page-subtitle">Gestiona los dominios DKIM/SPF asociados a tus servidores de envío</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Servidores
            </a>
            <a href="{{ route('manager.sending-servers.domains.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                Nuevo dominio
            </a>
        </div>
    </div>
@endsection

@section('content')

<div class="mc-card mc-card-table">

    @if ($domains->isEmpty())
        <div style="padding:var(--space-16);text-align:center;color:var(--color-text-muted);">
            <div style="margin-bottom:var(--space-4);">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'layers', 'size' => 48, 'class' => 'mc-empty-icon'])
            </div>
            <p style="font-weight:var(--font-semibold);margin-bottom:var(--space-2);">No hay dominios configurados</p>
            <p style="font-size:var(--text-sm);margin-bottom:var(--space-4);">Agrega un dominio para gestionar la firma DKIM y verificación SPF</p>
            <a href="{{ route('manager.sending-servers.domains.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                Agregar primer dominio
            </a>
        </div>
    @else
        <div class="mc-table-wrap">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>Dominio</th>
                        <th>Servidor</th>
                        <th>Estado</th>
                        <th>Firma DKIM</th>
                        <th>Verificado</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        <tr>
                            <td>
                                <a href="{{ route('manager.sending-servers.domains.show', $domain->uid) }}"
                                   style="font-weight:var(--font-semibold);color:var(--color-text);text-decoration:none;">
                                    {{ $domain->name }}
                                </a>
                            </td>
                            <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                @php
                                    try {
                                        echo optional($domain->sendingServer)->name ?: '—';
                                    } catch (\Exception $e) {
                                        echo '—';
                                    }
                                @endphp
                            </td>
                            <td>
                                @php $st = $domain->status ?? 'pending'; @endphp
                                @if($st === 'verified')
                                    <span class="mc-badge mc-badge-success">Verificado</span>
                                @elseif($st === 'failed')
                                    <span class="mc-badge mc-badge-danger">Error</span>
                                @else
                                    <span class="mc-badge mc-badge-warning">Pendiente</span>
                                @endif
                            </td>
                            <td style="font-size:var(--text-sm);">
                                @if($domain->signing_enabled)
                                    <span class="mc-badge mc-badge-success">Activa</span>
                                @else
                                    <span style="color:var(--color-text-muted);">—</span>
                                @endif
                            </td>
                            <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                {{ isset($domain->verified_at) ? $domain->verified_at?->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td>
                                <div class="mc-dropdown" data-mc-dropdown>
                                    <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 16])
                                    </button>
                                    <div class="mc-dropdown-menu mc-dropdown-menu-end">
                                        <a class="mc-dropdown-item" href="{{ route('manager.sending-servers.domains.show', $domain->uid) }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
                                            Ver detalle
                                        </a>
                                        <button class="mc-dropdown-item" form="verify-{{ $domain->uid }}" type="submit">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.verify') }} DKIM
                                        </button>
                                        <form id="verify-{{ $domain->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.domains.verify', $domain->uid) }}"
                                              style="display:none;">
                                            @csrf
                                        </form>
                                        <div class="mc-dropdown-divider"></div>
                                        <button class="mc-dropdown-item mc-dropdown-item-danger"
                                                type="button"
                                                data-delete-domain="{{ $domain->uid }}"
                                                data-delete-name="{{ $domain->name }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.delete') }}
                                        </button>
                                        <form id="del-{{ $domain->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.domains.destroy', $domain->uid) }}"
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

        @if ($domains->hasPages())
            <div class="mc-table-footer">
                @include('campaign::refactor.partials._pagination', ['items' => $domains])
            </div>
        @endif
    @endif

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-delete-domain]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.dataset.deleteName || 'este dominio';
            var uid  = btn.dataset.deleteDomain;
            if (confirm('¿Eliminar el dominio «' + name + '»? Esta acción no se puede deshacer.')) {
                var form = document.getElementById('del-' + uid);
                if (form) form.submit();
            }
        });
    });
});
</script>
@endsection
