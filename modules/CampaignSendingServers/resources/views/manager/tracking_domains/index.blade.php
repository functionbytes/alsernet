@extends('campaign::refactor.layout')

@section('title', trans('campaign::sending-servers.tracking.title'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ trans('campaign::sending-servers.tracking.title') }}</h1>
            <p class="mc-page-subtitle">Dominios personalizados para el seguimiento de aperturas y clicks</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Servidores
            </a>
            <a href="{{ route('manager.sending-servers.tracking-domains.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
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
            <p style="font-weight:var(--font-semibold);margin-bottom:var(--space-2);">Sin dominios de tracking</p>
            <p style="font-size:var(--text-sm);margin-bottom:var(--space-4);">Agrega un dominio propio para personalizar los enlaces de seguimiento</p>
            <a href="{{ route('manager.sending-servers.tracking-domains.create') }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'add', 'size' => 16])
                Agregar dominio
            </a>
        </div>
    @else
        <div class="mc-table-wrap">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>Dominio</th>
                        <th>Estado</th>
                        <th>Método de verificación</th>
                        <th>Verificado el</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $d)
                        <tr>
                            <td>
                                <a href="{{ route('manager.sending-servers.tracking-domains.show', $d->uid) }}"
                                   style="font-weight:var(--font-semibold);color:var(--color-text);text-decoration:none;">
                                    {{ $d->name }}
                                </a>
                            </td>
                            <td>
                                @php $st = $d->status ?? 'pending'; @endphp
                                @if($st === 'verified')
                                    <span class="mc-badge mc-badge-success">Verificado</span>
                                @elseif($st === 'failed')
                                    <span class="mc-badge mc-badge-danger">Error</span>
                                @else
                                    <span class="mc-badge mc-badge-warning">Pendiente</span>
                                @endif
                            </td>
                            <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                {{ $d->verification_method ? strtoupper($d->verification_method) : '—' }}
                            </td>
                            <td style="font-size:var(--text-sm);color:var(--color-text-muted);">
                                {{ $d->verified_at?->format('d/m/Y H:i') ?: '—' }}
                            </td>
                            <td>
                                <div class="mc-dropdown" data-mc-dropdown>
                                    <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm mc-btn-icon" data-dropdown-trigger>
                                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'more-v', 'size' => 16])
                                    </button>
                                    <div class="mc-dropdown-menu mc-dropdown-menu-end">
                                        <a class="mc-dropdown-item" href="{{ route('manager.sending-servers.tracking-domains.show', $d->uid) }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
                                            Ver detalle
                                        </a>
                                        <button class="mc-dropdown-item" form="verify-{{ $d->uid }}" type="submit">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.verify') }}
                                        </button>
                                        <form id="verify-{{ $d->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.tracking-domains.verify', $d->uid) }}"
                                              style="display:none;">
                                            @csrf
                                        </form>
                                        <div class="mc-dropdown-divider"></div>
                                        <button class="mc-dropdown-item mc-dropdown-item-danger"
                                                type="button"
                                                data-delete-uid="{{ $d->uid }}"
                                                data-delete-name="{{ $d->name }}">
                                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 14])
                                            {{ trans('campaign::sending-servers.action.delete') }}
                                        </button>
                                        <form id="del-{{ $d->uid }}"
                                              method="post"
                                              action="{{ route('manager.sending-servers.tracking-domains.destroy', $d->uid) }}"
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
    document.querySelectorAll('[data-delete-uid]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.dataset.deleteName || 'este dominio';
            var uid  = btn.dataset.deleteUid;
            if (confirm('¿Eliminar el dominio de tracking «' + name + '»?')) {
                var form = document.getElementById('del-' + uid);
                if (form) form.submit();
            }
        });
    });
});
</script>
@endsection
