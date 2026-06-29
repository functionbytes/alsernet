@if($title)
    <h3 class="fw-bold mb-4">{{ $title }}</h3>
@endif

@if($faqs->isNotEmpty())
    <div class="accordion" id="faqShortcodeAccordion{{ $instanceId }}">
        @foreach($faqs as $index => $faq)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#faqSc{{ $instanceId }}_{{ $faq->id }}">
                        {{ $faq->trans('question') }}
                    </button>
                </h2>
                <div id="faqSc{{ $instanceId }}_{{ $faq->id }}"
                     class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                     data-bs-parent="#faqShortcodeAccordion{{ $instanceId }}">
                    <div class="accordion-body">
                        {!! nl2br(e($faq->trans('answer'))) !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
