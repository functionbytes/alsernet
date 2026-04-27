@extends('layouts.theme')

@section('title', 'Editar plantilla SEO')

@section('content')
    @include('core::components.card', ['title' => 'Editar plantilla SEO'])

    @include('core::components.alerts')

    <form action="{{ route('settings.seo.templates.update', $template) }}" method="POST" novalidate>
        @csrf
        @method('PUT')

        <div class="row">

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Variables disponibles</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Usa estas variables en los patrones de título y descripción:</p>
                        <ul class="list-unstyled  mb-0">
                            <li class="mb-1"><code>{title}</code> — Título del modelo</li>
                            <li class="mb-1"><code>{name}</code> — Nombre del modelo</li>
                            <li class="mb-1"><code>{description}</code> — Descripción</li>
                            <li class="mb-1"><code>{excerpt}</code> — Extracto</li>
                            <li class="mb-1"><code>{site_name}</code> — Nombre del sitio</li>
                            <li class="mb-1"><code>{site_url}</code> — URL del sitio</li>
                            <li class="mb-1"><code>{year}</code> — Año actual</li>
                            <li class="mb-0"><code>{month}</code> — Mes actual</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Ejemplos</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong class=" d-block mb-1">Titulo de artículo:</strong>
                            <code class=" text-muted">{title} | {site_name}</code>
                        </div>
                        <div class="mb-0">
                            <strong class=" d-block mb-1">Descripcion de producto:</strong>
                            <code class=" text-muted">{excerpt} — Compra en {site_name}</code>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MAIN CONTENT --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Datos de la plantilla</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $template->name) }}"
                                   placeholder="Plantilla para artículos"
                                   maxlength="100"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tipo de modelo</label>
                            <select name="model_type" class="form-select select2 @error('model_type') is-invalid @enderror">
                                <option value="" {{ old('model_type', $template->model_type) === null || old('model_type', $template->model_type) === '' ? 'selected' : '' }}>
                                    Global (aplica a todos)
                                </option>
                                <option value="Page" {{ old('model_type', $template->model_type) === 'Page' ? 'selected' : '' }}>Page</option>
                                <option value="BlogPost" {{ old('model_type', $template->model_type) === 'BlogPost' ? 'selected' : '' }}>BlogPost</option>
                                @php $currentType = old('model_type', $template->model_type); @endphp
                                @if($currentType && !in_array($currentType, ['Page', 'BlogPost']))
                                    <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
                                @endif
                            </select>
                            @error('model_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Selecciona un tipo para aplicar solo a ese contenido, o deja en global para todos.</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Patron de titulo</label>
                            <input type="text"
                                   name="title_pattern"
                                   class="form-control font-monospace @error('title_pattern') is-invalid @enderror"
                                   value="{{ old('title_pattern', $template->title_pattern) }}"
                                   placeholder="{title} | {site_name}"
                                   maxlength="200">
                            @error('title_pattern')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Se recomienda no superar 60 caracteres. Usa variables del panel lateral como <code>{title}</code> o <code>{site_name}</code>.</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Patron de descripcion</label>
                            <textarea name="description_pattern"
                                      class="form-control font-monospace @error('description_pattern') is-invalid @enderror"
                                      rows="3"
                                      placeholder="{excerpt} - {site_name}"
                                      maxlength="500">{{ old('description_pattern', $template->description_pattern) }}</textarea>
                            @error('description_pattern')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Se recomienda no superar 160 caracteres. Aparece como resumen en los resultados de busqueda.</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">OG Type</label>
                            <select name="og_type" class="form-select select2 @error('og_type') is-invalid @enderror">
                                @foreach(['website' => 'Website', 'article' => 'Article', 'product' => 'Product', 'profile' => 'Profile', 'book' => 'Book'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('og_type', $template->og_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('og_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">Twitter card</label>
                            <select name="twitter_card" class="form-select select2 @error('twitter_card') is-invalid @enderror">
                                <option value="summary" {{ old('twitter_card', $template->twitter_card) === 'summary' ? 'selected' : '' }}>Summary</option>
                                <option value="summary_large_image" {{ old('twitter_card', $template->twitter_card) === 'summary_large_image' ? 'selected' : '' }}>Summary large image</option>
                                <option value="app" {{ old('twitter_card', $template->twitter_card) === 'app' ? 'selected' : '' }}>App</option>
                                <option value="player" {{ old('twitter_card', $template->twitter_card) === 'player' ? 'selected' : '' }}>Player</option>
                            </select>
                            @error('twitter_card')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">Robots <span class="text-danger">*</span></label>
                            <select name="robots" class="form-select select2 @error('robots') is-invalid @enderror" required>
                                <option value="index,follow" {{ old('robots', $template->robots) === 'index,follow' ? 'selected' : '' }}>index, follow</option>
                                <option value="noindex,follow" {{ old('robots', $template->robots) === 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                                <option value="index,nofollow" {{ old('robots', $template->robots) === 'index,nofollow' ? 'selected' : '' }}>index, nofollow</option>
                                <option value="noindex,nofollow" {{ old('robots', $template->robots) === 'noindex,nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                            </select>
                            @error('robots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">Prioridad</label>
                            <input type="number"
                                   name="priority"
                                   class="form-control @error('priority') is-invalid @enderror"
                                   value="{{ old('priority', $template->priority) }}"
                                   min="0"
                                   max="255">
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Las plantillas con mayor prioridad se aplican primero. Rango de 0 a 255.</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="is_active" id="is_active" class="form-select select2">
                                <option value="1" {{ old('is_active', $template->is_active) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active', $template->is_active) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                    </div>

                    <div class="card-footer gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Guardar cambios
                        </button>
                        <a href="{{ route('settings.seo.templates.index') }}" class="btn btn-light border w-100 py-2">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%', minimumResultsForSearch: Infinity });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
