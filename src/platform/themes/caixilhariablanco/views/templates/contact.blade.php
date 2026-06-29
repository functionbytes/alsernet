@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')



    @php
        $contactFormModule = \Modules\Forms\Models\Form::query()
            ->where('slug', 'contacto')
            ->active()
            ->with(['fields' => fn($q) => $q->visible()->ordered()])
            ->first();

        $processedContent = $transContent ?? '';
        if ($contactFormModule && $processedContent) {
            $formHtml = view('forms::public.render', [
                'form'            => $contactFormModule,
                'shortcodeConfig' => [],
            ])->render();
            $processedContent = preg_replace(
                '/<form\b[^>]*id=["\']formContacts["\'][^>]*>.*?<\/form>/si',
                $formHtml,
                $processedContent
            );
        }
    @endphp

    @if($processedContent)
    <div class="ck-content">
        {!! $processedContent !!}
    </div>
    @endif
@endsection
