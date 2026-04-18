<div class="forms-wrapper forms-theme-{{ $theme }}" id="{{ $formId }}-wrapper">
    @if($showTitle)
    <h3 class="forms-title mb-3">{{ $form->name }}</h3>
    @endif

    {{-- Barra de progreso multi-paso --}}
    @if($isMultiStep)
    <div class="forms-progress mb-4" data-style="{{ $form->progress_bar_style ?? 'bar' }}">
        @if(($form->progress_bar_style ?? 'bar') === 'bar')
        <div class="progress" style="height:6px">
            <div class="progress-bar bg-success" style="width: {{ (1/$totalSteps)*100 }}%" id="{{ $formId }}-progress-bar"></div>
        </div>
        <small class="text-muted mt-1 d-block">Paso <span id="{{ $formId }}-current-step">1</span> de {{ $totalSteps }}</small>
        @elseif(($form->progress_bar_style ?? 'bar') === 'dots')
        <div class="d-flex gap-2 align-items-center">
            @foreach($steps as $s)
            <div class="forms-step-dot {{ $loop->first ? 'active' : '' }}" data-step="{{ $s }}"></div>
            @endforeach
        </div>
        @elseif(($form->progress_bar_style ?? 'bar') === 'steps')
        <div class="d-flex gap-3 align-items-center flex-wrap">
            @foreach($steps as $s)
            @php $stepNames = $form->steps_config[$s-1]['name'] ?? "Paso {$s}"; @endphp
            <div class="forms-step-label {{ $loop->first ? 'active' : '' }}" data-step="{{ $s }}">
                <span class="forms-step-number">{{ $s }}</span> {{ $stepNames }}
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Mensaje de éxito (oculto inicialmente) --}}
    <div class="forms-success-message d-none" id="{{ $formId }}-success">
        <div class="alert alert-success text-center py-4">
            <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>
            <p class="mb-0">{{ $form->success_message ?? config('forms.default_success_message') }}</p>
        </div>
    </div>

    {{-- Formulario --}}
    <form id="{{ $formId }}" class="forms-form" method="post" novalidate data-config="{{ $formId }}">
        @csrf
        {{-- Campo honeypot (oculto, debe estar vacío) --}}
        @if($form->honeypot_enabled)
        <div style="display:none !important" aria-hidden="true">
            <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off">
        </div>
        @endif
        {{-- Tiempo de completado (se calcula en JS) --}}
        <input type="hidden" name="_time_to_complete" id="{{ $formId }}-time">
        <input type="hidden" name="_start_time" value="{{ time() }}">

        @foreach($steps as $step)
        <div class="forms-step {{ $loop->first ? 'forms-step-active' : 'd-none' }}" data-step="{{ $step }}">
            @php
                $stepFields = $form->fields->where('step_number', $step)
                    ->where('is_visible', true)
                    ->sortBy('sort_order');
            @endphp
            <div class="row g-3">
                @foreach($stepFields as $field)
                    @include('forms::public.partials.field', compact('field', 'formId', 'floatingLabel'))
                @endforeach
            </div>

            {{-- Botones de navegación multi-paso --}}
            @if($isMultiStep)
                <div class="forms-step-nav mt-4">
                    @if($loop->first && !$loop->last)
                        {{-- Solo Siguiente: ancho completo --}}
                        <button type="button" class="btn btn-primary forms-next-btn w-100" data-step="{{ $step }}">
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    @elseif(!$loop->first && !$loop->last)
                        {{-- Anterior + Siguiente: mitad y mitad --}}
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary forms-prev-btn w-50" data-step="{{ $step }}">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                            <button type="button" class="btn btn-primary forms-next-btn w-50" data-step="{{ $step }}">
                                Siguiente <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    @else
                        {{-- Último paso: Anterior + Submit --}}
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary forms-prev-btn" data-step="{{ $step }}">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                            <div>
                                @if(!empty($captchaEnabled) && $captchaEnabled && class_exists('\Modules\Captcha\Facades\Captcha'))
                                <div class="mb-3 w-100">
                                    {!! \Modules\Captcha\Facades\Captcha::display() !!}
                                </div>
                                @endif
                                @include('forms::public.partials.submit-button', compact('form', 'formId', 'buttonText', 'buttonColor'))
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        @endforeach

        {{-- Botón submit (sin multi-paso) --}}
        @if(!$isMultiStep)
        <div class="mt-4 text-{{ $form->button_position ?? 'start' }}">
            @if(!empty($captchaEnabled) && $captchaEnabled && class_exists('\Modules\Captcha\Facades\Captcha'))
            <div class="mb-3">
                {!! \Modules\Captcha\Facades\Captcha::display() !!}
            </div>
            @endif
            @include('forms::public.partials.submit-button', compact('form', 'formId', 'buttonText', 'buttonColor'))
        </div>
        @endif
    </form>
</div>
