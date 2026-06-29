@extends('campaign::refactor.layout')

@section('title', $server->name)

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $server->name }}</h1>
            <p class="mc-page-subtitle">
                {{ $server->getTypeName() }}
                &middot; <code style="font-size:var(--text-xs);">{{ $server->uid }}</code>
            </p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Volver
            </a>
            <a href="{{ route('manager.sending-servers.edit', $server->uid) }}" class="mc-btn mc-btn-primary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 16])
                {{ trans('campaign::sending-servers.action.edit') }}
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Detail card --}}
    <div style="display:grid;gap:var(--space-5);">

        {{-- General info --}}
        <div class="mc-card">
            <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
                <p style="font-weight:var(--font-semibold);margin:0;">Configuración general</p>
                @if($server->status === 'active')
                    <span class="mc-badge mc-badge-success">{{ trans('campaign::sending-servers.status.active') }}</span>
                @else
                    <span class="mc-badge mc-badge-neutral">{{ trans('campaign::sending-servers.status.inactive') }}</span>
                @endif
            </div>
            <div style="padding:var(--space-2) 0;">
                @php
                    $rows = [
                        'Tipo'                => $server->getTypeName(),
                        'Host SMTP'           => $server->host ?: null,
                        'Puerto / Protocolo'  => $server->smtp_port ? ($server->smtp_port . ' / ' . strtoupper($server->smtp_protocol ?? '—')) : null,
                        'Email por defecto'   => $server->default_from_email ?: null,
                        'Cuota'               => $server->quota_value
                            ? number_format($server->quota_value) . ' / ' . $server->quota_base . ' ' . $server->quota_unit
                            : 'Sin límite',
                        'Región AWS'          => $server->aws_region ?: null,
                        'Dominio API'         => $server->domain ?: null,
                    ];
                @endphp
                @foreach ($rows as $label => $value)
                    @if(!is_null($value))
                    <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);border-bottom:1px solid var(--color-border-subtle);">
                        <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">{{ $label }}</span>
                        <span style="font-size:var(--text-sm);">{{ $value }}</span>
                    </div>
                    @endif
                @endforeach

                {{-- Bounce / Feedback handlers --}}
                @php
                    try {
                        $bounceHandlerName   = optional($server->bounceHandler)->name ?: '—';
                        $feedbackHandlerName = optional($server->feedbackLoopHandler)->name ?: '—';
                        $showHandlers = true;
                    } catch (\Exception $e) {
                        $showHandlers = false;
                    }
                @endphp
                @if (!empty($showHandlers))
                <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);border-bottom:1px solid var(--color-border-subtle);">
                    <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Bounce handler</span>
                    <span style="font-size:var(--text-sm);">{{ $bounceHandlerName }}</span>
                </div>
                <div style="display:flex;padding:var(--space-3) var(--space-5);gap:var(--space-4);">
                    <span style="width:180px;flex-shrink:0;color:var(--color-text-muted);font-size:var(--text-sm);">Feedback handler</span>
                    <span style="font-size:var(--text-sm);">{{ $feedbackHandlerName }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Rate limits card --}}
        @php
            try {
                $limits = $server->getRateLimits();
            } catch (\Exception $e) {
                $limits = [];
            }
        @endphp
        @if (count($limits) > 0)
        <div class="mc-card">
            <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);margin:0;">Rate limits activos</p>
            </div>
            <div style="padding:var(--space-4) var(--space-5);display:grid;gap:var(--space-3);">
                @foreach ($limits as $limit)
                    <div class="mc-alert mc-alert-teal" style="margin:0;">
                        <div class="mc-alert-icon">
                            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 16])
                        </div>
                        <div class="mc-alert-content">
                            <div class="mc-alert-text" style="font-size:var(--text-sm);">{{ $limit->description ?? $limit }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- Sidebar actions --}}
    <div style="display:grid;gap:var(--space-4);position:sticky;top:var(--space-5);">

        {{-- Actions card --}}
        <div class="mc-card">
            <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);margin:0;">Acciones</p>
            </div>
            <div style="padding:var(--space-4) var(--space-5);display:grid;gap:var(--space-3);">

                {{-- Test --}}
                <form method="post" action="{{ route('manager.sending-servers.test', $server->uid) }}">
                    @csrf
                    <button type="submit" class="mc-btn mc-btn-secondary mc-btn-sm" style="width:100%;justify-content:center;">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                        {{ trans('campaign::sending-servers.action.test') }}
                    </button>
                </form>

                {{-- Edit --}}
                <a href="{{ route('manager.sending-servers.edit', $server->uid) }}"
                   class="mc-btn mc-btn-secondary mc-btn-sm"
                   style="width:100%;justify-content:center;">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'edit', 'size' => 16])
                    {{ trans('campaign::sending-servers.action.edit') }}
                </a>

                {{-- Delete --}}
                <button type="button"
                        class="mc-btn mc-btn-danger mc-btn-sm"
                        style="width:100%;justify-content:center;"
                        id="delete-btn">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'delete', 'size' => 16])
                    {{ trans('campaign::sending-servers.action.delete') }}
                </button>
                <form id="delete-form"
                      method="post"
                      action="{{ route('manager.sending-servers.destroy', $server->uid) }}"
                      style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>

        {{-- Sending domains linked --}}
        @php
            try {
                $linkedDomains = $server->sendingDomains()->take(5)->get();
            } catch (\Exception $e) {
                $linkedDomains = collect();
            }
        @endphp
        @if ($linkedDomains->isNotEmpty())
        <div class="mc-card">
            <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);margin:0;">Dominios vinculados</p>
            </div>
            <div style="padding:var(--space-2) 0;">
                @foreach ($linkedDomains as $d)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--space-2) var(--space-5);">
                    <a href="{{ route('manager.sending-servers.domains.show', $d->uid) }}"
                       style="font-size:var(--text-sm);color:var(--color-text);text-decoration:none;">
                        {{ $d->name }}
                    </a>
                    @if(($d->status ?? '') === 'verified')
                        <span class="mc-badge mc-badge-success" style="font-size:10px;">Verificado</span>
                    @else
                        <span class="mc-badge mc-badge-neutral" style="font-size:10px;">{{ $d->status ?? 'pendiente' }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteBtn  = document.getElementById('delete-btn');
    var deleteForm = document.getElementById('delete-form');
    if (deleteBtn && deleteForm) {
        deleteBtn.addEventListener('click', function () {
            if (confirm('¿Eliminar el servidor «{{ addslashes($server->name) }}»? Esta acción no se puede deshacer.')) {
                deleteForm.submit();
            }
        });
    }
});
</script>
@endsection
