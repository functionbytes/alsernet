@once
@push('css')
<style>
    .forms-nav-item {
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        text-decoration: none;
        color: inherit;
    }
    .forms-nav-item:hover:not(.active) {
        background: #f5f6f8;
        color: inherit;
    }
    .forms-nav-item.active {
        background: #212529;
        color: #fff !important;
    }
    .forms-nav-item.active .forms-nav-desc {
        color: rgba(255,255,255,.55);
    }
    .forms-nav-icon {
        margin-top: 2px;
        flex-shrink: 0;
        font-size: 0.85rem;
    }
    .forms-nav-label {
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.3;
    }
    .forms-nav-desc {
        font-size: 0.72rem;
        color: #6c757d;
        line-height: 1.3;
        margin-top: 1px;
    }
    .forms-nav-section-title {
        font-size: 0.65rem;
        letter-spacing: 0.08em;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.75rem 0.75rem 0.25rem;
        color: #6c757d;
    }
</style>
@endpush
@endonce

<div class="card">
    <div class="card-body p-2">
        <nav class="d-flex flex-column gap-1">

            <a href="{{ route('settings.forms.edit', $form) }}"
               class="forms-nav-item {{ $active === 'fields' ? 'active' : '' }}">
                <i class="fas fa-list-ul fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Campos</div>
                    <div class="forms-nav-desc">Diseña la estructura del formulario</div>
                </div>
            </a>

            <div class="forms-nav-section-title">Ajustes</div>

            <a href="{{ route('settings.forms.settings.edit', $form) }}"
               class="forms-nav-item {{ $active === 'settings' ? 'active' : '' }}">
                <i class="fas fa-gear fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">General</div>
                    <div class="forms-nav-desc">Nombre, slug y comportamiento</div>
                </div>
            </a>
            <a href="{{ route('settings.forms.settings.emails', $form) }}"
               class="forms-nav-item {{ $active === 'emails' ? 'active' : '' }}">
                <i class="fas fa-envelope fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Emails</div>
                    <div class="forms-nav-desc">Notificaciones y confirmaciones</div>
                </div>
            </a>
            <a href="{{ route('settings.forms.settings.access', $form) }}"
               class="forms-nav-item {{ $active === 'access' ? 'active' : '' }}">
                <i class="fas fa-lock fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Acceso</div>
                    <div class="forms-nav-desc">Contraseña y restricciones</div>
                </div>
            </a>
            <a href="{{ route('settings.forms.settings.gdpr', $form) }}"
               class="forms-nav-item {{ $active === 'gdpr' ? 'active' : '' }}">
                <i class="fas fa-shield-halved fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">GDPR</div>
                    <div class="forms-nav-desc">Retención de datos y privacidad</div>
                </div>
            </a>

            <div class="forms-nav-section-title">Automatización</div>

            <a href="{{ route('settings.forms.follow-ups.index', $form) }}"
               class="forms-nav-item {{ $active === 'follow-ups' ? 'active' : '' }}">
                <i class="fas fa-reply-all fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Follow-ups</div>
                    <div class="forms-nav-desc">Respuestas automáticas por email</div>
                </div>
            </a>
            <a href="{{ route('settings.forms.access-tokens.index', $form) }}"
               class="forms-nav-item {{ $active === 'tokens' ? 'active' : '' }}">
                <i class="fas fa-key fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Tokens de acceso</div>
                    <div class="forms-nav-desc">Acceso programático a la API</div>
                </div>
            </a>

            <div class="forms-nav-section-title">Datos</div>

            <a href="{{ route('settings.forms.versions.index', $form) }}"
               class="forms-nav-item {{ $active === 'versions' ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Versiones</div>
                    <div class="forms-nav-desc">Historial de cambios de campos</div>
                </div>
            </a>
            <a href="{{ route('settings.forms.analytics', $form) }}"
               class="forms-nav-item {{ $active === 'analytics' ? 'active' : '' }}">
                <i class="fas fa-chart-bar fa-fw forms-nav-icon"></i>
                <div>
                    <div class="forms-nav-label">Analíticas</div>
                    <div class="forms-nav-desc">Estadísticas de envíos</div>
                </div>
            </a>

        </nav>
    </div>
    <div class="card-footer p-2 border-top">
        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank"
           class="btn btn-sm btn-outline-secondary w-100 mb-1">
            <i class="fas fa-desktop me-1"></i> Preview
        </a>
        <a href="{{ route('settings.forms.submissions.index', $form) }}"
           class="btn btn-sm btn-outline-secondary w-100">
            <i class="fas fa-inbox me-1"></i> Submissions
        </a>
    </div>
</div>
