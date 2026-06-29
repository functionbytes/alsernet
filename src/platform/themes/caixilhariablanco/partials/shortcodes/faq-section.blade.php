@php
    $title    = $title ?? 'Preguntas frecuentes';
    $subtitle = $subtitle ?? 'Hemos recopilado las preguntas más habituales para que encuentre toda la información que necesita.';
    $cta_text = $cta_text ?? '¿Tiene más preguntas?';
    $cta_url  = $cta_url ?? 'tel:+351913893833';
    $accordionId = 'faqSc-' . Str::random(6);
@endphp

@if(! empty($items))
<div class="faq1-section-area sp1">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <div class="faq-header-area heading6">
                    <h5 class="wow fadeInUp">FAQ'S</h5>
                    <h2 class="tg-element-title wow fadeInUp" data-wow-delay="0.1s">{{ $title }}</h2>
                    <p class="wow fadeInUp" data-wow-delay="0.2s">{{ $subtitle }}</p>
                    <div class="btn-area wow fadeInUp" data-wow-delay="0.3s">
                        <a href="{{ $cta_url }}" class="header-btn1">
                            {{ $cta_text }} <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-1"></div>
            <div class="col-lg-6">
                <div class="faq-auhtoir-area1 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="accordion accordion-flush" id="{{ $accordionId }}">
                        @foreach($items as $idx => $item)
                        @if(! empty($item['question']) && ! empty($item['answer']))
                        @if($idx > 0)<div class="space20"></div>@endif
                        <div class="accordion-item wow fadeInUp" data-wow-delay="{{ $idx * 0.1 }}s">
                            <h2 class="accordion-header">
                                <button class="accordion-button{{ $idx > 0 ? ' collapsed' : '' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $accordionId }}-{{ $idx }}"
                                        aria-expanded="{{ $idx === 0 ? 'true' : 'false' }}"
                                        aria-controls="{{ $accordionId }}-{{ $idx }}">
                                    {{ $item['question'] }}
                                </button>
                            </h2>
                            <div id="{{ $accordionId }}-{{ $idx }}"
                                 class="accordion-collapse collapse{{ $idx === 0 ? ' show' : '' }}"
                                 data-bs-parent="#{{ $accordionId }}">
                                <div class="accordion-body">{{ $item['answer'] }}</div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
