<ul class="nav {{ $tabClass ?? 'nav-tabs mb-4' }}" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'fields' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.edit', $form) }}">
            <i class="fas fa-th-list me-1"></i> Campos
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'settings' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.settings.edit', $form) }}">
            <i class="fas fa-cog me-1"></i> Configuración
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'emails' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.settings.emails', $form) }}">
            <i class="fas fa-envelope me-1"></i> Emails
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'access' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.settings.access', $form) }}">
            <i class="fas fa-lock me-1"></i> Acceso
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'gdpr' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.settings.gdpr', $form) }}">
            <i class="fas fa-shield-alt me-1"></i> GDPR
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'follow-ups' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.follow-ups.index', $form) }}">
            <i class="fas fa-paper-plane me-1"></i> Follow-ups
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'tokens' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.access-tokens.index', $form) }}">
            <i class="fas fa-key me-1"></i> Tokens
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'versions' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.versions.index', $form) }}">
            <i class="fas fa-history me-1"></i> Versiones
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === 'analytics' ? 'active' : '' }}" role="tab"
           href="{{ route('settings.forms.analytics', $form) }}">
            <i class="fas fa-chart-bar me-1"></i> Analíticas
        </a>
    </li>
</ul>
