@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
            <p class="small mb-0 text-muted">Crea una nueva pregunta frecuente</p>
        </div>

        <form method="POST" action="{{ route('faqs.store') }}">
            @csrf

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Pregunta <span class="text-danger">*</span></label>
                        <ul class="nav nav-tabs" id="questionLangTabs" role="tablist">
                            @foreach($locales as $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $locale['is_default'] ? 'active' : '' }}" id="q-tab-{{ $locale['code'] }}"
                                        data-bs-toggle="tab" data-bs-target="#q-pane-{{ $locale['code'] }}"
                                        type="button" role="tab">{{ $locale['native_name'] }}</button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="tab-content pt-2" id="questionLangTabContent">
                            @foreach($locales as $locale)
                            <div class="tab-pane fade {{ $locale['is_default'] ? 'show active' : '' }}" id="q-pane-{{ $locale['code'] }}" role="tabpanel">
                                <input type="text" name="translations[{{ $loop->index }}][question]"
                                       class="form-control @error('translations.*.question') is-invalid @enderror"
                                       value="{{ old('translations.'.$loop->index.'.question') }}" required>
                                <input type="hidden" name="translations[{{ $loop->index }}][locale]" value="{{ $locale['code'] }}">
                                @error('translations.*.question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Respuesta <span class="text-danger">*</span></label>
                        <ul class="nav nav-tabs" id="answerLangTabs" role="tablist">
                            @foreach($locales as $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $locale['is_default'] ? 'active' : '' }}" id="a-tab-{{ $locale['code'] }}"
                                        data-bs-toggle="tab" data-bs-target="#a-pane-{{ $locale['code'] }}"
                                        type="button" role="tab">{{ $locale['native_name'] }}</button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="tab-content pt-2" id="answerLangTabContent">
                            @foreach($locales as $locale)
                            <div class="tab-pane fade {{ $locale['is_default'] ? 'show active' : '' }}" id="a-pane-{{ $locale['code'] }}" role="tabpanel">
                                <textarea name="translations[{{ $loop->index }}][answer]" rows="6"
                                          class="form-control @error('translations.*.answer') is-invalid @enderror" required>{{ old('translations.'.$loop->index.'.answer') }}</textarea>
                                @error('translations.*.answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', 'published') === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number" name="order" class="form-control @error('order') is-invalid @enderror"
                               value="{{ old('order', 0) }}" min="0">
                        @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar pregunta
                </button>
                <a href="{{ route('faqs.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>

@endsection
