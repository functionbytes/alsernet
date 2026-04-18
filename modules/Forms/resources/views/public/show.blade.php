<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->name }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @include('forms::public.partials.form-body', [
                        'formId'         => 'form-' . $form->id,
                        'form'           => $form,
                        'isMultiStep'    => $form->is_multi_step,
                        'steps'          => $form->fields->pluck('step_number')->unique()->sort()->values(),
                        'totalSteps'     => $form->fields->pluck('step_number')->unique()->count(),
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
    </div>
</div>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('modules/forms/js/forms.js') }}"></script>
</body>
</html>
