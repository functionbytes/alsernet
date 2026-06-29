@extends('template::layouts.default')

@section('title', $form->name)

@section('css')
<link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @php
                $formId      = 'form-preview-' . $form->id;
                $isMultiStep = $form->is_multi_step && $form->fields->pluck('step_number')->unique()->count() > 1;
                $steps       = $isMultiStep ? $form->fields->pluck('step_number')->unique()->sort()->values() : collect([1]);
            @endphp
            @include('forms::public.partials.form-body', [
                'formId'         => $formId,
                'form'           => $form,
                'isMultiStep'    => $isMultiStep,
                'steps'          => $steps,
                'totalSteps'     => $steps->count(),
                'floatingLabel'  => $form->floating_label ?? false,
                'buttonText'     => $form->submit_button_text ?? 'Enviar',
                'buttonColor'    => $form->button_color ?? null,
                'showTitle'      => true,
                'theme'          => $form->theme ?? 'default',
                'captchaEnabled' => $form->captcha_enabled && class_exists(\Modules\Captcha\Facades\Captcha::class),
            ])
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('modules/forms/js/forms.min.js') }}"></script>
@endsection
