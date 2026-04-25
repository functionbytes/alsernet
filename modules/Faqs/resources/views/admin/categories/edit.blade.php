@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
            <p class="small mb-0 text-muted">Editar categoría de FAQ</p>
        </div>

        <form method="POST" action="{{ route('faqs.categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <ul class="nav nav-tabs" id="nameLangTabs" role="tablist">
                            @foreach($locales as $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $locale['is_default'] ? 'active' : '' }}" id="name-tab-{{ $locale['code'] }}"
                                        data-bs-toggle="tab" data-bs-target="#name-pane-{{ $locale['code'] }}"
                                        type="button" role="tab">{{ $locale['native_name'] }}</button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="tab-content pt-2" id="nameLangTabContent">
                            @foreach($locales as $locale)
                            @php
                                $translation = $category->translations->firstWhere('locale', $locale['code']);
                            @endphp
                            <div class="tab-pane fade {{ $locale['is_default'] ? 'show active' : '' }}" id="name-pane-{{ $locale['code'] }}" role="tabpanel">
                                <input type="text" name="translations[{{ $loop->index }}][name]"
                                       class="form-control @error('translations.*.name') is-invalid @enderror"
                                       value="{{ old('translations.'.$loop->index.'.name', $translation?->name ?? $category->name) }}" required>
                                <input type="hidden" name="translations[{{ $loop->index }}][locale]" value="{{ $locale['code'] }}">
                                @error('translations.*.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', $category->status->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <ul class="nav nav-tabs" id="descLangTabs" role="tablist">
                            @foreach($locales as $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $locale['is_default'] ? 'active' : '' }}" id="desc-tab-{{ $locale['code'] }}"
                                        data-bs-toggle="tab" data-bs-target="#desc-pane-{{ $locale['code'] }}"
                                        type="button" role="tab">{{ $locale['native_name'] }}</button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="tab-content pt-2" id="descLangTabContent">
                            @foreach($locales as $locale)
                            @php
                                $translation = $category->translations->firstWhere('locale', $locale['code']);
                            @endphp
                            <div class="tab-pane fade {{ $locale['is_default'] ? 'show active' : '' }}" id="desc-pane-{{ $locale['code'] }}" role="tabpanel">
                                <textarea name="translations[{{ $loop->index }}][description]" rows="3"
                                          class="form-control @error('translations.*.description') is-invalid @enderror">{{ old('translations.'.$loop->index.'.description', $translation?->description ?? $category->description) }}</textarea>
                                @error('translations.*.description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number" name="order" class="form-control @error('order') is-invalid @enderror"
                               value="{{ old('order', $category->order) }}" min="0">
                        @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Actualizar categoría
                </button>
                <a href="{{ route('faqs.categories.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>

@endsection
