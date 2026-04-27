@php
    $supportedLocales = config('app.locales', ['es' => 'Español', 'en' => 'English', 'pt' => 'Português']);
@endphp

<div class="dropdown d-inline-block">
    <button class="btn btn-sm btn-link text-muted dropdown-toggle p-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Cambiar idioma">
        <i class="fas fa-globe me-1"></i>{{ strtoupper(app()->getLocale()) }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach($supportedLocales as $code => $name)
            <li>
                <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}" href="{{ route('locale.switch', $code) }}">
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
