@php
    $contactForm = \Modules\Forms\Models\Form::query()
        ->where('slug', 'contacto')
        ->active()
        ->with(['fields' => fn ($q) => $q->visible()->ordered()])
        ->first();
    $shortcodeConfig = [];
@endphp

@if($contactForm)
    @include('forms::public.render', ['form' => $contactForm])
@endif
