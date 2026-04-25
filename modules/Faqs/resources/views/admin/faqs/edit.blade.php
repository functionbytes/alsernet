@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
            <p class="small mb-0 text-muted">Editar pregunta frecuente</p>
        </div>

        <form method="POST" action="{{ route('faqs.update', $faq) }}">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Pregunta <span class="text-danger">*</span></label>
                        <input type="text" name="question" class="form-control @error('question') is-invalid @enderror"
                               value="{{ old('question', $faq->question) }}" required>
                        @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $faq->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Respuesta <span class="text-danger">*</span></label>
                        <textarea name="answer" rows="6" class="form-control @error('answer') is-invalid @enderror" required>{{ old('answer', $faq->answer) }}</textarea>
                        @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', $faq->status->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number" name="order" class="form-control @error('order') is-invalid @enderror"
                               value="{{ old('order', $faq->order) }}" min="0">
                        @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Actualizar pregunta
                </button>
                <a href="{{ route('faqs.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>

@endsection
