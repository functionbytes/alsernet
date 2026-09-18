@php
    $modalId = 'form-link-modal-'.$form->id;
    $formId = 'form-'.$form->id.'-link';
    $btnClass = 'btn btn-'.$variant.' btn-'.$size.' forms-link-trigger';
    $isMultiStep = $form->is_multi_step && count($form->fields->pluck('step_number')->unique()) > 1;
    $steps = $isMultiStep ? $form->fields->pluck('step_number')->unique()->sort()->values() : collect([1]);
    $totalSteps = $steps->count();
    $theme = $form->theme ?? 'default';
    $floatingLabel = $form->floating_label ?? false;
    $captchaEnabled = ($form->captcha_enabled ?? false) && class_exists(\Modules\Captcha\Facades\Captcha::class);
@endphp

<button type="button"
        class="{{ $btnClass }}"
        data-bs-toggle="modal"
        data-bs-target="#{{ $modalId }}"
        data-form-slug="{{ $form->slug }}"
        aria-haspopup="dialog"
        aria-controls="{{ $modalId }}">
    @if($icon)
        <i class="{{ $icon }} me-1" aria-hidden="true"></i>
    @endif
    {{ $text }}
</button>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}-title">{{ $form->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                @include('forms::public.partials.form-body', [
                    'form' => $form,
                    'formId' => $formId,
                    'theme' => $theme,
                    'columns' => 1,
                    'isMultiStep' => $isMultiStep,
                    'steps' => $steps,
                    'totalSteps' => $totalSteps,
                    'floatingLabel' => $floatingLabel,
                    'buttonText' => $form->submit_button_text ?? 'Enviar',
                    'buttonColor' => $form->button_color ?? null,
                    'showTitle' => false,
                    'captchaEnabled' => $captchaEnabled,
                ])
            </div>
        </div>
    </div>
</div>
