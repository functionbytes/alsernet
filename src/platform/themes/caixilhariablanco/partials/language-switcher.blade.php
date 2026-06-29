@php
    $activeLang  = $currentPageLocale ?? app()->getLocale();
    $localeNames = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English', 'fr' => 'Français'];
    $availableLinks = collect($pageLangLinks ?? [])
        ->filter(fn($info, $lang) => $lang !== $activeLang && !empty($info['url']) && $info['published'])
        ->all();
@endphp

@if (count($availableLinks) > 0)
    <li>
        <a class="language-dropdown-active" href="#">
            <i class="fa fa-globe"></i> {{ strtoupper($activeLang) }} <i class="fa fa-chevron-down"></i>
        </a>
        <ul class="language-dropdown">
            @foreach ($availableLinks as $lang => $info)
                <li>
                    <a rel="alternate" hreflang="{{ $lang }}" href="{{ $info['url'] }}">
                        {{ $localeNames[$lang] ?? strtoupper($lang) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </li>
@endif
