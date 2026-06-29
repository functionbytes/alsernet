@extends('campaign::refactor.layout')

@section('title', $domain->name)

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $domain->name }}</h1>
            <p class="mc-page-subtitle">
                Dominio de tracking
                &middot; <code style="font-size:var(--text-xs);">{{ $domain->uid }}</code>
            </p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.tracking-domains.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Tracking domains
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Detail --}}
    <div class="mc-card">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
            <p style="font-weight:var(--font-semibold);margin:0;">Configuración del dominio</p>
            @php $st = $domain->status ?? 'pending'; @endphp
            @if($st === 'verified')
                <span class="mc-badge mc-badge-success">Verificado</span>
            @elseif($st === 'failed')
                <span class="mc-badge mc-badge-danger">Error</span>
            @else
                <span class="mc-badge mc-badge-warning">Pendiente</span>
            @endif
        </div>
        <div style="padding:var(--space-2) 0;">
            <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);border-bottom:1px solid var(--color-border-subtle);">
                <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Dominio</span>
                <span style="font-size:var(--text-sm);font-weight:var(--font-semibold);">{{ $domain->name }}</span>
            </div>
            <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);border-bottom:1px solid var(--color-border-subtle);">
                <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Método de verificación</span>
                <span style="font-size:var(--text-sm);">
                    @if($domain->verification_method)
                        <span class="mc-badge mc-badge-neutral">{{ strtoupper($domain->verification_method) }}</span>
                    @else
                        <span style="color:var(--color-text-muted);">—</span>
                    @endif
                </span>
            </div>
            <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);border-bottom:1px solid var(--color-border-subtle);">
                <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Estado</span>
                <span style="font-size:var(--text-sm);">{{ $domain->status ?? 'pending' }}</span>
            </div>
            <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);">
                <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Verificado el</span>
                <span style="font-size:var(--text-sm);">{{ $domain->verified_at?->format('d/m/Y H:i') ?: '—' }}</span>
            </div>
        </div>

        {{-- DNS instructions --}}
        @if($domain->verification_method === 'cname')
        <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);">
            <div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-3);">
                <div class="mc-alert-icon">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 16])
                </div>
                <div class="mc-alert-content">
                    <div class="mc-alert-title">Configuración DNS requerida</div>
                    <div class="mc-alert-text" style="font-size:var(--text-sm);">Crea un registro CNAME en tu proveedor DNS apuntando a este servidor de tracking.</div>
                </div>
            </div>
            <div style="background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:var(--space-3) var(--space-4);">
                <code style="font-size:var(--text-xs);">{{ $domain->name }}  CNAME  [tu-servidor-de-tracking]</code>
            </div>
        </div>
        @elseif($domain->verification_method === 'host')
        <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);">
            <div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-3);">
                <div class="mc-alert-icon">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 16])
                </div>
                <div class="mc-alert-content">
                    <div class="mc-alert-title">Configuración DNS requerida</div>
                    <div class="mc-alert-text" style="font-size:var(--text-sm);">Crea un registro A apuntando a la IP de este servidor.</div>
                </div>
            </div>
            <div style="background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:var(--space-3) var(--space-4);">
                <code style="font-size:var(--text-xs);">{{ $domain->name }}  A  [IP-del-servidor]</code>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar actions --}}
    <div class="mc-card" style="position:sticky;top:var(--space-5);">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
            <p style="font-weight:var(--font-semibold);margin:0;">Acciones</p>
        </div>
        <div style="padding:var(--space-4) var(--space-5);display:grid;gap:var(--space-3);">

            {{-- Verify --}}
            <form method="post" action="{{ route('manager.sending-servers.tracking-domains.verify', $domain->uid) }}">
                @csrf
                <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm" style="width:100%;justify-content:center;">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                    {{ trans('campaign::sending-servers.action.verify') }}
                </button>
            </form>

            {{-- Delete --}}
            <button type="button" class="mc-btn mc-btn-danger mc-btn-sm" id="delete-btn"
                    style="width:100%;justify-content:center;">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                {{ trans('campaign::sending-servers.action.delete') }}
            </button>
            <form id="delete-form" method="post"
                  action="{{ route('manager.sending-servers.tracking-domains.destroy', $domain->uid) }}"
                  style="display:none;">
                @csrf
                @method('DELETE')
            </form>

        </div>
        <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--color-border);">
            <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin:0;">La verificación comprueba el registro DNS del dominio. La propagación puede tardar hasta 48 h.</p>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn  = document.getElementById('delete-btn');
    var form = document.getElementById('delete-form');
    if (btn && form) {
        btn.addEventListener('click', function () {
            if (confirm('¿Eliminar el dominio de tracking «{{ addslashes($domain->name) }}»?')) {
                form.submit();
            }
        });
    }
});
</script>
@endsection
