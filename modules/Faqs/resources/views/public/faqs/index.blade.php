@extends('layouts.theme')

@section('title', 'Preguntas frecuentes')

@section('content')

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Preguntas frecuentes</h1>

            @forelse($categories as $category)
                <div class="mb-4">
                    <h4 class="fw-semibold mb-3">{{ $category->trans('name') }}</h4>

                    <div class="accordion" id="faqAccordion{{ $category->id }}">
                        @foreach($category->publishedFaqs as $index => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#faqCollapse{{ $faq->id }}">
                                        {{ $faq->trans('question') }}
                                    </button>
                                </h2>
                                <div id="faqCollapse{{ $faq->id }}"
                                     class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                     data-bs-parent="#faqAccordion{{ $category->id }}">
                                    <div class="accordion-body">
                                        {!! nl2br(e($faq->trans('answer'))) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No hay preguntas frecuentes disponibles.
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
