@extends('layouts.theme')

@section('title', 'Nuevo Test A/B')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.ab-tests.index') }}">Tests A/B</a></li>
                <li class="breadcrumb-item active">Nuevo Test</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-flask me-2"></i>Nuevo Test A/B
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.ab-tests.store') }}" method="POST">
                            @csrf

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                <div>
                                    <strong>¿Qué es un Test A/B?</strong>
                                    <p class="mb-0 mt-1">
                                        Un test A/B te permite comparar dos versiones de una publicación para ver cuál funciona mejor.
                                        Publica ambas variantes y compara métricas como engagement, likes o comentarios para determinar la ganadora.
                                    </p>
                                </div>
                            </div>

                            <!-- Variant A Selection -->
                            <div class="mb-4">
                                <label for="variant_a_post_id" class="form-label fw-semibold">
                                    Variante A <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('variant_a_post_id') is-invalid @enderror"
                                        id="variant_a_post_id"
                                        name="variant_a_post_id"
                                        required>
                                    <option value="">Selecciona la primera publicación</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}"
                                                {{ old('variant_a_post_id') == $post->id ? 'selected' : '' }}
                                                data-content="{{ $post->content }}">
                                            #{{ $post->id }} - {{ Str::limit($post->content, 60) }} ({{ $post->status->label() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('variant_a_post_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="variant-a-preview" class="mt-2 p-3 bg-primary-subtle rounded d-none">
                                    <small class="text-muted fw-semibold d-block mb-1">Vista previa Variante A:</small>
                                    <small class="text-dark"></small>
                                </div>
                            </div>

                            <!-- Variant B Selection -->
                            <div class="mb-4">
                                <label for="variant_b_post_id" class="form-label fw-semibold">
                                    Variante B <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('variant_b_post_id') is-invalid @enderror"
                                        id="variant_b_post_id"
                                        name="variant_b_post_id"
                                        required>
                                    <option value="">Selecciona la segunda publicación</option>
                                    @foreach($posts as $post)
                                        <option value="{{ $post->id }}"
                                                {{ old('variant_b_post_id') == $post->id ? 'selected' : '' }}
                                                data-content="{{ $post->content }}">
                                            #{{ $post->id }} - {{ Str::limit($post->content, 60) }} ({{ $post->status->label() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('variant_b_post_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="variant-b-preview" class="mt-2 p-3 bg-info-subtle rounded d-none">
                                    <small class="text-muted fw-semibold d-block mb-1">Vista previa Variante B:</small>
                                    <small class="text-dark"></small>
                                </div>
                            </div>

                            <!-- Metric Selection -->
                            <div class="mb-4">
                                <label for="metric" class="form-label fw-semibold">
                                    Métrica de Comparación <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('metric') is-invalid @enderror"
                                        id="metric"
                                        name="metric"
                                        required>
                                    <option value="engagement" {{ old('metric', 'engagement') == 'engagement' ? 'selected' : '' }}>
                                        Engagement Total (likes + comentarios + shares)
                                    </option>
                                    <option value="likes" {{ old('metric') == 'likes' ? 'selected' : '' }}>
                                        Likes
                                    </option>
                                    <option value="comments" {{ old('metric') == 'comments' ? 'selected' : '' }}>
                                        Comentarios
                                    </option>
                                    <option value="shares" {{ old('metric') == 'shares' ? 'selected' : '' }}>
                                        Compartidos
                                    </option>
                                    <option value="clicks" {{ old('metric') == 'clicks' ? 'selected' : '' }}>
                                        Clics
                                    </option>
                                </select>
                                @error('metric')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror>
                                <small class="text-muted">La métrica que se usará para determinar qué variante funciona mejor</small>
                            </div>

                            <!-- Warning Alert -->
                            <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Importante:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Asegúrate de que ambas publicaciones estén programadas o publicadas</li>
                                        <li>El test comparará las métricas acumuladas de ambas variantes</li>
                                        <li>Puedes finalizar el test manualmente cuando tengas suficientes datos</li>
                                        <li>La variante ganadora se determinará automáticamente al completar el test</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.ab-tests.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Crear Test A/B
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Show preview of selected posts
    const variantASelect = document.getElementById('variant_a_post_id');
    const variantBSelect = document.getElementById('variant_b_post_id');
    const variantAPreview = document.getElementById('variant-a-preview');
    const variantBPreview = document.getElementById('variant-b-preview');

    variantASelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const content = selectedOption.getAttribute('data-content');
            variantAPreview.querySelector('small.text-dark').textContent = content;
            variantAPreview.classList.remove('d-none');
        } else {
            variantAPreview.classList.add('d-none');
        }
    });

    variantBSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const content = selectedOption.getAttribute('data-content');
            variantBPreview.querySelector('small.text-dark').textContent = content;
            variantBPreview.classList.remove('d-none');
        } else {
            variantBPreview.classList.add('d-none');
        }
    });

    // Prevent selecting the same post for both variants
    variantASelect.addEventListener('change', function() {
        if (this.value && this.value === variantBSelect.value) {
            alert('No puedes seleccionar la misma publicación para ambas variantes');
            this.value = '';
            variantAPreview.classList.add('d-none');
        }
    });

    variantBSelect.addEventListener('change', function() {
        if (this.value && this.value === variantASelect.value) {
            alert('No puedes seleccionar la misma publicación para ambas variantes');
            this.value = '';
            variantBPreview.classList.add('d-none');
        }
    });

    // Trigger change on load if values are pre-selected
    if (variantASelect.value) variantASelect.dispatchEvent(new Event('change'));
    if (variantBSelect.value) variantBSelect.dispatchEvent(new Event('change'));
</script>
@endpush
